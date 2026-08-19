<?php

namespace App\Services\Commerce;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

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

    /** @return array{Order, OrderItem} */
    public function createForProduct(User $user, Product $product, string $idempotencyKey): array
    {
        $order = Order::query()->create([
            'order_number' => 'AIN-'.now()->format('YmdHis').'-'.Str::upper(Str::random(8)),
            'idempotency_key' => $idempotencyKey,
            'user_id' => $user->id,
            'status' => 'awaiting_payment',
            'currency' => $product->currency,
            'subtotal' => $product->price,
            'discount_total' => 0,
            'tax_total' => 0,
            'total_amount' => $product->price,
            'placed_at' => now(),
        ]);

        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => $product->price,
            'line_total' => $product->price,
            'metadata' => ['product_type' => $product->type],
        ]);

        return [$order, $item];
    }
}
