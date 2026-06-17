<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('source_ip', 45)->nullable()->after('customer_telegram_username');
            $table->text('source_forwarded_for')->nullable()->after('source_ip');
            $table->text('source_user_agent')->nullable()->after('source_forwarded_for');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'source_ip',
                'source_forwarded_for',
                'source_user_agent',
            ]);
        });
    }
};
