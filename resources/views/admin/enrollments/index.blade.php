@extends('layouts.admin')

@section('title', 'Enrollments | AINCHORS Admin')

@section('content')
    <div class="mb-8">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">Learning access</p>
        <h1 class="mt-2 font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">Enrollments</h1>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-ainchors-grey-dark">Grant and revoke course access without creating duplicate enrollment records. A reason is required for every manual access change and is retained in the audit trail.</p>
    </div>

    <details class="mb-6 rounded-ainchors-card border border-ainchors-navy/10 bg-white shadow-sm" @if($errors->hasAny(['user_id','product_id','expires_at','reason'])) open @endif>
        <summary class="cursor-pointer list-none px-5 py-4 font-semibold text-ainchors-navy marker:hidden focus:outline-none focus:ring-2 focus:ring-inset focus:ring-ainchors-green"><span class="flex items-center justify-between gap-4">Grant enrollment<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg></span></summary>
        <form method="POST" action="{{ route('admin.enrollments.store') }}" class="grid gap-5 border-t border-ainchors-navy/10 p-5 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_12rem_minmax(14rem,1fr)_auto] lg:items-end">
            @csrf
            <div>
                <label for="enrollment-user" class="block text-sm font-semibold text-ainchors-navy">User</label>
                <select id="enrollment-user" name="user_id" required class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25"><option value="">Select a user</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>{{ $user->full_name }} — {{ $user->email }}</option>@endforeach</select>
                @error('user_id')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="enrollment-course" class="block text-sm font-semibold text-ainchors-navy">Course</label>
                <select id="enrollment-course" name="product_id" required class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25"><option value="">Select a course</option>@foreach ($courses as $course)<option value="{{ $course->id }}" @selected((string) old('product_id') === (string) $course->id)>{{ $course->name }}</option>@endforeach</select>
                @error('product_id')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="enrollment-expires" class="block text-sm font-semibold text-ainchors-navy">Expires on <span class="font-normal text-ainchors-grey-dark">(optional)</span></label>
                <input id="enrollment-expires" name="expires_at" type="date" value="{{ old('expires_at') }}" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 px-3.5 py-2.5 text-sm text-ainchors-navy outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                @error('expires_at')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="enrollment-reason" class="block text-sm font-semibold text-ainchors-navy">Reason</label>
                <input id="enrollment-reason" name="reason" type="text" maxlength="500" required value="{{ old('reason') }}" placeholder="e.g. Corporate training entitlement" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 px-3.5 py-2.5 text-sm text-ainchors-navy outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                @error('reason')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="rounded-ainchors-button bg-ainchors-green px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Grant access</button>
        </form>
    </details>

    <form method="GET" action="{{ route('admin.enrollments.index') }}" class="mb-6 grid gap-3 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_10rem_auto] sm:items-end">
        <div><label for="enrollments-search" class="block text-sm font-semibold text-ainchors-navy">Search</label><input id="enrollments-search" name="q" type="search" value="{{ request('q') }}" placeholder="User, email or course" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 px-3.5 py-2.5 text-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25"></div>
        <div><label for="enrollments-status" class="block text-sm font-semibold text-ainchors-navy">Status</label><select id="enrollments-status" name="status" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25"><option value="">All statuses</option>@foreach (['active', 'completed', 'expired', 'revoked'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>@endforeach</select></div>
        <div class="flex gap-2"><button type="submit" class="rounded-ainchors-button bg-ainchors-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Filter</button><a href="{{ route('admin.enrollments.index') }}" class="rounded-ainchors-button border border-ainchors-navy/15 px-4 py-2.5 text-sm font-semibold text-ainchors-grey-dark transition hover:border-ainchors-green hover:text-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green">Reset</a></div>
    </form>

    <section aria-labelledby="enrollments-table-heading" class="overflow-hidden rounded-ainchors-card border border-ainchors-navy/10 bg-white shadow-sm">
        <h2 id="enrollments-table-heading" class="sr-only">Enrollment records</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[76rem] text-left text-sm">
                <thead class="border-b border-ainchors-navy/10 bg-slate-50 text-xs uppercase tracking-wide text-ainchors-grey-dark"><tr><th class="px-5 py-3.5 font-bold">User</th><th class="px-5 py-3.5 font-bold">Course</th><th class="px-5 py-3.5 font-bold">Progress</th><th class="px-5 py-3.5 font-bold">Status</th><th class="px-5 py-3.5 font-bold">Expires</th><th class="px-5 py-3.5 font-bold">Revoke reason</th><th class="px-5 py-3.5 text-right font-bold">Action</th></tr></thead>
                <tbody class="divide-y divide-ainchors-navy/8">
                    @forelse ($enrollments as $enrollment)
                        <tr>
                            <td class="px-5 py-4"><p class="font-semibold text-ainchors-navy">{{ $enrollment->user?->full_name ?? 'User unavailable' }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ $enrollment->user?->email ?? '—' }}</p></td>
                            <td class="px-5 py-4 text-ainchors-navy">{{ $enrollment->product?->name ?? 'Course unavailable' }}</td>
                            <td class="px-5 py-4 text-ainchors-grey-dark">{{ number_format((float) $enrollment->progress_percent, 0) }}%</td>
                            <td class="px-5 py-4">@include('admin.partials.status-badge', ['status' => $enrollment->status])</td>
                            <td class="px-5 py-4 text-ainchors-grey-dark">{{ $enrollment->expires_at?->format('j M Y') ?? 'No expiry' }}</td>
                            @if ($enrollment->status !== 'revoked')
                                <td class="px-5 py-4"><label class="sr-only" for="revoke-reason-{{ $enrollment->id }}">Reason for revoking enrollment</label><input id="revoke-reason-{{ $enrollment->id }}" name="reason" form="revoke-enrollment-{{ $enrollment->id }}" type="text" maxlength="500" required placeholder="Reason required" class="w-52 rounded-ainchors-button border border-ainchors-grey-light/45 px-2.5 py-1.5 text-xs text-ainchors-navy focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25"></td>
                                <td class="px-5 py-4 text-right"><form id="revoke-enrollment-{{ $enrollment->id }}" method="POST" action="{{ route('admin.enrollments.revoke', $enrollment) }}" class="inline">@csrf @method('PATCH')<button type="submit" class="font-semibold text-red-700 transition hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-500">Revoke<span class="sr-only"> {{ $enrollment->product?->name ?? 'enrollment' }} for {{ $enrollment->user?->full_name ?? 'user' }}</span></button></form></td>
                            @else
                                <td class="px-5 py-4 text-ainchors-grey-light">—</td><td class="px-5 py-4 text-right"><span class="text-xs font-semibold text-ainchors-grey-light">Already revoked</span></td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center"><p class="font-semibold text-ainchors-navy">No enrollments match these filters.</p><p class="mt-1 text-sm text-ainchors-grey-dark">Grant a course enrollment to begin.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-6">{{ $enrollments->onEachSide(1)->links() }}</div>
@endsection
