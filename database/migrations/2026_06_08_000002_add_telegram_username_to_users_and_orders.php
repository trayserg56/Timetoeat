<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('telegram_username', 64)->nullable()->after('phone');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('customer_telegram_username', 64)->nullable()->after('customer_phone');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('customer_telegram_username');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('telegram_username');
        });
    }
};
