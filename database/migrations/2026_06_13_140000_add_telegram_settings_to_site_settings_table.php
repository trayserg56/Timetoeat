<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('telegram_bot_token')->nullable()->after('footer_description');
            $table->string('telegram_orders_chat_id')->nullable()->after('telegram_bot_token');
            $table->string('telegram_webhook_secret')->nullable()->after('telegram_orders_chat_id');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'telegram_bot_token',
                'telegram_orders_chat_id',
                'telegram_webhook_secret',
            ]);
        });
    }
};
