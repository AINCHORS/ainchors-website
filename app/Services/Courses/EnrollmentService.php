<?php

namespace App\Services\Courses;

use App\Models\Enrollment;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentService
{
    /** @return Collection<int, Enrollment> */
    public function activeFor(User $user): Collection
    {
        return $user->enrollments()
            ->with(['product.courseContent'])
            ->whereIn('status', ['active', 'completed'])
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderByDesc('enrolled_at')
            ->get();
    }

    public function grant(User $user, Product $course, OrderItem $source): Enrollment
    {
        $enrollment = Enrollment::query()->firstOrNew([
            'user_id' => $user->id,
            'product_id' => $course->id,
        ]);

        if (! $enrollment->exists || in_array($enrollment->status, ['expired', 'revoked'], true)) {
            $enrollment->fill([
                'source_order_item_id' => $source->id,
                'status' => 'active',
                'enrolled_at' => now(),
                'expires_at' => null,
            ])->save();
        }

        return $enrollment;
    }
}
