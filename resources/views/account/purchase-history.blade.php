@extends('layouts.app')

@section('title', 'Purchase History | AINCHORS')

@section('content')
<section class="bg-gradient-to-br from-ainchors-green-hero via-ainchors-white to-ainchors-card-blue/35 py-10 sm:py-14">
    <div class="mx-auto max-w-ainchors-container px-4 sm:px-6">
        <div class="mb-8 max-w-3xl">
            <span class="font-sans text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">Your account</span>
            <h1 class="mt-3 font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">Purchase History</h1>
            <p class="mt-3 font-sans text-ainchors-body text-ainchors-grey-dark">Review orders and payment records belonging to your AINCHORS account.</p>
        </div>

        <nav aria-label="Account navigation" class="mb-8 flex flex-wrap gap-2">
            <a href="{{ route('profile') }}" class="rounded-ainchors-button border border-ainchors-green/40 bg-ainchors-white px-4 py-2 font-sans text-sm font-semibold text-ainchors-green transition hover:bg-ainchors-green hover:text-ainchors-white">My Profile</a>
            <a href="{{ route('my-courses') }}" class="rounded-ainchors-button border border-ainchors-green/40 bg-ainchors-white px-4 py-2 font-sans text-sm font-semibold text-ainchors-green transition hover:bg-ainchors-green hover:text-ainchors-white">My Courses</a>
            <a href="{{ route('purchase-history') }}" aria-current="page" class="rounded-ainchors-button bg-ainchors-green px-4 py-2 font-sans text-sm font-semibold text-ainchors-white">Purchase History</a>
        </nav>

        @if ($orders->isEmpty())
            <div class="rounded-ainchors-card border border-ainchors-grey-light/25 bg-ainchors-white px-6 py-14 text-center shadow-lg shadow-ainchors-navy/5 sm:px-10">
                <h2 class="font-heading text-2xl font-bold text-ainchors-navy">No purchase history yet.</h2>
                <p class="mx-auto mt-3 max-w-md font-sans text-ainchors-body text-ainchors-grey-dark">No records available.</p>
                <x-button variant="primary" :href="route('courses.index')" class="mt-6">Browse Courses</x-button>
            </div>
        @else
            <div class="overflow-x-auto rounded-ainchors-card border border-ainchors-grey-light/25 bg-ainchors-white shadow-lg shadow-ainchors-navy/5">
                <table class="min-w-[1100px] w-full font-sans text-sm">
                    <caption class="sr-only">Purchase history for your AINCHORS account</caption>
                    <thead class="bg-ainchors-green-hero text-left text-ainchors-navy">
                        <tr>
                            <th scope="col" class="px-5 py-4 font-semibold">Order reference</th>
                            <th scope="col" class="px-5 py-4 font-semibold">Date</th>
                            <th scope="col" class="px-5 py-4 font-semibold">Product / Course / Package</th>
                            <th scope="col" class="px-5 py-4 font-semibold">Amount</th>
                            <th scope="col" class="px-5 py-4 font-semibold">Order status</th>
                            <th scope="col" class="px-5 py-4 font-semibold">Payment status</th>
                            <th scope="col" class="px-5 py-4 font-semibold">Payment method</th>
                            <th scope="col" class="px-5 py-4 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ainchors-grey-light/20">
                        @foreach ($orders as $order)
                            @php($payment = $order->payments->firstWhere('status', 'paid') ?? $order->payments->first())
                            @php($invoice = $customerInvoices->get($order->id))
                            @php($isCompletedPackage = $order->status === 'completed' && $order->items->contains(fn ($item) => data_get($item->metadata, 'product_type') === 'course_package'))
                            @php($hasCourseAction = $order->items->contains(fn ($item) => $activeEnrollments->has($item->product_id)))
                            <tr class="align-top text-ainchors-grey-dark">
                                <td class="whitespace-nowrap px-5 py-4 font-semibold text-ainchors-navy">{{ $order->order_number }}</td>
                                <td class="whitespace-nowrap px-5 py-4">{{ $order->placed_at?->format('j M Y') ?? $order->created_at?->format('j M Y') ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <ul class="space-y-1">
                                        @foreach ($order->items as $item)
                                            <li>{{ $item->product_name }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">{{ $order->currency }} {{ number_format((float) $order->total_amount, 2) }}</td>
                                <td class="whitespace-nowrap px-5 py-4">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</td>
                                <td class="whitespace-nowrap px-5 py-4">{{ $payment ? ucfirst(str_replace('_', ' ', $payment->status)) : '—' }}</td>
                                <td class="px-5 py-4">
                                    <span class="whitespace-nowrap">{{ $payment ? ucfirst($payment->provider) : '—' }}</span>
                                    @if ($payment?->provider_transaction_id)
                                        <span class="mt-1 block max-w-52 break-all text-xs text-ainchors-grey-light">Reference: {{ $payment->provider_transaction_id }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($order->items as $item)
                                            @php($enrollment = $activeEnrollments->get($item->product_id))
                                            @if ($enrollment?->product)
                                                <a href="{{ route('learn.show', $enrollment->product) }}" class="rounded-ainchors-button bg-ainchors-green px-3 py-2 text-xs font-semibold text-ainchors-white transition hover:bg-ainchors-navy">
                                                    Access Course
                                                </a>
                                            @endif
                                        @endforeach

                                        @if ($isCompletedPackage)
                                            <a href="{{ route('my-courses') }}" class="rounded-ainchors-button bg-ainchors-green px-3 py-2 text-xs font-semibold text-ainchors-white transition hover:bg-ainchors-green-dark">
                                                My Courses
                                            </a>
                                        @endif

                                        @if ($invoice)
                                            <a href="{{ route('purchase-history.invoice', $invoice) }}" class="rounded-ainchors-button border border-ainchors-green px-3 py-2 text-xs font-semibold text-ainchors-green transition hover:bg-ainchors-green hover:text-ainchors-white">
                                                Invoice
                                            </a>
                                        @endif

                                        @if (! $invoice && ! $hasCourseAction && ! $isCompletedPackage)
                                            <span class="py-2 text-xs text-ainchors-grey-light">No action available</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
@endsection
