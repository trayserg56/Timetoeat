<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 32);
            $table->string('customer_email')->nullable();
            $table->text('delivery_address');
            $table->date('delivery_date')->nullable();
            $table->string('delivery_interval')->nullable();
            $table->text('customer_comment')->nullable();
            $table->string('status')->default('new')->index();
            $table->string('payment_status')->default('awaiting_receipt')->index();
            $table->string('payment_method')->default('bank_transfer');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('delivery_price')->default(0);
            $table->unsignedBigInteger('total');
            $table->string('receipt_path')->nullable();
            $table->timestamp('receipt_uploaded_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
