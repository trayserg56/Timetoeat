<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'ingredients',
        'price',
        'image_path',
        'weight_grams',
        'menu_dates',
        'is_active',
        'is_available',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_available' => 'boolean',
        ];
    }

    protected function menuDates(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): array => collect(is_string($value) ? json_decode($value, true) : $value)
                ->map(fn ($date): array => is_array($date) ? $date : ['date' => $date])
                ->values()
                ->all(),
            set: fn (?array $value): string => json_encode(
                collect($value)
                    ->map(fn ($date) => is_array($date) ? ($date['date'] ?? null) : $date)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
                JSON_THROW_ON_ERROR,
            ),
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function mealSetItems(): HasMany
    {
        return $this->hasMany(MealSetItem::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CatalogTag::class)->orderBy('sort_order');
    }

    public function scopeAvailableOn(Builder $query, string $date): Builder
    {
        return $query->where(function (Builder $query) use ($date): void {
            $query
                ->whereNull('menu_dates')
                ->orWhereJsonLength('menu_dates', 0)
                ->orWhereJsonContains('menu_dates', $date);
        });
    }
}
