<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->text('telegram_bot_token')->nullable()->change();
            $table->text('telegram_orders_chat_id')->nullable()->change();
            $table->text('telegram_webhook_secret')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('telegram_bot_token')->nullable()->change();
            $table->string('telegram_orders_chat_id')->nullable()->change();
            $table->string('telegram_webhook_secret')->nullable()->change();
        });
    }
};
