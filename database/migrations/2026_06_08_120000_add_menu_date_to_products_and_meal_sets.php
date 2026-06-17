<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->date('menu_date')->nullable()->after('weight_grams');
        });

        Schema::table('meal_sets', function (Blueprint $table) {
            $table->date('menu_date')->nullable()->after('image_path');
        });

        $tomorrow = now('Europe/Moscow')->addDay()->toDateString();

        DB::table('products')->whereNull('menu_date')->update(['menu_date' => $tomorrow]);
        DB::table('meal_sets')->whereNull('menu_date')->update(['menu_date' => $tomorrow]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('menu_date');
        });

        Schema::table('meal_sets', function (Blueprint $table) {
            $table->dropColumn('menu_date');
        });
    }
};
