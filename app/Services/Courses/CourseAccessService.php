<?php

namespace App\Services\Courses;

use App\Models\Enrollment;
use App\Models\Product;
use App\Models\User;

class CourseAccessService
{
    public function __construct(private readonly EnrollmentService $enrollments) {}

    public function activeEnrollment(User $user, Product $product): ?Enrollment
    {
        $this->enrollments->expireDue($user);

        return Enrollment::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('status', 'active')
            ->latest('enrolled_at')
            ->first();
    }

    public function canAccess(User $user, Product $product): bool
    {
        return $product->isCourse() && $this->activeEnrollment($user, $product) !== null;
    }
}
