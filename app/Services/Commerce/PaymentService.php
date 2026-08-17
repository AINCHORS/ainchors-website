<?php

namespace App\Services\Commerce;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

class PaymentService
{
    /** @return Collection<int, Payment> */
    public function historyFor(Order $order): Collection
    {
        return $order->payments()->latest()->get();
    }
}
