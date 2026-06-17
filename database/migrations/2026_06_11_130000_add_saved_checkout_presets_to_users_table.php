<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('saved_delivery_addresses')->nullable()->after('telegram_username');
            $table->json('saved_delivery_comments')->nullable()->after('saved_delivery_addresses');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'saved_delivery_addresses',
                'saved_delivery_comments',
            ]);
        });
    }
};
