<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'provider', 'payment_environment', 'provider_transaction_id', 'amount', 'currency',
        'status', 'paid_at', 'failure_reason', 'provider_data',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime', 'provider_data' => 'array'];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }

    public function scopeLiveRevenue(Builder $query): Builder
    {
        return $query
            ->where('status', 'paid')
            ->where('payment_environment', 'live')
            ->where('provider', '!=', 'demo');
    }

    /** @param array<string, mixed> $providerData */
    public static function inferEnvironment(string $provider, ?string $transactionId = null, array $providerData = []): string
    {
        if (strtolower($provider) === 'demo') {
            return 'test';
        }

        if (array_key_exists('livemode', $providerData)) {
            return filter_var($providerData['livemode'], FILTER_VALIDATE_BOOL) ? 'live' : 'test';
        }

        $declared = strtolower((string) ($providerData['environment'] ?? ''));
        if (in_array($declared, ['live', 'production'], true)) {
            return 'live';
        }
        if (in_array($declared, ['test', 'sandbox'], true)) {
            return 'test';
        }

        $reference = strtolower((string) $transactionId);
        if (preg_match('/(?:^|_)live(?:_|$)/', $reference) === 1) {
            return 'live';
        }
        if (preg_match('/(?:^|_)test(?:_|$)/', $reference) === 1) {
            return 'test';
        }

        return 'unknown';
    }
}
