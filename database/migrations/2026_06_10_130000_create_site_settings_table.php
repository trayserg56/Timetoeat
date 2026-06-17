<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_price')->default(8000);
            $table->unsignedInteger('free_delivery_meal_set_quantity')->default(5);
            $table->string('delivery_interval')->default('09:00-12:00 МСК');
            $table->string('order_cutoff_time')->default('00:00');
            $table->string('payment_phone');
            $table->string('payment_recipient');
            $table->string('payment_banks');
            $table->text('payment_instruction');
            $table->text('address_instruction');
            $table->text('phone_instruction');
            $table->timestamps();
        });

        DB::table('site_settings')->insert([
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
