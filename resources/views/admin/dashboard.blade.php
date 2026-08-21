@extends('layouts.admin')

@section('title', 'Dashboard | AINCHORS Admin')

@section('content')
    @php
        $metrics = $metrics ?? [];
        $revenueByCurrency = $revenueByCurrency ?? collect();
        $recentOrders = $recentOrders ?? collect();
        $recentPayments = $recentPayments ?? collect();
        $recentApplications = $recentApplications ?? collect();
        $recentConsultations = $recentConsultations ?? collect();

        $overviewCards = [
            ['label' => 'Total users', 'value' => data_get($metrics, 'total_users', 0), 'detail' => 'Registered accounts'],
            ['label' => 'New users', 'value' => data_get($metrics, 'new_users', 0), 'detail' => 'Last 30 days'],
            ['label' => 'Active courses', 'value' => data_get($metrics, 'active_courses', 0), 'detail' => 'Published course products'],
            ['label' => 'Active packages', 'value' => data_get($metrics, 'active_packages', 0), 'detail' => 'Published course packages'],
            ['label' => 'Enrollments', 'value' => data_get($metrics, 'enrollments', 0), 'detail' => 'Course access records'],
            ['label' => 'Active products', 'value' => data_get($metrics, 'active_products', 0), 'detail' => 'All active product types'],
        ];

        $commerceCards = [
            ['label' => 'Orders', 'value' => data_get($metrics, 'total_orders', 0), 'detail' => 'All recorded orders'],
            ['label' => 'Awaiting payment', 'value' => data_get($metrics, 'awaiting_payment_orders', 0), 'detail' => 'Pending / awaiting orders'],
            ['label' => 'Completed orders', 'value' => data_get($metrics, 'completed_orders', 0), 'detail' => 'Completed order records'],
            ['label' => 'Paid payments', 'value' => data_get($metrics, 'paid_payments', 0), 'detail' => 'Payment status = paid'],
            ['label' => 'Pending payments', 'value' => data_get($metrics, 'pending_payments', 0), 'detail' => 'Pending or processing'],
            ['label' => 'Failed payments', 'value' => data_get($metrics, 'failed_payments', 0), 'detail' => 'Failed payment records'],
            ['label' => 'Demo / test', 'value' => data_get($metrics, 'test_payments', 0), 'detail' => 'Provider = demo'],
            ['label' => 'Non-demo provider', 'value' => data_get($metrics, 'non_demo_payments', 0), 'detail' => 'Environment not inferred yet'],
        ];

        $pipelineCards = [
            ['label' => 'New contact leads', 'value' => data_get($metrics, 'contact_leads_new', 0), 'detail' => 'New public contact submissions'],
            ['label' => 'Consultations requested', 'value' => data_get($metrics, 'consultations_requested', 0), 'detail' => 'Awaiting booking action'],
            ['label' => 'Consultations booked', 'value' => data_get($metrics, 'consultations_booked', 0), 'detail' => 'Scheduled consultation requests'],
            ['label' => 'Applications new', 'value' => data_get($metrics, 'job_applications_new', 0), 'detail' => 'New candidates'],
            ['label' => 'Applications reviewing', 'value' => data_get($metrics, 'job_applications_reviewing', 0), 'detail' => 'In review'],
            ['label' => 'Applications shortlisted', 'value' => data_get($metrics, 'job_applications_shortlisted', 0), 'detail' => 'Shortlisted candidates'],
        ];
    @endphp

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">Administration</p>
            <h1 class="mt-2 font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">Dashboard</h1>
            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-ainchors-grey-dark">Operational view of accounts, products, commerce, recruitment and consultation activity.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center rounded-ainchors-button bg-ainchors-green px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Add user</a>
    </div>

    <section aria-labelledby="overview-heading">
        <h2 id="overview-heading" class="font-heading text-xl font-bold text-ainchors-navy">Platform overview</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($overviewCards as $card)
                <article class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-ainchors-grey-dark">{{ $card['label'] }}</p><p class="mt-3 font-heading text-3xl font-bold text-ainchors-navy">{{ number_format((int) $card['value']) }}</p><p class="mt-2 text-xs leading-relaxed text-ainchors-grey-light">{{ $card['detail'] }}</p></article>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="commerce-heading" class="mt-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><h2 id="commerce-heading" class="font-heading text-xl font-bold text-ainchors-navy">Commerce health</h2><p class="mt-1 text-sm text-ainchors-grey-dark">Demo payments are test data. Phase 1 does not infer a live environment for non-demo rows.</p></div><div class="flex gap-4 text-sm font-semibold"><a href="{{ route('admin.orders.index') }}" class="text-ainchors-green hover:text-ainchors-navy">Orders</a><a href="{{ route('admin.payments.index') }}" class="text-ainchors-green hover:text-ainchors-navy">Payments</a></div></div>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($commerceCards as $card)
                <article class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-ainchors-grey-dark">{{ $card['label'] }}</p><p class="mt-3 font-heading text-3xl font-bold text-ainchors-navy">{{ number_format((int) $card['value']) }}</p><p class="mt-2 text-xs leading-relaxed text-ainchors-grey-light">{{ $card['detail'] }}</p></article>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="pipeline-heading" class="mt-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><h2 id="pipeline-heading" class="font-heading text-xl font-bold text-ainchors-navy">Business pipeline</h2><p class="mt-1 text-sm text-ainchors-grey-dark">Current CRM, consultation and recruitment workload.</p></div></div>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($pipelineCards as $card)
                <article class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-ainchors-grey-dark">{{ $card['label'] }}</p><p class="mt-3 font-heading text-3xl font-bold text-ainchors-navy">{{ number_format((int) $card['value']) }}</p><p class="mt-2 text-xs leading-relaxed text-ainchors-grey-light">{{ $card['detail'] }}</p></article>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="revenue-heading" class="mt-8 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm sm:p-6">
        <div><h2 id="revenue-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Paid payment totals</h2><p class="mt-1 text-sm leading-relaxed text-ainchors-grey-dark">Amounts remain separated by currency and provider. Demo rows are test-mode data and must not be read as production revenue.</p></div>
        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <thead class="border-b border-ainchors-navy/10 text-xs uppercase tracking-wide text-ainchors-grey-dark"><tr><th class="pb-3 pr-5 font-bold">Currency</th><th class="pb-3 pr-5 font-bold">Provider</th><th class="pb-3 pr-5 font-bold">Environment</th><th class="pb-3 pr-5 text-right font-bold">Payments</th><th class="pb-3 text-right font-bold">Amount</th></tr></thead>
                <tbody class="divide-y divide-ainchors-navy/8">
                    @forelse ($revenueByCurrency as $row)
                        @php($isDemo = strtolower((string) ($row->provider ?? '')) === 'demo')
                        <tr><td class="py-3 pr-5 font-semibold text-ainchors-navy">{{ $row->currency }}</td><td class="py-3 pr-5 text-ainchors-grey-dark">{{ $row->provider }}</td><td class="py-3 pr-5"><span @class(['rounded-full px-2.5 py-1 text-xs font-bold', 'bg-amber-100 text-amber-900' => $isDemo, 'bg-slate-100 text-ainchors-navy' => ! $isDemo])>{{ $isDemo ? 'Demo / test' : 'Not tracked yet' }}</span></td><td class="py-3 pr-5 text-right text-ainchors-grey-dark">{{ number_format((int) $row->payment_count) }}</td><td class="py-3 text-right font-semibold text-ainchors-navy">{{ $row->currency }} {{ number_format((float) $row->total_amount, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-ainchors-grey-dark">No paid payments have been recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        <section class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center justify-between gap-3"><h2 class="font-heading text-xl font-bold text-ainchors-navy">Recent orders</h2><a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-ainchors-green hover:text-ainchors-navy">View all</a></div>
            <div class="mt-4 divide-y divide-ainchors-navy/8">@forelse($recentOrders as $order)<a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between gap-4 py-3 text-sm hover:bg-slate-50"><div><p class="font-semibold text-ainchors-navy">{{ $order->order_number }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ $order->user?->full_name ?? 'Customer unavailable' }}</p></div><div class="text-right"><p class="font-semibold text-ainchors-navy">{{ $order->currency }} {{ number_format((float)$order->total_amount, 2) }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ str($order->status)->replace('_',' ')->headline() }}</p></div></a>@empty<p class="py-8 text-center text-sm text-ainchors-grey-dark">No orders recorded.</p>@endforelse</div>
        </section>

        <section class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center justify-between gap-3"><h2 class="font-heading text-xl font-bold text-ainchors-navy">Recent payments</h2><a href="{{ route('admin.payments.index') }}" class="text-sm font-semibold text-ainchors-green hover:text-ainchors-navy">View all</a></div>
            <div class="mt-4 divide-y divide-ainchors-navy/8">@forelse($recentPayments as $payment)<a href="{{ route('admin.payments.show', $payment) }}" class="flex items-center justify-between gap-4 py-3 text-sm hover:bg-slate-50"><div><p class="font-semibold text-ainchors-navy">{{ $payment->provider }} · {{ $payment->order?->order_number ?? 'Order unavailable' }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ $payment->order?->user?->full_name ?? 'Customer unavailable' }}</p></div><div class="text-right"><p class="font-semibold text-ainchors-navy">{{ $payment->currency }} {{ number_format((float)$payment->amount, 2) }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ str($payment->status)->headline() }}{{ strtolower($payment->provider) === 'demo' ? ' · Test' : '' }}</p></div></a>@empty<p class="py-8 text-center text-sm text-ainchors-grey-dark">No payments recorded.</p>@endforelse</div>
        </section>

        <section class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center justify-between gap-3"><h2 class="font-heading text-xl font-bold text-ainchors-navy">Recent applications</h2><a href="{{ route('admin.job-applications.index') }}" class="text-sm font-semibold text-ainchors-green hover:text-ainchors-navy">View all</a></div>
            <div class="mt-4 divide-y divide-ainchors-navy/8">@forelse($recentApplications as $application)<a href="{{ route('admin.job-applications.show', $application) }}" class="flex items-center justify-between gap-4 py-3 text-sm hover:bg-slate-50"><div><p class="font-semibold text-ainchors-navy">{{ $application->full_name }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ $application->jobPosition?->title ?? 'Position unavailable' }}</p></div><div class="text-right text-xs font-semibold text-ainchors-grey-dark">{{ str($application->status)->headline() }}</div></a>@empty<p class="py-8 text-center text-sm text-ainchors-grey-dark">No applications recorded.</p>@endforelse</div>
        </section>

        <section class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center justify-between gap-3"><h2 class="font-heading text-xl font-bold text-ainchors-navy">Recent consultations</h2><a href="{{ route('admin.consultations.index') }}" class="text-sm font-semibold text-ainchors-green hover:text-ainchors-navy">View all</a></div>
            <div class="mt-4 divide-y divide-ainchors-navy/8">@forelse($recentConsultations as $consultation)<a href="{{ route('admin.consultations.show', $consultation) }}" class="flex items-center justify-between gap-4 py-3 text-sm hover:bg-slate-50"><div><p class="font-semibold text-ainchors-navy">{{ $consultation->lead?->full_name ?? 'Lead unavailable' }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ $consultation->lead?->company_name ?: 'No company' }}</p></div><div class="text-right"><p class="text-xs font-semibold text-ainchors-grey-dark">{{ str($consultation->status)->replace('_',' ')->headline() }}</p><p class="mt-1 text-xs text-ainchors-grey-light">{{ $consultation->scheduled_at?->format('j M, H:i') ?? 'Not scheduled' }}</p></div></a>@empty<p class="py-8 text-center text-sm text-ainchors-grey-dark">No consultation requests recorded.</p>@endforelse</div>
        </section>
    </div>
@endsection
