@extends('layouts.admin')

@section('title', 'Users | AINCHORS Admin')

@section('content')
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">Account management</p>
            <h1 class="mt-2 font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">Users</h1>
            <p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">Manage account details, access roles and account status. Passwords are never displayed here.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center rounded-ainchors-button bg-ainchors-green px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Add user</a>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-6 grid gap-3 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_10rem_10rem_auto] sm:items-end">
        <div>
            <label for="users-search" class="block text-sm font-semibold text-ainchors-navy">Search</label>
            <input id="users-search" name="q" type="search" value="{{ request('q') }}" placeholder="Name or email" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 px-3.5 py-2.5 text-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        </div>
        <div>
            <label for="users-role" class="block text-sm font-semibold text-ainchors-navy">Role</label>
            <select id="users-role" name="role" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                <option value="">All roles</option><option value="admin" @selected(request('role') === 'admin')>Administrator</option><option value="user" @selected(request('role') === 'user')>User</option>
            </select>
        </div>
        <div>
            <label for="users-status" class="block text-sm font-semibold text-ainchors-navy">Status</label>
            <select id="users-status" name="status" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                <option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="flex gap-2"><button type="submit" class="rounded-ainchors-button bg-ainchors-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Filter</button><a href="{{ route('admin.users.index') }}" class="rounded-ainchors-button border border-ainchors-navy/15 px-4 py-2.5 text-sm font-semibold text-ainchors-grey-dark transition hover:border-ainchors-green hover:text-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green">Reset</a></div>
    </form>

    <section aria-labelledby="users-table-heading" class="overflow-hidden rounded-ainchors-card border border-ainchors-navy/10 bg-white shadow-sm">
        <h2 id="users-table-heading" class="sr-only">User records</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[48rem] text-left text-sm">
                <caption class="sr-only">AINCHORS user records</caption>
                <thead class="border-b border-ainchors-navy/10 bg-slate-50 text-xs uppercase tracking-wide text-ainchors-grey-dark"><tr><th scope="col" class="px-5 py-3.5 font-bold">User</th><th scope="col" class="px-5 py-3.5 font-bold">Role</th><th scope="col" class="px-5 py-3.5 font-bold">Status</th><th scope="col" class="px-5 py-3.5 font-bold">Registered</th><th scope="col" class="px-5 py-3.5 text-right font-bold">Actions</th></tr></thead>
                <tbody class="divide-y divide-ainchors-navy/8">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-5 py-4"><p class="font-semibold text-ainchors-navy">{{ $user->full_name }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ $user->email }}</p></td>
                            <td class="px-5 py-4 text-ainchors-grey-dark">{{ str($user->role)->headline() }}</td>
                            <td class="px-5 py-4">@include('admin.partials.status-badge', ['status' => $user->status])</td>
                            <td class="px-5 py-4 text-ainchors-grey-dark">{{ $user->created_at?->format('j M Y') ?? '—' }}</td>
                            <td class="px-5 py-4"><div class="flex justify-end gap-4"><a href="{{ route('admin.users.show', $user) }}" class="font-semibold text-ainchors-green hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">View<span class="sr-only"> {{ $user->full_name }}</span></a><a href="{{ route('admin.users.edit', $user) }}" class="font-semibold text-ainchors-green hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">Edit<span class="sr-only"> {{ $user->full_name }}</span></a></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center"><p class="font-semibold text-ainchors-navy">No users match these filters.</p><p class="mt-1 text-sm text-ainchors-grey-dark">Try a different search or create an account.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if (method_exists($users, 'links'))<div class="mt-6">{{ $users->onEachSide(1)->links() }}</div>@endif
@endsection
