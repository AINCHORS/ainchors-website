<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'provider', 'provider_transaction_id', 'amount', 'currency',
        'status', 'paid_at', 'failure_reason', 'provider_data',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime', 'provider_data' => 'array'];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
