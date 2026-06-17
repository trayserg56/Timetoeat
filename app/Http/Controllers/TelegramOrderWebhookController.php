<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\TelegramOrderNotifier;
use App\Support\OrderWebhookConfirmation;
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

        if ($action !== 'confirm' || ! is_string($publicId) || $publicId === '') {
            if ($action === 'confirmed') {
                $telegramOrderNotifier->answerCallbackQuery($callbackId, 'Заказ уже подтверждён.');
            } elseif ($action === 'error') {
                $telegramOrderNotifier->answerCallbackQuery($callbackId, 'При подтверждении уже была ошибка. Попробуйте ещё раз из админки.');
            } else {
                $telegramOrderNotifier->answerCallbackQuery($callbackId, 'Не удалось обработать действие.');
            }

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

        if ($order->status === OrderStatus::Confirmed) {
            $telegramOrderNotifier->answerCallbackQuery($callbackId, 'Заказ уже подтверждён.');
            $telegramOrderNotifier->syncOrderMessage($order);

            return response()->json(['ok' => true]);
        }

        if (! OrderWebhookConfirmation::canConfirm($order)) {
            $telegramOrderNotifier->answerCallbackQuery($callbackId, 'Заказ нельзя подтвердить в текущем статусе.');

            return response()->json(['ok' => true]);
        }

        try {
            $order->update([
                'status' => OrderStatus::Confirmed,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to confirm order from Telegram webhook.', [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'message' => $exception->getMessage(),
            ]);

            $telegramOrderNotifier->syncOrderMessage($order, 'error');
            $telegramOrderNotifier->answerCallbackQuery($callbackId, 'Произошла ошибка при подтверждении заказа.');

            return response()->json(['ok' => true]);
        }

        $telegramOrderNotifier->answerCallbackQuery($callbackId, 'Заказ подтверждён.');

        return response()->json(['ok' => true]);
    }
}
