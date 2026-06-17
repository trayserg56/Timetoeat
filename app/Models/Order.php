<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Order extends Model
{
    protected $fillable = [
        'public_id',
        'number',
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_telegram_username',
        'source_ip',
        'source_forwarded_for',
        'source_user_agent',
        'customer_email',
        'delivery_address',
        'delivery_date',
        'delivery_interval',
        'customer_comment',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'delivery_price',
        'total',
        'receipt_path',
        'telegram_chat_id',
        'telegram_message_id',
        'max_chat_id',
        'max_message_id',
        'receipt_uploaded_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'delivery_date' => 'date',
            'telegram_message_id' => 'integer',
            'receipt_uploaded_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function deliveryGroups(): HasMany
    {
        return $this->hasMany(OrderDeliveryGroup::class)->orderBy('sort_order')->orderBy('id');
    }

    public function receiptUrl(): ?string
    {
        return $this->receipt_path ? route('admin.orders.receipt', $this) : null;
    }

    public function receiptAbsolutePath(): ?string
    {
        if (! $this->receipt_path) {
            return null;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($this->receipt_path)) {
                return Storage::disk($disk)->path($this->receipt_path);
            }
        }

        return null;
    }

    public function receiptMimeType(): ?string
    {
        if (! $this->receipt_path) {
            return null;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($this->receipt_path)) {
                return Storage::disk($disk)->mimeType($this->receipt_path);
            }
        }

        return null;
    }
}
