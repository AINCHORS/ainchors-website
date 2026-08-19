<?php

namespace App\Services\Commerce;

use App\Exceptions\AlreadyOwnedException;
use App\Models\Order;
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
        return DB::transaction(function () use ($user, $product, $idempotencyKey): Order {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

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
            $this->payments->createDemoPayment($order);

            foreach ($targets as $course) {
                if (! $this->access->canAccess($user, $course)) {
                    $this->enrollments->grant($user, $course, $item);
                }
            }

            $order->update(['status' => 'completed']);

            return $order->fresh(['items.product', 'payments']);
        }, 3);
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
}
