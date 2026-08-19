<?php

namespace App\Services\Commerce;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class PaymentService
{
    /** @return Collection<int, Payment> */
    public function historyFor(Order $order): Collection
    {
        return $order->payments()->latest()->get();
    }

    public function createDemoPayment(Order $order): Payment
    {
        return $order->payments()->create([
            'provider' => 'demo',
            'provider_transaction_id' => 'DEMO-'.now()->format('Ymd').'-'.Str::upper(Str::random(10)),
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'status' => 'paid',
            'paid_at' => now(),
            'provider_data' => ['mode' => 'test'],
        ]);
    }
}
