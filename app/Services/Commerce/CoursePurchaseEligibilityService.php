<?php

namespace App\Services\Commerce;

use App\Models\Product;
use App\Models\User;
use App\Services\Products\ProductReadinessService;
use RuntimeException;

class CoursePurchaseEligibilityService
{
    public function __construct(private readonly ProductReadinessService $readiness) {}

    public function courseCanBePurchased(Product $course): bool
    {
        if (! $course->isCourse()
            || $course->status !== 'active'
            || $course->billing_type !== 'one_time'
            || $course->price === null
            || (float) $course->price <= 0
            || ! array_key_exists(strtoupper((string) $course->currency), config('commerce.supported_currencies', []))) {
            return false;
        }

        return (bool) data_get($this->readiness->inspect($course), 'ready', false);
    }

    public function customerCanPurchase(User $user): bool
    {
        return $user->status === 'active';
    }

    public function assertCourseCanBePurchased(Product $course, User $user): void
    {
        if (! $this->customerCanPurchase($user)) {
            throw new RuntimeException('This account is not permitted to start a purchase.');
        }

        if (! $this->courseCanBePurchased($course)) {
            throw new RuntimeException('This course is not ready for purchase.');
        }
    }
}
