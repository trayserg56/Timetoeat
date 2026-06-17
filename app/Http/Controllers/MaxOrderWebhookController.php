<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\MaxOrderNotifier;
use App\Support\OrderWebhookConfirmation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MaxOrderWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $secret,
        MaxOrderNotifier $maxOrderNotifier,
    ): JsonResponse {
        $configuredSecret = $maxOrderNotifier->webhookSecret();

        abort_unless(
            is_string($configuredSecret) && $configuredSecret !== '' && hash_equals($configuredSecret, $secret),
            404,
        );

        $updateType = $request->input('update_type');

        if ($updateType !== 'message_callback') {
            return response()->json(['ok' => true]);
        }

        $callback = $request->input('callback');

        if (! is_array($callback)) {
            return response()->json(['ok' => true]);
        }

        $callbackId = data_get($callback, 'callback_id');
        $payload = data_get($callback, 'payload');
        $chatId = (string) data_get($callback, 'message.recipient.chat_id', '');
        $configuredChatId = (string) ($maxOrderNotifier->ordersChatId() ?? '');

        if (! is_string($callbackId) || ! is_string($payload) || $chatId !== $configuredChatId) {
            if (is_string($callbackId)) {
                $maxOrderNotifier->answerCallbackQuery($callbackId, 'Эта кнопка недоступна.');
            }

            return response()->json(['ok' => true]);
        }

        [$action, $publicId] = array_pad(explode(':', $payload, 2), 2, null);

        if ($action !== 'confirm' || ! is_string($publicId) || $publicId === '') {
            if ($action === 'confirmed') {
                $maxOrderNotifier->answerCallbackQuery($callbackId, 'Заказ уже подтверждён.');
            } elseif ($action === 'error') {
                $maxOrderNotifier->answerCallbackQuery($callbackId, 'При подтверждении уже была ошибка. Попробуйте ещё раз из админки.');
            } else {
                $maxOrderNotifier->answerCallbackQuery($callbackId, 'Не удалось обработать действие.');
            }

            return response()->json(['ok' => true]);
        }

        $order = Order::query()
            ->with('deliveryGroups.items.components')
            ->where('public_id', $publicId)
            ->first();

        if (! $order) {
            $maxOrderNotifier->answerCallbackQuery($callbackId, 'Заказ не найден.');

            return response()->json(['ok' => true]);
        }

        if ($order->status === OrderStatus::Confirmed) {
            $maxOrderNotifier->answerCallbackQuery($callbackId, 'Заказ уже подтверждён.');
            $maxOrderNotifier->syncOrderMessage($order);

            return response()->json(['ok' => true]);
        }

        if (! OrderWebhookConfirmation::canConfirm($order)) {
            $maxOrderNotifier->answerCallbackQuery($callbackId, 'Заказ нельзя подтвердить в текущем статусе.');

            return response()->json(['ok' => true]);
        }

        try {
            $order->update([
                'status' => OrderStatus::Confirmed,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to confirm order from MAX webhook.', [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'message' => $exception->getMessage(),
            ]);

            $maxOrderNotifier->syncOrderMessage($order, 'error');
            $maxOrderNotifier->answerCallbackQuery($callbackId, 'Произошла ошибка при подтверждении заказа.');

            return response()->json(['ok' => true]);
        }

        $maxOrderNotifier->answerCallbackQuery($callbackId, 'Заказ подтверждён.');

        return response()->json(['ok' => true]);
    }
}
