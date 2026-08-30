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
            ['label' => 'Active courses', 'value' => data_get($metrics, 'active_courses', 0), 'detail' => 'Published courses'],
            ['label' => 'Active packages', 'value' => data_get($metrics, 'active_packages', 0), 'detail' => 'Published course packages'],
            ['label' => 'Enrollments', 'value' => data_get($metrics, 'enrollments', 0), 'detail' => 'Course access records'],
            ['label' => 'Active products', 'value' => data_get($metrics, 'active_products', 0), 'detail' => 'All active product types'],
        ];

        $commerceCards = [
            ['label' => 'Orders', 'value' => data_get($metrics, 'total_orders', 0), 'detail' => 'All recorded orders'],
            ['label' => 'Awaiting payment', 'value' => data_get($metrics, 'awaiting_payment_orders', 0), 'detail' => 'Pending / awaiting orders'],
            ['label' => 'Completed orders', 'value' => data_get($metrics, 'completed_orders', 0), 'detail' => 'Completed order records'],
            ['label' => 'Paid payments', 'value' => data_get($metrics, 'paid_payments', 0), 'detail' => 'Verified live payments'],
            ['label' => 'Pending payments', 'value' => data_get($metrics, 'pending_payments', 0), 'detail' => 'Pending or processing'],
            ['label' => 'Failed payments', 'value' => data_get($metrics, 'failed_payments', 0), 'detail' => 'Failed payment records'],
            ['label' => 'Demo / test', 'value' => data_get($metrics, 'test_payments', 0), 'detail' => 'Excluded from totals'],
            ['label' => 'Live provider records', 'value' => data_get($metrics, 'live_provider_payments', 0), 'detail' => 'Environment = live'],
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
                <article class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-ainchors-grey-dark">{{ $card['label'] }}</p>
                    <p class="mt-3 font-heading text-3xl font-bold text-ainchors-navy">{{ number_format((int) $card['value']) }}</p>
                    <p class="mt-2 text-xs text-ainchors-grey-light">{{ $card['detail'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="commerce-heading" class="mt-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="commerce-heading" class="font-heading text-xl font-bold text-ainchors-navy">Commerce health</h2>
                <p class="mt-1 text-sm text-ainchors-grey-dark">Live provider activity is separated from Demo, Sandbox and unknown payment records.</p>
            </div>
            <div class="flex gap-4 text-sm font-semibold"><a href="{{ route('admin.orders.index') }}" class="text-ainchors-green hover:text-ainchors-navy">Orders</a><a href="{{ route('admin.payments.index') }}" class="text-ainchors-green hover:text-ainchors-navy">Payments</a></div>
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($commerceCards as $card)
                <article class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-ainchors-grey-dark">{{ $card['label'] }}</p>
                    <p class="mt-3 font-heading text-3xl font-bold text-ainchors-navy">{{ number_format((int) $card['value']) }}</p>
                    <p class="mt-2 text-xs text-ainchors-grey-light">{{ $card['detail'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="pipeline-heading" class="mt-8">
        <h2 id="pipeline-heading" class="font-heading text-xl font-bold text-ainchors-navy">Business pipeline</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($pipelineCards as $card)
                <article class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-ainchors-grey-dark">{{ $card['label'] }}</p>
                    <p class="mt-3 font-heading text-3xl font-bold text-ainchors-navy">{{ number_format((int) $card['value']) }}</p>
                    <p class="mt-2 text-xs text-ainchors-grey-light">{{ $card['detail'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="revenue-heading" class="mt-8 overflow-hidden rounded-ainchors-card border border-ainchors-navy/10 bg-white shadow-sm">
        <div class="border-b border-ainchors-navy/10 px-5 py-4 sm:px-6">
            <h2 id="revenue-heading" class="font-heading text-xl font-bold text-ainchors-navy">Paid payment totals</h2>
            <p class="mt-1 text-sm text-ainchors-grey-dark">Only verified live payments are included. Demo, Sandbox and unknown-environment payments remain in payment records but are excluded from these totals.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[36rem] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-ainchors-grey-dark">
                    <tr>
                        <th class="px-5 py-3 sm:px-6">Currency</th>
                        <th class="px-5 py-3 sm:px-6">Provider</th>
                        <th class="px-5 py-3 text-right sm:px-6">Payments</th>
                        <th class="px-5 py-3 text-right sm:px-6">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ainchors-navy/8">
                    @forelse ($revenueByCurrency as $row)
                        <tr>
                            <td class="px-5 py-3 font-semibold text-ainchors-navy sm:px-6">{{ $row->currency }}</td>
                            <td class="px-5 py-3 text-ainchors-grey-dark sm:px-6">{{ str($row->provider)->headline() }}</td>
                            <td class="px-5 py-3 text-right text-ainchors-navy sm:px-6">{{ number_format((int) $row->payment_count) }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-ainchors-navy sm:px-6">{{ $row->currency }} {{ number_format((float) $row->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-6 text-center text-ainchors-grey-dark sm:px-6">No verified live payments recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        <section class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center justify-between gap-3"><h2 class="font-heading text-xl font-bold text-ainchors-navy">Recent orders</h2><a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-ainchors-green hover:text-ainchors-navy">View all</a></div>
            <div class="mt-4 divide-y divide-ainchors-navy/8">
                @forelse ($recentOrders as $order)
                    <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between gap-4 py-3 text-sm hover:bg-slate-50"><div><p class="font-semibold text-ainchors-navy">{{ $order->order_number }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ $order->user?->full_name ?? 'Customer unavailable' }}</p></div><div class="text-right"><p class="font-semibold text-ainchors-navy">{{ $order->currency }} {{ number_format((float) $order->total_amount, 2) }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ str($order->status)->replace('_', ' ')->headline() }}</p></div></a>
                @empty
                    <p class="py-8 text-center text-sm text-ainchors-grey-dark">No orders recorded.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center justify-between gap-3"><h2 class="font-heading text-xl font-bold text-ainchors-navy">Recent payments</h2><a href="{{ route('admin.payments.index') }}" class="text-sm font-semibold text-ainchors-green hover:text-ainchors-navy">View all records</a></div>
            <div class="mt-4 divide-y divide-ainchors-navy/8">
                @forelse ($recentPayments as $payment)
                    <a href="{{ route('admin.payments.show', $payment) }}" class="flex items-center justify-between gap-4 py-3 text-sm hover:bg-slate-50"><div><p class="font-semibold text-ainchors-navy">{{ $payment->provider }} · {{ $payment->order?->order_number ?? 'Order unavailable' }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ $payment->order?->user?->full_name ?? 'Customer unavailable' }}</p></div><div class="text-right"><p class="font-semibold text-ainchors-navy">{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ str($payment->status)->headline() }} · {{ str($payment->payment_environment ?? 'unknown')->headline() }}</p></div></a>
                @empty
                    <p class="py-8 text-center text-sm text-ainchors-grey-dark">No payment records recorded.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center justify-between gap-3"><h2 class="font-heading text-xl font-bold text-ainchors-navy">Recent applications</h2><a href="{{ route('admin.job-applications.index') }}" class="text-sm font-semibold text-ainchors-green hover:text-ainchors-navy">View all</a></div>
            <div class="mt-4 divide-y divide-ainchors-navy/8">
                @forelse ($recentApplications as $application)
                    <a href="{{ route('admin.job-applications.show', $application) }}" class="flex items-center justify-between gap-4 py-3 text-sm hover:bg-slate-50"><div><p class="font-semibold text-ainchors-navy">{{ $application->full_name }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ $application->jobPosition?->title ?? 'Position unavailable' }}</p></div><span class="text-xs font-semibold text-ainchors-grey-dark">{{ str($application->status)->headline() }}</span></a>
                @empty
                    <p class="py-8 text-center text-sm text-ainchors-grey-dark">No applications recorded.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center justify-between gap-3"><h2 class="font-heading text-xl font-bold text-ainchors-navy">Recent consultations</h2><a href="{{ route('admin.consultations.index') }}" class="text-sm font-semibold text-ainchors-green hover:text-ainchors-navy">View all</a></div>
            <div class="mt-4 divide-y divide-ainchors-navy/8">
                @forelse ($recentConsultations as $consultation)
                    <a href="{{ route('admin.consultations.show', $consultation) }}" class="flex items-center justify-between gap-4 py-3 text-sm hover:bg-slate-50"><div><p class="font-semibold text-ainchors-navy">{{ $consultation->lead?->full_name ?? 'Lead unavailable' }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ $consultation->lead?->company_name ?: 'No company' }} · {{ $consultation->consulting_type ? str($consultation->consulting_type)->headline() : 'Type not specified' }}</p></div><span class="text-xs font-semibold text-ainchors-grey-dark">{{ str($consultation->status)->replace('_', ' ')->headline() }}</span></a>
                @empty
                    <p class="py-8 text-center text-sm text-ainchors-grey-dark">No consultations recorded.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
