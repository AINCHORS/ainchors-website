<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'quantity', 'unit_price',
        'line_total', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer', 'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2', 'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'source_order_item_id');
    }

    public function serviceEngagements(): HasMany
    {
        return $this->hasMany(ServiceEngagement::class);
    }
}
