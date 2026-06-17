<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderDeliveryGroup;
use App\Models\OrderItem;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MaxOrderNotifier
{
    private const BASE_URL = 'https://platform-api.max.ru';

    public function sendNewOrder(Order $order): void
    {
        $botToken = $this->botToken();
        $chatId = $this->ordersChatId();

        if (! is_string($botToken) || $botToken === '' || ! is_string($chatId) || $chatId === '') {
            return;
        }

        try {
            $response = Http::withToken($botToken)
                ->timeout(10)
                ->post(self::BASE_URL.'/messages', [
                    'chat_id' => (int) $chatId,
                    'text' => $this->buildMessage($order),
                    'format' => 'html',
                    'attachments' => $this->buildAttachments($order),
                    'disable_link_preview' => true,
                ])
                ->throw();

            $messageId = data_get($response->json(), 'message.body.mid');

            if (is_string($messageId) && $messageId !== '') {
                $order->forceFill([
                    'max_chat_id' => $chatId,
                    'max_message_id' => $messageId,
                ])->saveQuietly();
            }
        } catch (\Throwable $exception) {
            Log::warning('Failed to send MAX order notification.', [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function syncOrderMessage(Order $order, ?string $actionState = null): bool
    {
        $botToken = $this->botToken();

        if (
            ! is_string($botToken) || $botToken === ''
            || ! is_string($order->max_message_id) || $order->max_message_id === ''
        ) {
            return false;
        }

        try {
            Http::withToken($botToken)
                ->timeout(10)
                ->put(self::BASE_URL.'/messages', [
                    'message_id' => $order->max_message_id,
                    'text' => $this->buildMessage($order),
                    'format' => 'html',
                    'attachments' => $this->buildAttachments($order, $actionState),
                ])
                ->throw();

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Failed to sync MAX order notification.', [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function answerCallbackQuery(?string $callbackId, string $notification): bool
    {
        $botToken = $this->botToken();

        if (! is_string($botToken) || $botToken === '' || ! is_string($callbackId) || $callbackId === '') {
            return false;
        }

        try {
            Http::withToken($botToken)
                ->timeout(10)
                ->post(self::BASE_URL.'/answers', [
                    'callback_id' => $callbackId,
                    'notification' => $notification,
                    'type' => 'callback',
                ])
                ->throw();

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Failed to answer MAX callback query.', [
                'callback_id' => $callbackId,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function botToken(): ?string
    {
        $value = SiteSetting::current()->max_bot_token ?: config('services.max.bot_token');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function ordersChatId(): ?string
    {
        $value = SiteSetting::current()->max_orders_chat_id ?: config('services.max.orders_chat_id');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function webhookSecret(): ?string
    {
        $value = SiteSetting::current()->max_webhook_secret ?: config('services.max.webhook_secret');

        return is_string($value) && $value !== '' ? $value : null;
    }

    protected function buildMessage(Order $order): string
    {
        $lines = [
            '<b>Новый заказ '.$this->escape($order->number).'</b>',
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
        $lines[] = 'Статус: '.$this->escape($order->status->getLabel());
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

    protected function buildAttachments(Order $order, ?string $actionState = null): array
    {
        if ($actionState === 'error') {
            return [
                [
                    'type' => 'inline_keyboard',
                    'payload' => [
                        'buttons' => [
                            [
                                [
                                    'type' => 'callback',
                                    'text' => '⚠️ Ошибка подтверждения',
                                    'payload' => 'error:'.$order->public_id,
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($order->status === OrderStatus::Confirmed) {
            return [
                [
                    'type' => 'inline_keyboard',
                    'payload' => [
                        'buttons' => [
                            [
                                [
                                    'type' => 'callback',
                                    'text' => '✅ Подтверждён',
                                    'payload' => 'confirmed:'.$order->public_id,
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        return [
            [
                'type' => 'inline_keyboard',
                'payload' => [
                    'buttons' => [
                        [
                            [
                                'type' => 'callback',
                                'text' => 'Заказ принят',
                                'payload' => 'confirm:'.$order->public_id,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
