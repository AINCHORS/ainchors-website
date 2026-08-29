@extends('layouts.admin')

@section('title', $lead->full_name.' | Consulting Request | AINCHORS Admin')

@section('content')
    @php($consultation = $lead->consultationRequests->first())
    <div class="mx-auto max-w-5xl">
        <a href="{{ route('admin.leads.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ainchors-green transition hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
            All contact submissions
        </a>

        <div class="mt-5 grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(19rem,0.72fr)]">
            <section class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">{{ $consultation?->consulting_type === 'government' ? 'Government Consultation Request' : ($consultation?->consulting_type === 'private' ? 'Private Consultation Request' : 'Consultation Request') }}</p>
                        <h1 class="mt-2 font-heading text-3xl font-bold text-ainchors-navy">{{ $lead->full_name }}</h1>
                        <p class="mt-2 text-sm text-ainchors-grey-dark">Received {{ $lead->created_at?->format('j M Y, H:i') ?? '—' }}</p>
                    </div>
                    @include('admin.partials.status-badge', ['status' => $lead->status])
                </div>

                <h2 class="mt-7 font-heading text-2xl font-bold text-ainchors-navy">Request details</h2>
                <dl class="mt-4 divide-y divide-ainchors-navy/8 text-sm">
                    <div class="grid gap-2 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]"><dt class="font-semibold text-ainchors-grey-dark">Full Name</dt><dd class="text-ainchors-navy">{{ $lead->full_name }}</dd></div>
                    <div class="grid gap-2 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]"><dt class="font-semibold text-ainchors-grey-dark">Email</dt><dd class="break-words text-ainchors-navy">{{ $lead->email }}</dd></div>
                    <div class="grid gap-2 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]"><dt class="font-semibold text-ainchors-grey-dark">Phone</dt><dd class="text-ainchors-navy">{{ $lead->phone ?: '—' }}</dd></div>
                    <div class="grid gap-2 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]"><dt class="font-semibold text-ainchors-grey-dark">Country</dt><dd class="text-ainchors-navy">{{ $lead->country ?: '—' }}</dd></div>
                    <div class="grid gap-2 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]"><dt class="font-semibold text-ainchors-grey-dark">Company</dt><dd class="text-ainchors-navy">{{ $lead->company_name ?: '—' }}</dd></div>
                    <div class="grid gap-2 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]"><dt class="font-semibold text-ainchors-grey-dark">Consulting Type</dt><dd class="text-ainchors-navy">{{ $consultation?->consulting_type === 'government' ? 'Government Consulting' : ($consultation?->consulting_type === 'private' ? 'Private Consulting' : 'Legacy / Unknown') }}</dd></div>
                    <div class="grid gap-2 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]"><dt class="font-semibold text-ainchors-grey-dark">Source</dt><dd class="text-ainchors-navy">{{ str($lead->source)->replace('_', ' ')->headline() }}</dd></div>
                </dl>

                <section class="mt-6 border-t border-ainchors-navy/10 pt-6" aria-labelledby="requirements-heading">
                    <h2 id="requirements-heading" class="font-heading text-xl font-bold text-ainchors-navy">How can we help? <span class="text-sm font-semibold text-ainchors-grey-dark">(Requirements)</span></h2>
                    <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ainchors-grey-dark">{{ $lead->notes ?: 'No requirements were recorded.' }}</p>
                </section>

                <section class="mt-6 border-t border-ainchors-navy/10 pt-6" aria-labelledby="consultation-record-heading">
                    <h2 id="consultation-record-heading" class="font-heading text-xl font-bold text-ainchors-navy">Consultation record</h2>
                    @if ($consultation)
                        <a href="{{ route('admin.consultations.show', $consultation) }}" class="mt-3 inline-flex rounded-ainchors-button border border-ainchors-green px-4 py-2.5 text-sm font-semibold text-ainchors-green transition hover:bg-ainchors-green-hero focus:outline-none focus:ring-2 focus:ring-ainchors-green">View Consultation</a>
                    @else
                        <p class="mt-3 text-sm text-ainchors-grey-dark">No consultation scheduled yet.</p>
                    @endif
                </section>
            </section>

            <section class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="request-status-heading">
                <h2 id="request-status-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Request Status</h2>
                <p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">This stage is internal and is not shown to the requester.</p>
                <form method="POST" action="{{ route('admin.leads.update', $lead) }}" class="mt-6 space-y-4">
                    @csrf
                    @method('PUT')
                    <label for="lead-show-status" class="block text-sm font-semibold text-ainchors-navy">Request Status</label>
                    <select id="lead-show-status" name="status" class="block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $lead->status) === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                    <button type="submit" class="w-full rounded-ainchors-button bg-ainchors-green px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Save Request Status</button>
                </form>
            </section>
        </div>
    </div>
@endsection
