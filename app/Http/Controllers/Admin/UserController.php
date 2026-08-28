<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $users = User::query()
            ->select(['id', 'full_name', 'email', 'role', 'status', 'created_at', 'last_login_at'])
            ->withCount(['enrollments', 'orders'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')->value()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->has('status')) {
            $request->merge(['status' => 'active']);
        }

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['prohibited'],
            'status' => ['required', Rule::in(['active', 'inactive', 'blocked'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var User $admin */
        $admin = $request->user();

        /** @var User $user */
        $user = DB::transaction(function () use ($data, $admin): User {
            $user = User::query()->create([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'role' => 'user',
                'status' => $data['status'],
                'password' => Hash::make($data['password']),
            ]);

            $this->audit->record($admin, 'USER_CREATED', $user, [], $this->auditData($user));

            return $user;
        });

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User created.');
    }

    public function show(User $user): View
    {
        $user = $this->safeUserQuery()
            ->withCount(['enrollments', 'orders'])
            ->with([
                'enrollments' => fn ($query) => $query
                    ->select(['id', 'user_id', 'product_id', 'status', 'enrolled_at', 'expires_at'])
                    ->with('product:id,name,slug,type,status')
                    ->latest('enrolled_at'),
                'orders' => fn ($query) => $query
                    ->select(['id', 'order_number', 'user_id', 'status', 'currency', 'total_amount', 'placed_at', 'created_at'])
                    ->with([
                        'items' => fn ($items) => $items
                            ->select(['id', 'order_id', 'product_id', 'product_name', 'quantity', 'unit_price', 'line_total'])
                            ->with('product:id,name,slug,type,status'),
                        'payments' => fn ($payments) => $payments
                            ->select(['id', 'order_id', 'provider', 'provider_transaction_id', 'amount', 'currency', 'status', 'paid_at']),
                    ])
                    ->orderByDesc('placed_at')
                    ->limit(10),
            ])
            ->findOrFail($user->getKey());

        $recentActivity = collect();

        return view('admin.users.show', compact('user', 'recentActivity'));
    }

    public function edit(User $user): View
    {
        $user = $this->safeUserQuery()->findOrFail($user->getKey());

        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['prohibited'],
        ]);

        $this->assertAdminIdentityChangeIsSafe($user, $data['email']);

        /** @var User $admin */
        $admin = $request->user();

        DB::transaction(function () use ($admin, $user, $data): void {
            $before = $this->auditData($user);

            $user->fill($data);

            if (! $user->isDirty()) {
                return;
            }

            $user->save();
            $this->audit->record(
                $admin,
                'USER_UPDATED',
                $user,
                $before,
                $this->auditData($user),
            );
        });

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User updated.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive', 'blocked'])],
        ]);

        /** @var User $admin */
        $admin = $request->user();
        $this->assertStatusChangeIsSafe($admin, $user, $data['status']);

        DB::transaction(function () use ($admin, $user, $data): void {
            $before = $this->auditData($user);

            if ($user->status === $data['status']) {
                return;
            }

            $user->forceFill(['status' => $data['status']])->save();
            $action = in_array($data['status'], ['inactive', 'blocked'], true)
                ? 'USER_DISABLED'
                : 'USER_STATUS_CHANGED';

            $this->audit->record($admin, $action, $user, $before, $this->auditData($user));
        });

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User status updated.');
    }

    private function assertAdminIdentityChangeIsSafe(User $user, string $newEmail): void
    {
        if (! $user->isAdmin()) {
            return;
        }

        $configuredEmail = strtolower(trim((string) config('ainchors.admin.email', '')));

        if ($configuredEmail === '' || strtolower(trim($newEmail)) !== $configuredEmail) {
            throw ValidationException::withMessages([
                'email' => 'The administrator email is controlled by AINCHORS_ADMIN_EMAIL and cannot be changed here.',
            ]);
        }
    }

    private function assertStatusChangeIsSafe(User $admin, User $subject, string $newStatus): void
    {
        if ($admin->is($subject) && $newStatus !== 'active') {
            throw ValidationException::withMessages([
                'status' => 'You cannot deactivate or block your own administrator account.',
            ]);
        }

        if ($subject->isAuthorizedAdmin() && $newStatus !== 'active' && $this->activeAdminCount() <= 1) {
            throw ValidationException::withMessages([
                'status' => 'The configured administrator account must remain active.',
            ]);
        }
    }

    private function activeAdminCount(): int
    {
        $configuredEmail = strtolower(trim((string) config('ainchors.admin.email', '')));

        if ($configuredEmail === '') {
            return 0;
        }

        return User::query()
            ->where('role', 'admin')
            ->where('status', 'active')
            ->whereRaw('LOWER(email) = ?', [$configuredEmail])
            ->count();
    }

    /** @return array<string, int|string> */
    private function auditData(User $user): array
    {
        return [
            'id' => $user->id,
            'role' => $user->role,
            'status' => $user->status,
        ];
    }

    private function safeUserQuery()
    {
        return User::query()->select([
            'id', 'full_name', 'email', 'role', 'status', 'created_at',
            'email_verified_at', 'last_login_at',
        ]);
    }
}
