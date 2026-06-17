<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('contact_phone')->default('+7 (999) 000-00-01')->after('phone_instruction');
            $table->string('contact_email')->default('hello@food-delivery.local')->after('contact_phone');
            $table->string('contact_telegram')->default('@food_delivery')->after('contact_email');
            $table->text('contact_address')->nullable()->after('contact_telegram');
            $table->text('contact_schedule')->nullable()->after('contact_address');
            $table->text('footer_description')->nullable()->after('contact_schedule');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->text('product_ingredients')->nullable()->after('name');
        });

        DB::table('site_settings')->update([
            'contact_address' => 'Екатеринбург, доставка по городу и ближайшим районам',
            'contact_schedule' => 'Принимаем заказы ежедневно, доставка на следующий день с 9:00 до 12:00 МСК.',
            'footer_description' => 'Готовые наборы и блюда на следующий день, понятное оформление заказа и быстрый доступ к новостям сервиса.',
        ]);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn('product_ingredients');
        });

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'contact_phone',
                'contact_email',
                'contact_telegram',
                'contact_address',
                'contact_schedule',
                'footer_description',
            ]);
        });
    }
};
