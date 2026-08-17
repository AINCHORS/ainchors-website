<?php

namespace App\Services\Commerce;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class OrderService
{
    /** @return Collection<int, Order> */
    public function historyFor(User $user): Collection
    {
        return $user->orders()
            ->with(['items.product', 'payments'])
            ->latest()
            ->get();
    }

    public function detailsFor(User $user, string $orderNumber): ?Order
    {
        return $user->orders()
            ->with(['items.product', 'payments'])
            ->where('order_number', $orderNumber)
            ->first();
    }
}
