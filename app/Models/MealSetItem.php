<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealSetItem extends Model
{
    protected $fillable = [
        'meal_set_id',
        'product_id',
        'quantity',
        'sort_order',
    ];

    public function mealSet(): BelongsTo
    {
        return $this->belongsTo(MealSet::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
