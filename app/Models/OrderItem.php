<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'order_delivery_group_id',
        'purchasable_type',
        'purchasable_id',
        'type',
        'name',
        'product_ingredients',
        'unit_price',
        'quantity',
        'total_price',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryGroup(): BelongsTo
    {
        return $this->belongsTo(OrderDeliveryGroup::class, 'order_delivery_group_id');
    }

    public function purchasable(): MorphTo
    {
        return $this->morphTo();
    }

    public function components(): HasMany
    {
        return $this->hasMany(OrderItemComponent::class);
    }
}
