<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->json('menu_dates')->nullable()->after('menu_date');
        });

        Schema::table('meal_sets', function (Blueprint $table): void {
            $table->json('menu_dates')->nullable()->after('menu_date');
        });

        DB::table('products')
            ->whereNotNull('menu_date')
            ->orderBy('id')
            ->eachById(function ($product): void {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'menu_dates' => json_encode([$product->menu_date], JSON_THROW_ON_ERROR),
                    ]);
            });

        DB::table('meal_sets')
            ->whereNotNull('menu_date')
            ->orderBy('id')
            ->eachById(function ($mealSet): void {
                DB::table('meal_sets')
                    ->where('id', $mealSet->id)
                    ->update([
                        'menu_dates' => json_encode([$mealSet->menu_date], JSON_THROW_ON_ERROR),
                    ]);
            });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('menu_date');
        });

        Schema::table('meal_sets', function (Blueprint $table): void {
            $table->dropColumn('menu_date');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->date('menu_date')->nullable()->after('weight_grams');
        });

        Schema::table('meal_sets', function (Blueprint $table): void {
            $table->date('menu_date')->nullable()->after('image_path');
        });

        DB::table('products')
            ->whereNotNull('menu_dates')
            ->orderBy('id')
            ->eachById(function ($product): void {
                $dates = json_decode($product->menu_dates, true);

                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['menu_date' => is_array($dates[0] ?? null) ? ($dates[0]['date'] ?? null) : ($dates[0] ?? null)]);
            });

        DB::table('meal_sets')
            ->whereNotNull('menu_dates')
            ->orderBy('id')
            ->eachById(function ($mealSet): void {
                $dates = json_decode($mealSet->menu_dates, true);

                DB::table('meal_sets')
                    ->where('id', $mealSet->id)
                    ->update(['menu_date' => is_array($dates[0] ?? null) ? ($dates[0]['date'] ?? null) : ($dates[0] ?? null)]);
            });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('menu_dates');
        });

        Schema::table('meal_sets', function (Blueprint $table): void {
            $table->dropColumn('menu_dates');
        });
    }
};
