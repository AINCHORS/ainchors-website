@extends('layouts.admin')

@section('title', $user->full_name.' | AINCHORS Admin')

@section('content')
    @php
        $enrollments = $user->relationLoaded('enrollments') ? $user->getRelation('enrollments') : collect();
        $orders = $user->relationLoaded('orders') ? $user->getRelation('orders') : collect();
        $activityEvents = $recentActivity ?? collect();
    @endphp
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ainchors-green transition hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>All users</a><div class="mt-4 flex flex-wrap items-center gap-3"><h1 class="font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">{{ $user->full_name }}</h1>@include('admin.partials.status-badge', ['status' => $user->status])</div><p class="mt-2 text-sm text-ainchors-grey-dark">{{ $user->email }}</p></div>
        <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center justify-center rounded-ainchors-button bg-ainchors-green px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Edit user</a>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
        <section aria-labelledby="account-details-heading" class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm"><h2 id="account-details-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Account details</h2><dl class="mt-5 divide-y divide-ainchors-navy/8 text-sm"><div class="flex justify-between gap-5 py-3"><dt class="font-semibold text-ainchors-grey-dark">Role</dt><dd class="text-right text-ainchors-navy">{{ str($user->role)->headline() }}</dd></div><div class="flex justify-between gap-5 py-3"><dt class="font-semibold text-ainchors-grey-dark">Registered</dt><dd class="text-right text-ainchors-navy">{{ $user->created_at?->format('j M Y, H:i') ?? '—' }}</dd></div><div class="flex justify-between gap-5 py-3"><dt class="font-semibold text-ainchors-grey-dark">Last sign-in</dt><dd class="text-right text-ainchors-navy">{{ $user->last_login_at?->format('j M Y, H:i') ?? 'Not recorded' }}</dd></div></dl></section>

        <section aria-labelledby="enrollments-heading" class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm"><div class="flex items-center justify-between gap-4"><h2 id="enrollments-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Enrollments</h2><a href="{{ route('admin.enrollments.index', ['q' => $user->email]) }}" class="text-sm font-semibold text-ainchors-green hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">Manage</a></div><div class="mt-5 divide-y divide-ainchors-navy/8">@forelse ($enrollments as $enrollment)<div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between"><p class="font-semibold text-ainchors-navy">{{ $enrollment->product?->name ?? 'Course unavailable' }}</p>@include('admin.partials.status-badge', ['status' => $enrollment->status])</div>@empty<p class="py-5 text-sm text-ainchors-grey-dark">This account has no enrollments.</p>@endforelse</div></section>
    </div>

    @if (! $user->isAdmin())
        <section aria-labelledby="reset-password-heading" class="mt-6 rounded-ainchors-card border border-amber-300/70 bg-white p-6 shadow-sm">
            <div class="max-w-2xl">
                <h2 id="reset-password-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Reset Password</h2>
                <p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">Use this only when the user cannot access the normal email password reset. The password entered here is temporary, and the user will be required to change it after signing in.</p>

                @if ($user->must_change_password)
                    <p role="status" class="mt-4 rounded-ainchors-button border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">This account already has a temporary password pending. Setting another one will replace it.</p>
                @endif

                <form method="POST" action="{{ route('admin.users.password.reset', $user) }}" class="mt-6 space-y-5">
                    @csrf
                    @include('auth.partials.password-field', [
                        'id' => 'admin-temporary-password',
                        'name' => 'password',
                        'label' => 'Temporary Password',
                        'autocomplete' => 'new-password',
                    ])
                    @include('auth.partials.password-field', [
                        'id' => 'admin-temporary-password-confirmation',
                        'name' => 'password_confirmation',
                        'label' => 'Confirm Temporary Password',
                        'autocomplete' => 'new-password',
                        'errorName' => 'password_confirmation',
                    ])
                    <x-button variant="primary" type="submit">Reset User Password</x-button>
                </form>
            </div>
        </section>
    @endif

    <section aria-labelledby="orders-heading" class="mt-6 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm"><div class="flex items-center justify-between gap-4"><h2 id="orders-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Orders</h2><a href="{{ route('admin.orders.index', ['q' => $user->email]) }}" class="text-sm font-semibold text-ainchors-green hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">View order records</a></div><div class="mt-5 overflow-x-auto"><table class="w-full min-w-[38rem] text-left text-sm"><caption class="sr-only">Orders for {{ $user->full_name }}</caption><thead class="border-b border-ainchors-navy/10 text-xs uppercase tracking-wide text-ainchors-grey-dark"><tr><th scope="col" class="pb-3 pr-5">Order</th><th scope="col" class="pb-3 pr-5">Status</th><th scope="col" class="pb-3 pr-5 text-right">Total</th><th scope="col" class="pb-3 text-right">Action</th></tr></thead><tbody class="divide-y divide-ainchors-navy/8">@forelse ($orders as $order)<tr><td class="py-3 pr-5 font-semibold text-ainchors-navy">{{ $order->order_number }}</td><td class="py-3 pr-5">@include('admin.partials.status-badge', ['status' => $order->status])</td><td class="py-3 pr-5 text-right font-semibold text-ainchors-navy">{{ $order->currency }} {{ number_format((float) $order->total_amount, 2) }}</td><td class="py-3 text-right"><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-ainchors-green hover:text-ainchors-navy">Inspect</a></td></tr>@empty<tr><td colspan="4" class="py-6 text-center text-ainchors-grey-dark">This account has no orders.</td></tr>@endforelse</tbody></table></div></section>

    <section aria-labelledby="activity-heading" class="mt-6 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm"><h2 id="activity-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Recent activity</h2><p class="mt-1 text-sm text-ainchors-grey-dark">First-party activity details will become available with the Analytics phase.</p>@if ($activityEvents->isNotEmpty())<ul class="mt-5 divide-y divide-ainchors-navy/8">@foreach ($activityEvents as $event)<li class="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between"><span class="font-semibold text-ainchors-navy">{{ str($event->event_name ?? 'Activity')->headline() }}</span><time class="text-sm text-ainchors-grey-dark">{{ $event->created_at?->format('j M Y, H:i') }}</time></li>@endforeach</ul>@else<p class="mt-5 rounded-ainchors-button bg-slate-50 px-4 py-3 text-sm text-ainchors-grey-dark">No first-party activity events are available for this account yet.</p>@endif</section>
@endsection
