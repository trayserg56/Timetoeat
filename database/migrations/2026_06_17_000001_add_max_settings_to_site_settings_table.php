<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->text('max_bot_token')->nullable()->after('telegram_webhook_secret');
            $table->text('max_orders_chat_id')->nullable()->after('max_bot_token');
            $table->text('max_webhook_secret')->nullable()->after('max_orders_chat_id');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['max_bot_token', 'max_orders_chat_id', 'max_webhook_secret']);
        });
    }
};
