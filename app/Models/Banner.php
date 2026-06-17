<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banner extends Model
{
    protected $fillable = [
        'meal_set_id',
        'banner_tag_id',
        'title',
        'description',
        'image_path',
        'image_url',
        'link_url',
        'menu_date',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'menu_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function mealSet(): BelongsTo
    {
        return $this->belongsTo(MealSet::class);
    }

    public function bannerTag(): BelongsTo
    {
        return $this->belongsTo(BannerTag::class);
    }

    public function scopeAvailableOn(Builder $query, string $date): Builder
    {
        return $query->where(function (Builder $query) use ($date): void {
            $query
                ->whereNull('menu_date')
                ->orWhereDate('menu_date', $date);
        });
    }
}
