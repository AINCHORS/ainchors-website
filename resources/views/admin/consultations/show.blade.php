@extends('layouts.admin')

@section('title', 'Consultation #'.$consultation->id.' | AINCHORS Admin')

@section('content')
    <div class="mx-auto max-w-5xl">
        <a href="{{ route('admin.consultations.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ainchors-green transition hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
            All consultations
        </a>

        <div class="mt-5 grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(20rem,0.9fr)]">
            <section class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">CRM consultation</p>
                        <h1 class="mt-2 font-heading text-3xl font-bold text-ainchors-navy">{{ $consultation->lead?->full_name ?? 'Lead unavailable' }}</h1>
                        <p class="mt-2 text-sm text-ainchors-grey-dark">Request #{{ $consultation->id }} · {{ $consultation->requested_at?->format('j M Y, H:i') ?? '—' }}</p>
                    </div>
                    @include('admin.partials.status-badge', ['status' => $consultation->status])
                </div>

                <dl class="mt-7 divide-y divide-ainchors-navy/8 text-sm">
                    <div class="grid gap-2 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]"><dt class="font-semibold text-ainchors-grey-dark">Email</dt><dd class="break-words text-ainchors-navy">{{ $consultation->lead?->email ?? '—' }}</dd></div>
                    <div class="grid gap-2 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]"><dt class="font-semibold text-ainchors-grey-dark">Phone</dt><dd class="text-ainchors-navy">{{ $consultation->lead?->phone ?: '—' }}</dd></div>
                    <div class="grid gap-2 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]"><dt class="font-semibold text-ainchors-grey-dark">Country</dt><dd class="text-ainchors-navy">{{ $consultation->lead?->country ?: '—' }}</dd></div>
                    <div class="grid gap-2 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]"><dt class="font-semibold text-ainchors-grey-dark">Company</dt><dd class="text-ainchors-navy">{{ $consultation->lead?->company_name ?: '—' }}</dd></div>
                    <div class="grid gap-2 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]"><dt class="font-semibold text-ainchors-grey-dark">Consulting type</dt><dd class="text-ainchors-navy">{{ $consultation->consulting_type ? str($consultation->consulting_type)->headline() : 'Not specified' }}</dd></div>
                    <div class="grid gap-2 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]"><dt class="font-semibold text-ainchors-grey-dark">Source</dt><dd class="text-ainchors-navy">{{ $consultation->source_page ?: ($consultation->lead?->source ?? '—') }}</dd></div>
                    <div class="grid gap-2 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]"><dt class="font-semibold text-ainchors-grey-dark">Scheduled</dt><dd class="text-ainchors-navy">{{ $consultation->scheduled_at?->format('j M Y, H:i') ?? 'Not scheduled' }}</dd></div>
                    <div class="grid gap-2 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]"><dt class="font-semibold text-ainchors-grey-dark">Assigned</dt><dd class="text-ainchors-navy">{{ $consultation->assignedAdmin?->full_name ?? 'Unassigned' }}</dd></div>
                </dl>

                <div class="mt-6 border-t border-ainchors-navy/10 pt-6">
                    <h2 class="font-heading text-xl font-bold text-ainchors-navy">How can we help?</h2>
                    <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ainchors-grey-dark">{{ $consultation->lead?->notes ?: 'No requirements were recorded.' }}</p>
                </div>

                @if (filled($consultation->notes))
                    <div class="mt-6 border-t border-ainchors-navy/10 pt-6">
                        <h2 class="font-heading text-xl font-bold text-ainchors-navy">Internal notes</h2>
                        <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ainchors-grey-dark">{{ $consultation->notes }}</p>
                    </div>
                @endif
            </section>

            <section class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="manage-consultation-heading">
                <h2 id="manage-consultation-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Manage consultation</h2>
                <p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">Scheduling and internal notes are audited. Notes are not copied into the audit payload.</p>

                <form method="POST" action="{{ route('admin.consultations.update', $consultation) }}" class="mt-6 space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="consultation-status" class="block text-sm font-semibold text-ainchors-navy">Status</label>
                        <select id="consultation-status" name="status" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(old('status', $consultation->status) === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="scheduled-at" class="block text-sm font-semibold text-ainchors-navy">Scheduled date and time</label>
                        <input id="scheduled-at" name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at', $consultation->scheduled_at?->format('Y-m-d\TH:i')) }}" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 px-3.5 py-2.5 text-sm text-ainchors-navy focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                        @error('scheduled_at')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-start gap-3 rounded-ainchors-button bg-slate-50 p-4 text-sm text-ainchors-grey-dark">
                        <input type="checkbox" name="assigned_to_me" value="1" @checked(old('assigned_to_me', $consultation->assigned_admin_id === auth()->id())) class="mt-1 rounded border-ainchors-grey-light text-ainchors-green focus:ring-ainchors-green">
                        <span><strong class="text-ainchors-navy">Assign to me</strong><span class="mt-1 block">Clear this to leave the request unassigned.</span></span>
                    </label>

                    <div>
                        <label for="consultation-notes" class="block text-sm font-semibold text-ainchors-navy">Internal notes</label>
                        <textarea id="consultation-notes" name="notes" rows="7" maxlength="5000" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 px-3.5 py-2.5 text-sm text-ainchors-navy focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">{{ old('notes', $consultation->notes) }}</textarea>
                        @error('notes')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="w-full rounded-ainchors-button bg-ainchors-green px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Save consultation</button>
                </form>
            </section>
        </div>
    </div>
@endsection
