@extends('layouts.admin')

@section('title', 'Audit #'.$auditLog->id.' | AINCHORS Admin')

@section('content')
    <div class="mx-auto max-w-5xl">
        <a href="{{ route('admin.audit-log.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ainchors-green transition hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
            Audit log
        </a>

        <section class="mt-5 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">System audit</p>
                    <h1 class="mt-2 font-heading text-3xl font-bold text-ainchors-navy">{{ str($auditLog->action)->replace('_', ' ')->headline() }}</h1>
                    <p class="mt-2 text-sm text-ainchors-grey-dark">{{ $auditLog->created_at?->format('j M Y, H:i:s') ?? '—' }}</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-ainchors-navy">Read only</span>
            </div>

            <dl class="mt-7 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-ainchors-button bg-slate-50 p-4"><dt class="font-semibold text-ainchors-grey-dark">Administrator</dt><dd class="mt-1 font-semibold text-ainchors-navy">{{ $auditLog->admin?->full_name ?? 'Unavailable' }}</dd><dd class="mt-1 text-xs text-ainchors-grey-dark">{{ $auditLog->admin?->email ?? '—' }}</dd></div>
                <div class="rounded-ainchors-button bg-slate-50 p-4"><dt class="font-semibold text-ainchors-grey-dark">Entity</dt><dd class="mt-1 break-words font-semibold text-ainchors-navy">{{ class_basename($auditLog->entity_type) }}</dd></div>
                <div class="rounded-ainchors-button bg-slate-50 p-4"><dt class="font-semibold text-ainchors-grey-dark">Entity ID</dt><dd class="mt-1 font-semibold text-ainchors-navy">{{ $auditLog->entity_id ?? '—' }}</dd></div>
                <div class="rounded-ainchors-button bg-slate-50 p-4"><dt class="font-semibold text-ainchors-grey-dark">Audit ID</dt><dd class="mt-1 font-semibold text-ainchors-navy">#{{ $auditLog->id }}</dd></div>
            </dl>

            <div class="mt-8 grid gap-6 lg:grid-cols-2">
                <section aria-labelledby="audit-before-heading">
                    <h2 id="audit-before-heading" class="font-heading text-xl font-bold text-ainchors-navy">Before</h2>
                    <pre class="mt-3 max-h-[32rem] overflow-auto rounded-ainchors-button bg-slate-950 p-4 text-xs leading-relaxed text-slate-100">{{ json_encode($beforeValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}' }}</pre>
                </section>
                <section aria-labelledby="audit-after-heading">
                    <h2 id="audit-after-heading" class="font-heading text-xl font-bold text-ainchors-navy">After</h2>
                    <pre class="mt-3 max-h-[32rem] overflow-auto rounded-ainchors-button bg-slate-950 p-4 text-xs leading-relaxed text-slate-100">{{ json_encode($afterValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}' }}</pre>
                </section>
            </div>
        </section>
    </div>
@endsection
