@extends('layouts.admin')

@section('title', 'Contact Submissions | AINCHORS Admin')

@section('content')
    @php($filterStatuses = array_values(array_unique([...$consultingStatuses, ...$contactStatuses])))
    <div class="mb-8">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">CRM</p>
        <h1 class="mt-2 font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">Contact submissions</h1>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-ainchors-grey-dark">Review incoming requests and update their internal status. Submissions are retained rather than casually deleted.</p>
    </div>

    <form method="GET" action="{{ route('admin.leads.index') }}" class="mb-6 grid gap-3 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_13rem_auto] sm:items-end">
        <div>
            <label for="leads-search" class="block text-sm font-semibold text-ainchors-navy">Search</label>
            <input id="leads-search" name="q" type="search" value="{{ request('q') }}" placeholder="Name, email or company" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 px-3.5 py-2.5 text-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        </div>
        <div>
            <label for="leads-status" class="block text-sm font-semibold text-ainchors-navy">Status</label>
            <select id="leads-status" name="status" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                <option value="">All statuses</option>
                @foreach ($filterStatuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="rounded-ainchors-button bg-ainchors-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Filter</button>
            <a href="{{ route('admin.leads.index') }}" class="rounded-ainchors-button border border-ainchors-navy/15 px-4 py-2.5 text-sm font-semibold text-ainchors-grey-dark transition hover:border-ainchors-green hover:text-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green">Reset</a>
        </div>
    </form>

    <section aria-labelledby="leads-table-heading" class="overflow-hidden rounded-ainchors-card border border-ainchors-navy/10 bg-white shadow-sm">
        <h2 id="leads-table-heading" class="sr-only">Contact submissions</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[68rem] text-left text-sm">
                <caption class="sr-only">AINCHORS contact submissions</caption>
                <thead class="border-b border-ainchors-navy/10 bg-slate-50 text-xs uppercase tracking-wide text-ainchors-grey-dark">
                    <tr><th scope="col" class="px-5 py-3.5 font-bold">Contact</th><th scope="col" class="px-5 py-3.5 font-bold">Company</th><th scope="col" class="px-5 py-3.5 font-bold">Source</th><th scope="col" class="px-5 py-3.5 font-bold">Received</th><th scope="col" class="px-5 py-3.5 font-bold">Status</th><th scope="col" class="px-5 py-3.5 text-right font-bold">Action</th></tr>
                </thead>
                <tbody class="divide-y divide-ainchors-navy/8">
                    @forelse ($leads as $lead)
                        @php($rowStatuses = $lead->source === 'consulting_booking' ? $consultingStatuses : $contactStatuses)
                        <tr>
                            <td class="px-5 py-4"><p class="font-semibold text-ainchors-navy">{{ $lead->full_name }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ $lead->email }}</p></td>
                            <td class="px-5 py-4 text-ainchors-grey-dark">{{ $lead->company_name ?: '—' }}</td>
                            <td class="px-5 py-4 text-ainchors-grey-dark">{{ str($lead->source)->replace('_', ' ')->headline() }}</td>
                            <td class="px-5 py-4 text-ainchors-grey-dark">{{ $lead->created_at?->format('j M Y, H:i') ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <form method="POST" action="{{ route('admin.leads.update', $lead) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <label class="sr-only" for="lead-status-{{ $lead->id }}">Status for {{ $lead->full_name }}</label>
                                    <select id="lead-status-{{ $lead->id }}" name="status" class="rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-2.5 py-1.5 text-xs text-ainchors-navy focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                                        @foreach ($rowStatuses as $status)
                                            <option value="{{ $status }}" @selected($lead->status === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="text-xs font-semibold text-ainchors-green hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">Save</button>
                                </form>
                            </td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('admin.leads.show', $lead) }}" class="font-semibold text-ainchors-green hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">View<span class="sr-only"> {{ $lead->full_name }}</span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center"><p class="font-semibold text-ainchors-navy">No contact submissions match these filters.</p><p class="mt-1 text-sm text-ainchors-grey-dark">New submissions will appear here when a visitor contacts AINCHORS.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @if (method_exists($leads, 'links'))<div class="mt-6">{{ $leads->onEachSide(1)->links() }}</div>@endif
@endsection
