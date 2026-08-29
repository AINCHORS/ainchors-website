@php
    $status = $status ?? 'unknown';
    $normalizedStatus = strtolower((string) $status);
    $styles = match ($normalizedStatus) {
        'active', 'paid', 'completed', 'won', 'qualified', 'contacted', 'consultation_booked', 'consultation_scheduled', 'booked', 'shortlisted' => 'bg-ainchors-green-hero text-emerald-800 ring-emerald-700/20',
        'pending', 'awaiting_payment', 'processing', 'new', 'new_request', 'consultation_requested', 'requested', 'proposal', 'reviewing' => 'bg-amber-50 text-amber-800 ring-amber-700/20',
        'inactive', 'expired', 'revoked', 'cancelled', 'closed', 'failed', 'lost', 'no_show', 'rejected' => 'bg-slate-100 text-slate-700 ring-slate-600/20',
        'refunded' => 'bg-orange-50 text-orange-800 ring-orange-700/20',
        default => 'bg-slate-100 text-slate-700 ring-slate-600/20',
    };
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $styles }}">{{ str($status)->replace('_', ' ')->headline() }}</span>
