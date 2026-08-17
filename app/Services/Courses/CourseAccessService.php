<?php

namespace App\Services\Courses;

use App\Models\Enrollment;
use App\Models\Product;
use App\Models\User;

class CourseAccessService
{
    public function activeEnrollment(User $user, Product $product): ?Enrollment
    {
        return Enrollment::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest('enrolled_at')
            ->first();
    }

    public function canAccess(User $user, Product $product): bool
    {
        return $this->activeEnrollment($user, $product) !== null;
    }
}
