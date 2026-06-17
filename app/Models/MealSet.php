<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MealSet extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'image_path',
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

    public function items(): HasMany
    {
        return $this->hasMany(MealSetItem::class)->orderBy('sort_order');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CatalogTag::class)->orderBy('sort_order');
    }

    public function scopeAvailableOn(Builder $query, string $date): Builder
    {
        return $query->whereJsonContains('menu_dates', $date);
    }
}
