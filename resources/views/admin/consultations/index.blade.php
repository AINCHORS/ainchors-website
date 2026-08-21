@extends('layouts.admin')

@section('title', 'Consultations | AINCHORS Admin')

@section('content')
    <div class="mb-8">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">CRM</p>
        <h1 class="mt-2 font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">Consultations</h1>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-ainchors-grey-dark">Manage consultation requests created by the public booking flow without deleting the original lead record.</p>
    </div>

    <form method="GET" action="{{ route('admin.consultations.index') }}" class="mb-6 grid gap-3 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_13rem_auto] sm:items-end">
        <div>
            <label for="consultation-search" class="block text-sm font-semibold text-ainchors-navy">Search</label>
            <input id="consultation-search" name="q" type="search" value="{{ request('q') }}" placeholder="Name, email, company or source" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 px-3.5 py-2.5 text-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        </div>
        <div>
            <label for="consultation-status" class="block text-sm font-semibold text-ainchors-navy">Status</label>
            <select id="consultation-status" name="status" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="rounded-ainchors-button bg-ainchors-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Filter</button>
            <a href="{{ route('admin.consultations.index') }}" class="rounded-ainchors-button border border-ainchors-navy/15 px-4 py-2.5 text-sm font-semibold text-ainchors-grey-dark transition hover:border-ainchors-green hover:text-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green">Reset</a>
        </div>
    </form>

    <section class="overflow-hidden rounded-ainchors-card border border-ainchors-navy/10 bg-white shadow-sm" aria-labelledby="consultations-table-heading">
        <h2 id="consultations-table-heading" class="sr-only">Consultation requests</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[72rem] text-left text-sm">
                <thead class="border-b border-ainchors-navy/10 bg-slate-50 text-xs uppercase tracking-wide text-ainchors-grey-dark">
                    <tr>
                        <th class="px-5 py-3.5 font-bold">Contact</th>
                        <th class="px-5 py-3.5 font-bold">Company</th>
                        <th class="px-5 py-3.5 font-bold">Requested</th>
                        <th class="px-5 py-3.5 font-bold">Scheduled</th>
                        <th class="px-5 py-3.5 font-bold">Status</th>
                        <th class="px-5 py-3.5 font-bold">Assigned</th>
                        <th class="px-5 py-3.5 text-right font-bold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ainchors-navy/8">
                    @forelse ($consultations as $consultation)
                        <tr>
                            <td class="px-5 py-4"><p class="font-semibold text-ainchors-navy">{{ $consultation->lead?->full_name ?? 'Lead unavailable' }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ $consultation->lead?->email ?? '—' }}</p></td>
                            <td class="px-5 py-4 text-ainchors-grey-dark">{{ $consultation->lead?->company_name ?: '—' }}</td>
                            <td class="px-5 py-4 text-ainchors-grey-dark">{{ $consultation->requested_at?->format('j M Y, H:i') ?? '—' }}</td>
                            <td class="px-5 py-4 text-ainchors-grey-dark">{{ $consultation->scheduled_at?->format('j M Y, H:i') ?? 'Not scheduled' }}</td>
                            <td class="px-5 py-4">@include('admin.partials.status-badge', ['status' => $consultation->status])</td>
                            <td class="px-5 py-4 text-ainchors-grey-dark">{{ $consultation->assignedAdmin?->full_name ?? 'Unassigned' }}</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('admin.consultations.show', $consultation) }}" class="font-semibold text-ainchors-green hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center"><p class="font-semibold text-ainchors-navy">No consultation requests match these filters.</p><p class="mt-1 text-sm text-ainchors-grey-dark">Public booking requests will appear here.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $consultations->onEachSide(1)->links() }}</div>
@endsection
