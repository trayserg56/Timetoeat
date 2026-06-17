<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderDeliveryGroup;
use App\Models\OrderItem;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramOrderNotifier
{
    public function sendNewOrder(Order $order): void
    {
        $botToken = $this->botToken();
        $chatId = $this->ordersChatId();

        if (! is_string($botToken) || $botToken === '' || ! is_string($chatId) || $chatId === '') {
            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $this->buildMessage($order),
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                    'reply_markup' => json_encode($this->buildReplyMarkup($order), JSON_UNESCAPED_UNICODE),
                ])
                ->throw();

            $messageId = data_get($response->json(), 'result.message_id');

            if (is_numeric($messageId)) {
                $order->forceFill([
                    'telegram_chat_id' => $chatId,
                    'telegram_message_id' => (int) $messageId,
                ])->saveQuietly();
            }
        } catch (\Throwable $exception) {
            Log::warning('Failed to send Telegram order notification.', [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function syncOrderMessage(Order $order, ?string $actionState = null): bool
    {
        if (! is_int($order->telegram_message_id) || ! is_string($order->telegram_chat_id) || $order->telegram_chat_id === '') {
            $this->sendNewOrder($order);

            return is_int($order->fresh()->telegram_message_id);
        }

        $botToken = $this->botToken();

        if (! is_string($botToken) || $botToken === '') {
            return false;
        }

        try {
            Http::asForm()
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$botToken}/editMessageText", [
                    'chat_id' => $order->telegram_chat_id,
                    'message_id' => $order->telegram_message_id,
                    'text' => $this->buildMessage($order),
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                    'reply_markup' => json_encode($this->buildReplyMarkup($order, $actionState), JSON_UNESCAPED_UNICODE),
                ])
                ->throw();

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Failed to sync Telegram order notification.', [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function sendStatusChangeNotice(Order $order, OrderStatus $previousStatus): void
    {
        if ($previousStatus === $order->status) {
            return;
        }

        $this->sendThreadNotice(
            $order,
            '🔄 <b>'.$this->escape($order->number).'</b>'."\n"
            .$this->escape($previousStatus->getLabel()).' → <b>'.$this->escape($order->status->getLabel()).'</b>',
        );
    }

    public function sendPaymentStatusChangeNotice(Order $order, PaymentStatus $previousStatus): void
    {
        if ($previousStatus === $order->payment_status) {
            return;
        }

        $this->sendThreadNotice(
            $order,
            '💳 <b>'.$this->escape($order->number).'</b>'."\n"
            .'Оплата: '.$this->escape($previousStatus->getLabel()).' → <b>'.$this->escape($order->payment_status->getLabel()).'</b>',
        );
    }

    public function answerCallbackQuery(?string $callbackQueryId, string $text): bool
    {
        $botToken = $this->botToken();

        if (! is_string($botToken) || $botToken === '' || ! is_string($callbackQueryId) || $callbackQueryId === '') {
            return false;
        }

        try {
            Http::asForm()
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$botToken}/answerCallbackQuery", [
                    'callback_query_id' => $callbackQueryId,
                    'text' => $text,
                ])
                ->throw();

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Failed to answer Telegram callback query.', [
                'callback_query_id' => $callbackQueryId,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    protected function sendThreadNotice(Order $order, string $text): void
    {
        $botToken = $this->botToken();

        if (
            ! is_string($botToken) || $botToken === ''
            || ! is_string($order->telegram_chat_id) || $order->telegram_chat_id === ''
            || ! is_int($order->telegram_message_id)
        ) {
            return;
        }

        try {
            Http::asForm()
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $order->telegram_chat_id,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                    'reply_to_message_id' => $order->telegram_message_id,
                    'allow_sending_without_reply' => true,
                ])
                ->throw();
        } catch (\Throwable $exception) {
            Log::warning('Failed to send Telegram status change notice.', [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    protected function buildMessage(Order $order): string
    {
        $lines = [
            $this->buildMessageTitle($order),
            '',
            'Клиент: '.$this->escape($order->customer_name),
            'Телефон: '.$this->escape($order->customer_phone),
            'Telegram: '.$this->escape($order->customer_telegram_username),
        ];

        if ($order->customer_email) {
            $lines[] = 'Email: '.$this->escape($order->customer_email);
        }

        $lines[] = '';
        $lines[] = 'Доставка: '.$this->escape($order->delivery_date?->format('d.m.Y') ?? (string) $order->delivery_date);
        $lines[] = 'Интервал: '.$this->escape($order->delivery_interval);
        $lines[] = 'Сумма товаров: '.$this->formatPrice($order->subtotal);
        $lines[] = 'Доставка: '.$this->formatPrice($order->delivery_price);
        $lines[] = '<b>Итого: '.$this->formatPrice($order->total).'</b>';
        $lines[] = 'Статус заказа: <b>'.$this->escape($order->status->getLabel()).'</b>';
        $lines[] = 'Оплата: '.$this->escape($order->payment_status->getLabel());
        $lines[] = '';

        $lines = [
            ...$lines,
            ...$order->deliveryGroups
                ->values()
                ->flatMap(fn (OrderDeliveryGroup $group, int $index): array => $this->formatGroup($group, $index + 1))
                ->all(),
        ];

        return implode("\n", $lines);
    }

    protected function buildMessageTitle(Order $order): string
    {
        $emoji = match ($order->status) {
            OrderStatus::New => '🆕',
            OrderStatus::Confirmed => '✅',
            OrderStatus::Cooking => '👨‍🍳',
            OrderStatus::Ready => '🍱',
            OrderStatus::Delivering => '🚚',
            OrderStatus::Completed => '✔️',
            OrderStatus::Cancelled => '❌',
        };

        return $emoji.' <b>Заказ '.$this->escape($order->number).'</b>';
    }

    protected function formatGroup(OrderDeliveryGroup $group, int $number): array
    {
        $lines = [
            '<b>Адрес '.$number.'</b>',
            $this->escape($group->delivery_address),
        ];

        if ($group->customer_comment) {
            $lines[] = 'Комментарий: '.$this->escape($group->customer_comment);
        }

        $lines[] = 'Товары:';

        foreach ($group->items as $item) {
            $lines[] = '• '.$this->formatItem($item);
        }

        $lines[] = 'Подытог: '.$this->formatPrice($group->subtotal);
        $lines[] = 'Доставка по адресу: '.$this->formatPrice($group->delivery_price);
        $lines[] = 'Итого по адресу: '.$this->formatPrice($group->total);
        $lines[] = '';

        return $lines;
    }

    protected function formatItem(OrderItem $item): string
    {
        $line = $this->escape($item->name).' x'.$item->quantity.' — '.$this->formatPrice($item->total_price);

        if ($item->type !== 'meal_set' || $item->components->isEmpty()) {
            return $line;
        }

        $components = $item->components
            ->map(fn ($component): string => $this->escape($component->name).' x'.$component->quantity)
            ->implode(', ');

        return $line."\n  Состав: ".$components;
    }

    protected function formatPrice(int $kopecks): string
    {
        return number_format($kopecks / 100, 0, '', ' ').' ₽';
    }

    public function ordersChatId(): ?string
    {
        $value = SiteSetting::current()->telegram_orders_chat_id ?: config('services.telegram.orders_chat_id');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function botToken(): ?string
    {
        $value = SiteSetting::current()->telegram_bot_token ?: config('services.telegram.bot_token');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function webhookSecret(): ?string
    {
        $value = SiteSetting::current()->telegram_webhook_secret ?: config('services.telegram.webhook_secret');

        return is_string($value) && $value !== '' ? $value : null;
    }

    protected function buildReplyMarkup(Order $order, ?string $actionState = null): array
    {
        if ($actionState === 'error') {
            return [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '⚠️ Ошибка обновления',
                            'callback_data' => 'error:'.$order->public_id,
                        ],
                    ],
                ],
            ];
        }

        return match ($order->status) {
            OrderStatus::New => [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ Заказ принят', 'callback_data' => 'confirm:'.$order->public_id],
                        ['text' => '❌ Отменить', 'callback_data' => 'cancel:'.$order->public_id],
                    ],
                ],
            ],
            OrderStatus::Confirmed => [
                'inline_keyboard' => [
                    [
                        ['text' => '👨‍🍳 Готовится', 'callback_data' => 'cooking:'.$order->public_id],
                        ['text' => '❌ Отменить', 'callback_data' => 'cancel:'.$order->public_id],
                    ],
                ],
            ],
            OrderStatus::Cooking => [
                'inline_keyboard' => [
                    [
                        ['text' => '🍱 Готов', 'callback_data' => 'ready:'.$order->public_id],
                        ['text' => '❌ Отменить', 'callback_data' => 'cancel:'.$order->public_id],
                    ],
                ],
            ],
            OrderStatus::Ready => [
                'inline_keyboard' => [
                    [
                        ['text' => '🚚 В доставке', 'callback_data' => 'delivering:'.$order->public_id],
                        ['text' => '❌ Отменить', 'callback_data' => 'cancel:'.$order->public_id],
                    ],
                ],
            ],
            OrderStatus::Delivering => [
                'inline_keyboard' => [
                    [
                        ['text' => '✔️ Завершён', 'callback_data' => 'complete:'.$order->public_id],
                    ],
                ],
            ],
            OrderStatus::Completed => [
                'inline_keyboard' => [
                    [
                        ['text' => '✔️ Завершён', 'callback_data' => 'completed:'.$order->public_id],
                    ],
                ],
            ],
            OrderStatus::Cancelled => [
                'inline_keyboard' => [
                    [
                        ['text' => '❌ Отменён', 'callback_data' => 'cancelled:'.$order->public_id],
                    ],
                ],
            ],
        };
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
