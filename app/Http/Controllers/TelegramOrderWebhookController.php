<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\TelegramOrderNotifier;
use App\Support\OrderTelegramWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramOrderWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $secret,
        TelegramOrderNotifier $telegramOrderNotifier,
    ): JsonResponse {
        $configuredSecret = $telegramOrderNotifier->webhookSecret();

        abort_unless(
            is_string($configuredSecret) && $configuredSecret !== '' && hash_equals($configuredSecret, $secret),
            404,
        );

        $callbackQuery = $request->input('callback_query');

        if (! is_array($callbackQuery)) {
            return response()->json(['ok' => true]);
        }

        $chatId = (string) data_get($callbackQuery, 'message.chat.id', '');
        $configuredChatId = (string) ($telegramOrderNotifier->ordersChatId() ?? '');
        $callbackId = data_get($callbackQuery, 'id');
        $data = data_get($callbackQuery, 'data');

        if (! is_string($callbackId) || ! is_string($data) || $chatId !== $configuredChatId) {
            $telegramOrderNotifier->answerCallbackQuery($callbackId, 'Эта кнопка недоступна.');

            return response()->json(['ok' => true]);
        }

        [$action, $publicId] = array_pad(explode(':', $data, 2), 2, null);

        if (! is_string($action) || ! is_string($publicId) || $publicId === '') {
            $telegramOrderNotifier->answerCallbackQuery($callbackId, 'Не удалось обработать действие.');

            return response()->json(['ok' => true]);
        }

        if (in_array($action, ['confirmed', 'cancelled', 'error'], true)) {
            $telegramOrderNotifier->answerCallbackQuery($callbackId, match ($action) {
                'confirmed' => 'Заказ уже подтверждён.',
                'cancelled' => 'Заказ уже отменён.',
                default => 'При обновлении уже была ошибка. Попробуйте ещё раз из админки.',
            });

            return response()->json(['ok' => true]);
        }

        $targetStatus = OrderTelegramWorkflow::targetStatus($action);

        if (! $targetStatus instanceof OrderStatus) {
            $telegramOrderNotifier->answerCallbackQuery($callbackId, 'Не удалось обработать действие.');

            return response()->json(['ok' => true]);
        }

        $order = Order::query()
            ->with('deliveryGroups.items.components')
            ->where('public_id', $publicId)
            ->first();

        if (! $order) {
            $telegramOrderNotifier->answerCallbackQuery($callbackId, 'Заказ не найден.');

            return response()->json(['ok' => true]);
        }

        if ($order->status === $targetStatus) {
            $telegramOrderNotifier->answerCallbackQuery($callbackId, 'Статус уже обновлён.');
            $telegramOrderNotifier->syncOrderMessage($order);

            return response()->json(['ok' => true]);
        }

        if (! OrderTelegramWorkflow::canApply($order, $targetStatus)) {
            $telegramOrderNotifier->answerCallbackQuery($callbackId, 'Заказ нельзя перевести в этот статус.');

            return response()->json(['ok' => true]);
        }

        try {
            $order->update([
                'status' => $targetStatus,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to update order status from Telegram webhook.', [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'target_status' => $targetStatus->value,
                'message' => $exception->getMessage(),
            ]);

            $telegramOrderNotifier->syncOrderMessage($order, 'error');
            $telegramOrderNotifier->answerCallbackQuery($callbackId, 'Произошла ошибка при обновлении заказа.');

            return response()->json(['ok' => true]);
        }

        $telegramOrderNotifier->answerCallbackQuery($callbackId, OrderTelegramWorkflow::successMessage($targetStatus));

        return response()->json(['ok' => true]);
    }
}
