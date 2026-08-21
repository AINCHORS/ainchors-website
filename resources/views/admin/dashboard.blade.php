@extends('layouts.admin')

@section('title', 'Dashboard | AINCHORS Admin')

@section('content')
    @php
        $metrics = $metrics ?? [];
        $metricCards = [
            ['label' => 'Total users', 'value' => data_get($metrics, 'total_users', 0), 'detail' => 'Registered accounts'],
            ['label' => 'New users', 'value' => data_get($metrics, 'new_users', 0), 'detail' => 'Recent registrations'],
            ['label' => 'Active products', 'value' => data_get($metrics, 'active_products', 0), 'detail' => 'Available in the catalogue'],
            ['label' => 'Total orders', 'value' => data_get($metrics, 'total_orders', 0), 'detail' => 'Recorded orders'],
            ['label' => 'Completed payments', 'value' => data_get($metrics, 'completed_payments', 0), 'detail' => 'Payment records marked paid'],
            ['label' => 'Enrollments', 'value' => data_get($metrics, 'enrollments', 0), 'detail' => 'Course access records'],
        ];
        $revenueByCurrency = $revenueByCurrency ?? collect();
        $recentOrders = $recentOrders ?? collect();
    @endphp

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">Administration</p>
            <h1 class="mt-2 font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">Dashboard</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-ainchors-grey-dark">A current view of AINCHORS account, catalogue and commerce records.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center rounded-ainchors-button bg-ainchors-green px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Add user</a>
    </div>

    <section aria-label="Current records" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($metricCards as $card)
            <article class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-ainchors-grey-dark">{{ $card['label'] }}</p>
                <p class="mt-3 font-heading text-3xl font-bold text-ainchors-navy">{{ number_format((int) $card['value']) }}</p>
                <p class="mt-2 text-xs leading-relaxed text-ainchors-grey-light">{{ $card['detail'] }}</p>
            </article>
        @endforeach
    </section>

    <section aria-labelledby="revenue-heading" class="mt-8 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 id="revenue-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Completed payment revenue</h2>
                <p class="mt-1 text-sm leading-relaxed text-ainchors-grey-dark">Amounts remain separated by currency and provider; no currency conversion is inferred.</p>
            </div>
            <a href="{{ route('admin.payments.index') }}" class="text-sm font-semibold text-ainchors-green transition hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">View payments</a>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[34rem] text-left text-sm">
                <caption class="sr-only">Revenue from completed payments by currency and provider</caption>
                <thead class="border-b border-ainchors-navy/10 text-xs uppercase tracking-wide text-ainchors-grey-dark">
                    <tr><th scope="col" class="pb-3 pr-5 font-bold">Currency</th><th scope="col" class="pb-3 pr-5 font-bold">Provider</th><th scope="col" class="pb-3 pr-5 text-right font-bold">Completed payments</th><th scope="col" class="pb-3 text-right font-bold">Amount</th></tr>
                </thead>
                <tbody class="divide-y divide-ainchors-navy/8">
                    @forelse ($revenueByCurrency as $row)
                        <tr>
                            <td class="py-3 pr-5 font-semibold text-ainchors-navy">{{ $row->currency ?? data_get($row, 'currency', '—') }}</td>
                            <td class="py-3 pr-5 text-ainchors-grey-dark">{{ (strtolower((string) ($row->provider ?? data_get($row, 'provider', '')))) === 'demo' ? 'Demo/test payment' : ($row->provider ?? data_get($row, 'provider', '—')) }}</td>
                            <td class="py-3 pr-5 text-right text-ainchors-grey-dark">{{ number_format((int) ($row->payment_count ?? data_get($row, 'payment_count', 0))) }}</td>
                            <td class="py-3 text-right font-semibold text-ainchors-navy">{{ $row->currency ?? data_get($row, 'currency', '') }} {{ number_format((float) ($row->total_amount ?? data_get($row, 'total_amount', 0)), 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-ainchors-grey-dark">No completed payments have been recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section aria-labelledby="recent-orders-heading" class="mt-8 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 id="recent-orders-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Recent orders</h2>
                <p class="mt-1 text-sm text-ainchors-grey-dark">Read-only financial records.</p>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-ainchors-green transition hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">View all orders</a>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[44rem] text-left text-sm">
                <caption class="sr-only">Recent orders</caption>
                <thead class="border-b border-ainchors-navy/10 text-xs uppercase tracking-wide text-ainchors-grey-dark">
                    <tr><th scope="col" class="pb-3 pr-5 font-bold">Order</th><th scope="col" class="pb-3 pr-5 font-bold">Customer</th><th scope="col" class="pb-3 pr-5 font-bold">Status</th><th scope="col" class="pb-3 pr-5 text-right font-bold">Total</th><th scope="col" class="pb-3 text-right font-bold">Action</th></tr>
                </thead>
                <tbody class="divide-y divide-ainchors-navy/8">
                    @forelse ($recentOrders as $order)
                        <tr>
                            <td class="py-3 pr-5 font-semibold text-ainchors-navy">{{ $order->order_number }}</td>
                            <td class="py-3 pr-5 text-ainchors-grey-dark">{{ $order->user?->full_name ?? 'Customer unavailable' }}</td>
                            <td class="py-3 pr-5">@include('admin.partials.status-badge', ['status' => $order->status])</td>
                            <td class="py-3 pr-5 text-right font-semibold text-ainchors-navy">{{ $order->currency }} {{ number_format((float) $order->total_amount, 2) }}</td>
                            <td class="py-3 text-right"><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-ainchors-green hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">Inspect<span class="sr-only"> {{ $order->order_number }}</span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-ainchors-grey-dark">No orders have been recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
