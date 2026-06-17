<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('catalog_tag_product', function (Blueprint $table) {
            $table->foreignId('catalog_tag_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->primary(['catalog_tag_id', 'product_id']);
        });

        Schema::create('catalog_tag_meal_set', function (Blueprint $table) {
            $table->foreignId('catalog_tag_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meal_set_id')->constrained()->cascadeOnDelete();

            $table->primary(['catalog_tag_id', 'meal_set_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_tag_meal_set');
        Schema::dropIfExists('catalog_tag_product');
        Schema::dropIfExists('catalog_tags');
    }
};
