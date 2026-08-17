<?php

namespace App\Services\Courses;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentService
{
    /** @return Collection<int, Enrollment> */
    public function activeFor(User $user): Collection
    {
        return $user->enrollments()
            ->with(['product.courseContent'])
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderByDesc('enrolled_at')
            ->get();
    }
}
