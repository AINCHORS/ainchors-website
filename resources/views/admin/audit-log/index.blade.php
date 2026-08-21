@extends('layouts.admin')

@section('title', 'Audit Log | AINCHORS Admin')

@section('content')
    <div class="mb-8">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">System</p>
        <h1 class="mt-2 font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">Audit log</h1>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-ainchors-grey-dark">Read-only history of administrator actions. Sensitive values are redacted before storage and again before display.</p>
    </div>

    <form method="GET" action="{{ route('admin.audit-log.index') }}" class="mb-6 grid gap-3 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_18rem_auto] sm:items-end">
        <div>
            <label for="audit-search" class="block text-sm font-semibold text-ainchors-navy">Search</label>
            <input id="audit-search" name="q" type="search" value="{{ request('q') }}" placeholder="Action, entity type or ID" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 px-3.5 py-2.5 text-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        </div>
        <div>
            <label for="audit-action" class="block text-sm font-semibold text-ainchors-navy">Action</label>
            <select id="audit-action" name="action" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                <option value="">All actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ str($action)->replace('_', ' ')->headline() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="rounded-ainchors-button bg-ainchors-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Filter</button>
            <a href="{{ route('admin.audit-log.index') }}" class="rounded-ainchors-button border border-ainchors-navy/15 px-4 py-2.5 text-sm font-semibold text-ainchors-grey-dark transition hover:border-ainchors-green hover:text-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green">Reset</a>
        </div>
    </form>

    <section class="overflow-hidden rounded-ainchors-card border border-ainchors-navy/10 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[68rem] text-left text-sm">
                <thead class="border-b border-ainchors-navy/10 bg-slate-50 text-xs uppercase tracking-wide text-ainchors-grey-dark">
                    <tr><th class="px-5 py-3.5 font-bold">Time</th><th class="px-5 py-3.5 font-bold">Administrator</th><th class="px-5 py-3.5 font-bold">Action</th><th class="px-5 py-3.5 font-bold">Entity</th><th class="px-5 py-3.5 font-bold">Entity ID</th><th class="px-5 py-3.5 text-right font-bold">Details</th></tr>
                </thead>
                <tbody class="divide-y divide-ainchors-navy/8">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="px-5 py-4 text-ainchors-grey-dark">{{ $log->created_at?->format('j M Y, H:i:s') ?? '—' }}</td>
                            <td class="px-5 py-4"><p class="font-semibold text-ainchors-navy">{{ $log->admin?->full_name ?? 'Administrator unavailable' }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ $log->admin?->email ?? '—' }}</p></td>
                            <td class="px-5 py-4 font-semibold text-ainchors-navy">{{ str($log->action)->replace('_', ' ')->headline() }}</td>
                            <td class="px-5 py-4 text-ainchors-grey-dark">{{ class_basename($log->entity_type) }}</td>
                            <td class="px-5 py-4 text-ainchors-grey-dark">{{ $log->entity_id ?? '—' }}</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('admin.audit-log.show', $log) }}" class="font-semibold text-ainchors-green hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center"><p class="font-semibold text-ainchors-navy">No audit records match these filters.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $logs->onEachSide(1)->links() }}</div>
@endsection
