<?php

namespace App\Services\Courses;

use App\Models\Enrollment;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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

    public function grant(User $user, Product $course, ?OrderItem $source = null, ?CarbonInterface $expiresAt = null): Enrollment
    {
        if (! $course->isCourse()) {
            throw new InvalidArgumentException('Only course products can be enrolled.');
        }

        return DB::transaction(function () use ($user, $course, $source, $expiresAt): Enrollment {
            $enrollment = Enrollment::query()->firstOrNew([
                'user_id' => $user->id,
                'product_id' => $course->id,
            ]);

            if (! $enrollment->exists || in_array($enrollment->status, ['expired', 'revoked'], true)) {
                $enrollment->fill([
                    'source_order_item_id' => $source?->id ?? $enrollment->source_order_item_id,
                    'status' => 'active',
                    'enrolled_at' => now(),
                    'completed_at' => null,
                    'expires_at' => $expiresAt,
                ])->save();
            } elseif ($expiresAt !== null && (! $enrollment->expires_at || ! $enrollment->expires_at->equalTo($expiresAt))) {
                $enrollment->forceFill(['expires_at' => $expiresAt])->save();
            }

            return $enrollment;
        });
    }

    public function grantManually(User $user, Product $course, ?CarbonInterface $expiresAt = null): Enrollment
    {
        return $this->grant($user, $course, null, $expiresAt);
    }

    public function revoke(Enrollment $enrollment): Enrollment
    {
        if ($enrollment->status !== 'revoked') {
            $enrollment->forceFill(['status' => 'revoked'])->save();
        }

        return $enrollment;
    }
}
