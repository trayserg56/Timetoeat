<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'delivery_price',
        'free_delivery_meal_set_quantity',
        'delivery_interval',
        'order_cutoff_time',
        'payment_phone',
        'payment_recipient',
        'payment_banks',
        'payment_instruction',
        'address_instruction',
        'phone_instruction',
        'contact_phone',
        'contact_email',
        'contact_telegram',
        'contact_telegram_url',
        'contact_address',
        'contact_schedule',
        'footer_description',
        'telegram_bot_token',
        'telegram_orders_chat_id',
        'telegram_webhook_secret',
        'max_bot_token',
        'max_orders_chat_id',
        'max_webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'delivery_price' => 'integer',
            'free_delivery_meal_set_quantity' => 'integer',
            'telegram_bot_token' => 'encrypted',
            'telegram_orders_chat_id' => 'encrypted',
            'telegram_webhook_secret' => 'encrypted',
            'max_bot_token' => 'encrypted',
            'max_orders_chat_id' => 'encrypted',
            'max_webhook_secret' => 'encrypted',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], static::defaults());
    }

    public static function defaults(): array
    {
        return [
            'delivery_price' => 8000,
            'free_delivery_meal_set_quantity' => 5,
            'delivery_interval' => '09:00-12:00 МСК',
            'order_cutoff_time' => '00:00',
            'payment_phone' => '8 987 87 87 004',
            'payment_recipient' => 'Кофанов И.Д.',
            'payment_banks' => 'Сбербанк, Тинькофф',
            'payment_instruction' => 'Заказ присылать в группу с чеком.',
            'address_instruction' => 'Адрес: улица, дом, подъезд, этаж, квартира, домофон, организация или офис.',
            'phone_instruction' => 'Номер телефона для связи.',
            'contact_phone' => '+7 (999) 000-00-01',
            'contact_email' => 'hello@food-delivery.local',
            'contact_telegram' => '@food_delivery',
            'contact_telegram_url' => null,
            'contact_address' => 'Екатеринбург, доставка по городу и ближайшим районам',
            'contact_schedule' => 'Принимаем заказы ежедневно, доставка на следующий день с 9:00 до 12:00 МСК.',
            'footer_description' => 'Готовые наборы и блюда на следующий день, понятное оформление заказа и быстрый доступ к новостям сервиса.',
            'telegram_bot_token' => null,
            'telegram_orders_chat_id' => null,
            'telegram_webhook_secret' => null,
            'max_bot_token' => null,
            'max_orders_chat_id' => null,
            'max_webhook_secret' => null,
        ];
    }
}
