<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalInvoice extends Model
{
    protected $fillable = [
        'order_id', 'provider', 'external_reference', 'invoice_number',
        'invoice_url', 'status', 'issued_at', 'email_claimed_at', 'email_sent_at',
    ];

    protected $hidden = ['invoice_url'];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'email_claimed_at' => 'datetime',
            'email_sent_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
