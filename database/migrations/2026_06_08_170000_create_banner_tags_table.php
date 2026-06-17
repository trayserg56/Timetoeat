<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banner_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->foreignId('banner_tag_id')->nullable()->after('meal_set_id')->constrained('banner_tags')->nullOnDelete();
        });

        $now = now();
        $tagId = DB::table('banner_tags')->insertGetId([
            'name' => 'Меню на завтра',
            'slug' => Str::slug('Меню на завтра'),
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('banners')
            ->whereNull('banner_tag_id')
            ->update(['banner_tag_id' => $tagId]);
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('banner_tag_id');
        });

        Schema::dropIfExists('banner_tags');
    }
};
