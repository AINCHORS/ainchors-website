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
            ->where(function ($query): void {
                $query->whereIn('status', ['completed', 'cancelled'])
                    ->orWhereHas('payments', fn ($payments) => $payments->where('status', 'failed'));
            })
            ->with([
                'items' => fn ($query) => $query->select([
                    'id', 'order_id', 'product_id', 'product_name', 'quantity',
                    'unit_price', 'line_total', 'metadata', 'created_at',
                ]),
                'payments' => fn ($query) => $query->select([
                    'id', 'order_id', 'provider', 'provider_transaction_id', 'payment_environment', 'amount', 'currency',
                    'status', 'paid_at', 'created_at',
                ])->latest('id'),
                'externalInvoices' => fn ($query) => $query->select([
                    'id', 'order_id', 'provider', 'external_reference',
                    'invoice_number', 'invoice_url', 'status', 'issued_at',
                ]),
            ])
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

    public function awaitingStripePurchaseFor(User $user, Product $course): ?Order
    {
        return $this->awaitingHostedPurchaseFor($user, $course, 'stripe');
    }

    public function awaitingHostedPurchaseFor(User $user, Product $product, string $provider): ?Order
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->where('status', 'awaiting_payment')
            ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
            ->whereHas('payments', fn ($query) => $query
                ->where('provider', $provider)
                ->whereIn('status', ['pending', 'processing']))
            ->with(['items.product', 'payments' => fn ($query) => $query->latest('id')])
            ->latest('id')
            ->first();
    }

    /** @return array{Order, OrderItem} */
    public function createForProduct(User $user, Product $product, string $idempotencyKey): array
    {
        $currency = strtoupper((string) $product->currency);
        $courseProductIds = $product->isCourse()
            ? [$product->id]
            : $product->bundleProducts()->where('products.type', 'course')->pluck('products.id')->all();

        $order = Order::query()->create([
            'order_number' => 'AIN-'.now()->format('YmdHis').'-'.Str::upper(Str::random(8)),
            'idempotency_key' => $idempotencyKey,
            'user_id' => $user->id,
            'status' => 'awaiting_payment',
            'currency' => $currency,
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
            'metadata' => [
                'product_type' => $product->type,
                'sku' => $product->sku,
                'currency' => $currency,
                'course_product_ids' => $courseProductIds,
            ],
        ]);

        return [$order, $item];
    }
}
