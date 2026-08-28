<?php

namespace App\Services\Commerce;

use App\Exceptions\AlreadyOwnedException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Courses\CourseAccessService;
use App\Services\Courses\EnrollmentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CheckoutService
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly PaymentService $payments,
        private readonly EnrollmentService $enrollments,
        private readonly CourseAccessService $access,
        private readonly CoursePurchaseEligibilityService $eligibility,
    ) {}

    /** @return Collection<int, Product> */
    public function purchasableProducts(): Collection
    {
        return Product::query()
            ->where('status', 'active')
            ->whereIn('type', ['course', 'course_package', 'consulting', 'service'])
            ->orderBy('name')
            ->get();
    }

    public function isFullyOwned(User $user, Product $product): bool
    {
        $targets = $this->enrollmentTargets($product);

        return $targets->isNotEmpty() && $targets->every(fn (Product $course) => $this->access->canAccess($user, $course));
    }

    public function purchase(User $user, Product $product, string $idempotencyKey): Order
    {
        $order = $this->prepareOrder($user, $product, $idempotencyKey);

        if ($order->status === 'completed') {
            return $order;
        }

        return DB::transaction(function () use ($order): Order {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status === 'completed') {
                return $order->fresh(['items.product', 'payments']);
            }

            if (! $order->payments()->where('provider', 'demo')->where('status', 'paid')->exists()) {
                $this->payments->createDemoPayment($order);
            }

            return $this->completeLockedOrder(
                $order,
                'demo',
                (string) $order->payments()->where('provider', 'demo')->value('provider_transaction_id'),
                ['mode' => 'test'],
                (string) $order->total_amount,
                $order->currency,
            );
        }, 3);
    }

    public function prepareOrder(User $user, Product $product, string $idempotencyKey): Order
    {
        return DB::transaction(function () use ($user, $product, $idempotencyKey): Order {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $existing = Order::query()
                ->where('user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->with(['items.product', 'payments'])
                ->first();

            if ($existing) {
                return $existing;
            }

            $product = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            abort_unless($product->status === 'active' && in_array($product->type, ['course', 'course_package'], true), 404);
            if ($product->isCourse()) {
                $this->eligibility->assertCourseCanBePurchased($product, $user);
            }

            $targets = $this->enrollmentTargets($product);
            if ($targets->isEmpty()) {
                throw new RuntimeException('This package has no related courses.');
            }

            if ($product->isCourse() && $this->access->canAccess($user, $product)) {
                throw new AlreadyOwnedException($product);
            }

            if ($product->isPackage() && $targets->every(fn (Product $course) => $this->access->canAccess($user, $course))) {
                throw new AlreadyOwnedException($product);
            }

            [$order, $item] = $this->orders->createForProduct($user, $product, $idempotencyKey);

            return $order->fresh(['items.product', 'payments']);
        }, 3);
    }

    /** @return array{Order, Payment} */
    public function prepareStripeOrder(User $user, Product $course, string $idempotencyKey): array
    {
        return DB::transaction(function () use ($user, $course, $idempotencyKey): array {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $course = Product::query()->whereKey($course->id)->lockForUpdate()->firstOrFail();
            $this->eligibility->assertCourseCanBePurchased($course, $user);

            if ($this->access->canAccess($user, $course)) {
                throw new AlreadyOwnedException($course);
            }

            $existing = Order::query()
                ->where('user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->with(['items.product', 'payments'])
                ->first();

            if ($existing) {
                $sameCourse = $existing->items->contains(fn (OrderItem $item): bool => $item->product_id === $course->id);
                if (! $sameCourse || $existing->status !== 'awaiting_payment') {
                    throw new RuntimeException('This checkout attempt cannot be reused. Please start a new checkout.');
                }

                return [$existing, $this->payments->beginStripePayment($existing)];
            }

            $awaiting = $this->orders->awaitingStripePurchaseFor($user, $course);
            if ($awaiting) {
                $payment = $awaiting->payments
                    ->first(fn (Payment $candidate): bool => $candidate->provider === 'stripe'
                        && in_array($candidate->status, ['pending', 'processing'], true));

                if ($payment) {
                    return [$awaiting, $payment];
                }
            }

            [$order] = $this->orders->createForProduct($user, $course, $idempotencyKey);

            return [$order, $this->payments->beginStripePayment($order)];
        }, 3);
    }

    /** @param array<string, mixed> $providerData */
    public function completeHostedPayment(
        Order $order,
        string $provider,
        string $transactionId,
        array $providerData,
        string|float $amount,
        string $currency,
    ): Order {
        return DB::transaction(function () use ($order, $provider, $transactionId, $providerData, $amount, $currency): Order {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            return $this->completeLockedOrder($order, $provider, $transactionId, $providerData, $amount, $currency);
        }, 3);
    }

    public function cancelPendingPayment(Order $order, string $provider): void
    {
        DB::transaction(function () use ($order, $provider): void {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'completed') {
                $this->payments->markPendingPaymentFailed($order, $provider, 'Customer cancelled hosted checkout.');
                $order->update(['status' => 'cancelled']);
            }
        });
    }

    /** @return Collection<int, Product> */
    private function enrollmentTargets(Product $product): Collection
    {
        if ($product->isCourse()) {
            return new Collection([$product]);
        }

        if ($product->isPackage()) {
            return $product->bundleProducts()->where('products.type', 'course')->get();
        }

        return new Collection;
    }

    /** @param array<string, mixed> $providerData */
    private function completeLockedOrder(
        Order $order,
        string $provider,
        string $transactionId,
        array $providerData,
        string|float $amount,
        string $currency,
    ): Order {
        if ($transactionId === '') {
            throw new RuntimeException('The verified payment reference is missing.');
        }

        if (strtoupper($currency) !== strtoupper($order->currency)
            || number_format((float) $amount, 2, '.', '') !== number_format((float) $order->total_amount, 2, '.', '')) {
            throw new RuntimeException('Verified payment amount or currency does not match the order.');
        }

        $item = $this->validatedOrderItemForCompletion($order, $provider);
        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->where('provider', $provider)
            ->where('provider_transaction_id', $transactionId)
            ->first();

        if (! $payment && $provider !== 'stripe') {
            $payment = Payment::query()
                ->where('order_id', $order->id)
                ->where('provider', $provider)
                ->whereIn('status', ['pending', 'processing'])
                ->latest('id')
                ->first();
        }

        if ($payment
            && (strtoupper((string) $payment->currency) !== strtoupper((string) $order->currency)
                || number_format((float) $payment->amount, 2, '.', '') !== number_format((float) $order->total_amount, 2, '.', ''))) {
            throw new RuntimeException('The payment attempt does not match the order amount or currency.');
        }

        if ($order->status === 'completed') {
            if (! $payment || $payment->status !== 'paid') {
                throw new RuntimeException('The completed order does not have a matching paid payment.');
            }

            return $order->fresh(['items.product', 'payments']);
        }

        if ($provider === 'stripe' && (! $payment || ! in_array($payment->status, ['pending', 'processing'], true))) {
            throw new RuntimeException('No pending Stripe payment matches this Checkout Session.');
        }

        if ($payment) {
            $payment->update([
                'provider_transaction_id' => $transactionId,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'status' => 'paid',
                'paid_at' => now(),
                'failure_reason' => null,
                'provider_data' => $providerData,
            ]);
        } elseif ($provider === 'demo') {
            $payment = $order->payments()->create([
                'provider' => $provider,
                'provider_transaction_id' => $transactionId,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'status' => 'paid',
                'paid_at' => now(),
                'provider_data' => $providerData,
            ]);
        } else {
            throw new RuntimeException('No pending payment record matches this provider transaction.');
        }

        $user = $order->user()->firstOrFail();
        foreach ($this->enrollmentTargetsForOrderItem($item) as $course) {
            if (! $this->access->canAccess($user, $course)) {
                $this->enrollments->grant($user, $course, $item);
            }
        }

        $order->update(['status' => 'completed']);

        return $order->fresh(['items.product', 'payments']);
    }

    private function validatedOrderItemForCompletion(Order $order, string $provider): OrderItem
    {
        $items = $order->items()->with('product')->get();
        $item = $items->first();

        if (! $item) {
            throw new RuntimeException('The order has no purchasable item.');
        }

        if ($provider !== 'stripe') {
            return $item;
        }

        $courseIds = collect(data_get($item->metadata, 'course_product_ids', []))
            ->filter(fn ($id): bool => is_numeric($id))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $snapshotCurrency = strtoupper((string) data_get($item->metadata, 'currency', ''));
        $valid = $items->count() === 1
            && data_get($item->metadata, 'product_type') === 'course'
            && $snapshotCurrency === strtoupper((string) $order->currency)
            && $courseIds->count() === 1
            && $courseIds->first() === (int) $item->product_id
            && $item->product?->isCourse()
            && (int) $item->quantity === 1
            && number_format((float) $item->unit_price, 2, '.', '') === number_format((float) $order->total_amount, 2, '.', '')
            && number_format((float) $item->line_total, 2, '.', '') === number_format((float) $order->total_amount, 2, '.', '');

        if (! $valid) {
            throw new RuntimeException('The Stripe payment does not match a valid single-course order snapshot.');
        }

        return $item;
    }

    /** @return Collection<int, Product> */
    private function enrollmentTargetsForOrderItem(OrderItem $item): Collection
    {
        $courseIds = collect(data_get($item->metadata, 'course_product_ids', []))
            ->filter(fn ($id): bool => is_numeric($id))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($courseIds->isNotEmpty()) {
            return Product::query()
                ->whereIn('id', $courseIds)
                ->where('type', 'course')
                ->get();
        }

        return $this->enrollmentTargets($item->product);
    }
}
