<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $fillable = [
        'user_id', 'product_id', 'source_order_item_id', 'status',
        'progress_percent', 'enrolled_at', 'completed_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'progress_percent' => 'decimal:2', 'enrolled_at' => 'datetime',
            'completed_at' => 'datetime', 'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function sourceOrderItem(): BelongsTo { return $this->belongsTo(OrderItem::class, 'source_order_item_id'); }
}
