@extends('layouts.admin')

@section('title', 'Payment Record | AINCHORS Admin')

@section('content')
    <div class="mx-auto max-w-4xl"><a href="{{ route('admin.payments.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ainchors-green transition hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>All payments</a><div class="mt-5 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm sm:p-8"><div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">Read-only commerce record</p><h1 class="mt-2 font-heading text-3xl font-bold text-ainchors-navy">Payment record</h1><p class="mt-2 text-sm text-ainchors-grey-dark">{{ strtolower($payment->provider) === 'demo' ? 'Demo/test payment' : $payment->provider }}</p></div>@include('admin.partials.status-badge', ['status' => $payment->status])</div><dl class="mt-7 divide-y divide-ainchors-navy/8 text-sm"><div class="flex flex-col gap-1 py-3 sm:flex-row sm:justify-between sm:gap-5"><dt class="font-semibold text-ainchors-grey-dark">Related order</dt><dd class="text-ainchors-navy">@if ($payment->order)<a href="{{ route('admin.orders.show', $payment->order) }}" class="font-semibold text-ainchors-green hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">{{ $payment->order->order_number }}</a>@else Order unavailable @endif</dd></div><div class="flex flex-col gap-1 py-3 sm:flex-row sm:justify-between sm:gap-5"><dt class="font-semibold text-ainchors-grey-dark">Customer</dt><dd class="text-ainchors-navy">{{ $payment->order?->user?->full_name ?? 'Unavailable' }}</dd></div><div class="flex flex-col gap-1 py-3 sm:flex-row sm:justify-between sm:gap-5"><dt class="font-semibold text-ainchors-grey-dark">Amount</dt><dd class="font-semibold text-ainchors-navy">{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</dd></div><div class="flex flex-col gap-1 py-3 sm:flex-row sm:justify-between sm:gap-5"><dt class="font-semibold text-ainchors-grey-dark">Paid at</dt><dd class="text-ainchors-navy">{{ $payment->paid_at?->format('j M Y, H:i') ?? 'Not paid' }}</dd></div></dl><aside class="mt-7 rounded-ainchors-button border border-ainchors-navy/10 bg-slate-50 px-4 py-3 text-sm leading-relaxed text-ainchors-grey-dark"><strong class="text-ainchors-navy">Sensitive payment data is protected.</strong> Provider payloads, card details, security codes and transaction secrets are intentionally not visible or editable here.</aside></div></div>
    <section aria-labelledby="invoice-heading" class="mx-auto mt-6 max-w-4xl rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm sm:p-8">
        <h2 id="invoice-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Invoice / Receipt</h2>
        <dl class="mt-5 grid gap-4 border-b border-ainchors-navy/10 pb-5 text-sm sm:grid-cols-3">
            <div><dt class="font-semibold text-ainchors-grey-dark">Environment</dt><dd class="mt-1 text-ainchors-navy">{{ str($payment->payment_environment ?? 'unknown')->headline() }}</dd></div>
            <div><dt class="font-semibold text-ainchors-grey-dark">Provider transaction ID</dt><dd class="mt-1 break-all text-ainchors-navy">{{ $payment->provider_transaction_id ?: '—' }}</dd></div>
            <div><dt class="font-semibold text-ainchors-grey-dark">Failure state</dt><dd class="mt-1 text-ainchors-navy">{{ $payment->failure_reason ?: '—' }}</dd></div>
        </dl>
        @if ($invoice)
            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="font-semibold text-ainchors-grey-dark">Provider</dt><dd class="mt-1 text-ainchors-navy">{{ str($invoice->provider)->headline() }}</dd></div>
                <div><dt class="font-semibold text-ainchors-grey-dark">Invoice / reference</dt><dd class="mt-1 break-words text-ainchors-navy">{{ $invoice->invoice_number ?: $invoice->external_reference }}</dd></div>
                <div><dt class="font-semibold text-ainchors-grey-dark">Status</dt><dd class="mt-1 text-ainchors-navy">{{ str($invoice->status)->headline() }}</dd></div>
                <div><dt class="font-semibold text-ainchors-grey-dark">Environment</dt><dd class="mt-1 text-ainchors-navy">{{ str($payment->payment_environment ?? 'unknown')->headline() }}</dd></div>
            </dl>
            <a href="{{ route('admin.invoices.show', $invoice) }}" target="_blank" rel="noopener noreferrer" class="mt-5 inline-flex items-center justify-center rounded-ainchors-button bg-ainchors-green px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-green-dark focus:outline-none focus:ring-2 focus:ring-ainchors-green">View Invoice</a>
        @else
            <p class="mt-3 text-sm text-ainchors-grey-dark">Not available</p>
        @endif
    </section>
@endsection
