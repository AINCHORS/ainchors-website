<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Product;
use App\Models\User;
use App\Services\Admin\AuditService;
use App\Services\Courses\EnrollmentService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollments,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $enrollments = Enrollment::query()
            ->select([
                'id', 'user_id', 'product_id', 'source_order_item_id', 'status',
                'progress_percent', 'enrolled_at', 'completed_at', 'expires_at',
                'created_at', 'updated_at',
            ])
            ->with([
                'user:id,full_name,email,role,status',
                'product:id,name,slug,sku,type,status',
                'sourceOrderItem:id,order_id,product_id,product_name,quantity,unit_price,line_total',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->whereHas('user', function ($userQuery) use ($search): void {
                            $userQuery
                                ->where('full_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('product', function ($productQuery) use ($search): void {
                            $productQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', (int) $request->input('user_id')))
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', (int) $request->input('product_id')))
            ->orderByDesc('enrolled_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // These select lists deliberately expose only currently usable course
        // products and active accounts for an administrator's manual grant.
        $users = User::query()
            ->select(['id', 'full_name', 'email'])
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get();
        $courses = Product::query()
            ->select(['id', 'name', 'slug', 'sku'])
            ->where('type', 'course')
            ->where('status', 'active')
            ->whereHas('courseContent', fn ($query) => $query->whereNotNull('video_url')->where('video_url', '!=', ''))
            ->orderBy('name')
            ->get();

        return view('admin.enrollments.index', compact('enrollments', 'users', 'courses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $user = User::query()->findOrFail($data['user_id']);
        $course = Product::query()->findOrFail($data['product_id']);
        $this->assertManualGrantIsSafe($user, $course);

        /** @var User $admin */
        $admin = $request->user();
        $existing = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('product_id', $course->id)
            ->first();
        $wasAlreadyActive = $existing?->status === 'active';
        $expiresAt = filled($data['expires_at'] ?? null)
            ? Carbon::parse($data['expires_at'])->endOfDay()
            : null;
        $expiryChanged = $expiresAt !== null && (! $existing?->expires_at || ! $existing->expires_at->equalTo($expiresAt));

        /** @var Enrollment $enrollment */
        $enrollment = DB::transaction(function () use ($admin, $user, $course, $existing, $wasAlreadyActive, $expiresAt, $expiryChanged): Enrollment {
            $before = $existing ? $this->auditData($existing) : [];
            $enrollment = $this->enrollments->grantManually($user, $course, $expiresAt);

            if (! $wasAlreadyActive) {
                $this->audit->record($admin, 'ENROLLMENT_GRANTED', $enrollment, $before, $this->auditData($enrollment));
            } elseif ($expiryChanged) {
                $this->audit->record($admin, 'ENROLLMENT_EXPIRY_UPDATED', $enrollment, $before, $this->auditData($enrollment));
            }

            return $enrollment;
        });

        return redirect()->route('admin.enrollments.index')
            ->with('success', ! $wasAlreadyActive
                ? 'Enrollment granted.'
                : ($expiryChanged ? 'Enrollment expiry updated.' : 'This user already has an active enrollment for the course.'));
    }

    public function revoke(Request $request, Enrollment $enrollment): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $wasRevoked = $enrollment->status === 'revoked';

        DB::transaction(function () use ($admin, $enrollment, $wasRevoked): void {
            $before = $this->auditData($enrollment);
            $this->enrollments->revoke($enrollment);

            if (! $wasRevoked) {
                $this->audit->record($admin, 'ENROLLMENT_REVOKED', $enrollment, $before, $this->auditData($enrollment));
            }
        });

        return redirect()->route('admin.enrollments.index')
            ->with('success', $wasRevoked ? 'Enrollment was already revoked.' : 'Enrollment revoked.');
    }

    private function assertManualGrantIsSafe(User $user, Product $course): void
    {
        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'user_id' => 'Only active user accounts can receive a manual enrollment.',
            ]);
        }

        if (! $course->isCourse() || $course->status !== 'active') {
            throw ValidationException::withMessages([
                'product_id' => 'Only active course products can receive manual enrollments.',
            ]);
        }

        if (! $course->courseContent()->whereNotNull('video_url')->where('video_url', '!=', '')->exists()) {
            throw ValidationException::withMessages([
                'product_id' => 'The course must have protected video metadata before it can be enrolled.',
            ]);
        }
    }

    /** @return array<string, int|string|null> */
    private function auditData(Enrollment $enrollment): array
    {
        return [
            'id' => $enrollment->id,
            'user_id' => $enrollment->user_id,
            'product_id' => $enrollment->product_id,
            'source_order_item_id' => $enrollment->source_order_item_id,
            'status' => $enrollment->status,
            'enrolled_at' => $enrollment->enrolled_at?->toAtomString(),
            'expires_at' => $enrollment->expires_at?->toAtomString(),
        ];
    }
}
