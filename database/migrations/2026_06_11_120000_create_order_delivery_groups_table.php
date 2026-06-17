<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_delivery_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->text('delivery_address');
            $table->text('customer_comment')->nullable();
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('delivery_price')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreignId('order_delivery_group_id')
                ->nullable()
                ->after('order_id')
                ->constrained('order_delivery_groups')
                ->nullOnDelete();
        });

        $orders = DB::table('orders')->orderBy('id')->get();

        foreach ($orders as $order) {
            $groupId = DB::table('order_delivery_groups')->insertGetId([
                'order_id' => $order->id,
                'delivery_address' => $order->delivery_address,
                'customer_comment' => $order->customer_comment,
                'subtotal' => $order->subtotal,
                'delivery_price' => $order->delivery_price,
                'total' => $order->total,
                'sort_order' => 0,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ]);

            DB::table('order_items')
                ->where('order_id', $order->id)
                ->update(['order_delivery_group_id' => $groupId]);
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('order_delivery_group_id');
        });

        Schema::dropIfExists('order_delivery_groups');
    }
};
