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
            <div data-purchase-history-desktop class="hidden overflow-x-auto rounded-ainchors-card border border-ainchors-grey-light/25 bg-ainchors-white shadow-lg shadow-ainchors-navy/5 lg:block">
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
                            @php($displayStatus = $order->status === 'completed' ? 'Completed' : ($order->status === 'cancelled' ? 'Cancelled' : 'Failed'))
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
                                <td class="whitespace-nowrap px-5 py-4">{{ $displayStatus }}</td>
                                <td class="whitespace-nowrap px-5 py-4">{{ $payment ? ucfirst(str_replace('_', ' ', $payment->status)) : '—' }}</td>
                                <td class="px-5 py-4">
                                    <span class="whitespace-nowrap">{{ $payment ? ucfirst($payment->provider) : '—' }}</span>
                                    @if ($payment?->provider_transaction_id)
                                        <span class="mt-1 block max-w-52 break-all text-xs text-ainchors-grey-light">Reference: {{ $payment->provider_transaction_id }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        @if ($isCompletedPackage)
                                            <a href="{{ route('my-courses') }}" class="rounded-ainchors-button bg-ainchors-green px-3 py-2 text-xs font-semibold text-ainchors-white transition hover:bg-ainchors-green-dark">
                                                My Courses
                                            </a>
                                        @endif

                                        @if ($invoice)
                                            <a data-purchase-receipt data-purchase-receipt-desktop href="{{ route('purchase-history.invoice', $invoice) }}" target="_blank" rel="noopener noreferrer" class="whitespace-nowrap rounded-ainchors-button bg-ainchors-green px-3 py-2 text-xs font-semibold text-ainchors-white transition hover:bg-ainchors-green-hero hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">
                                                View Receipt
                                            </a>
                                        @endif

                                        @if (! $invoice && ! $isCompletedPackage)
                                            <span data-purchase-no-action class="whitespace-nowrap py-2 text-xs text-ainchors-grey-light">No action available</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div data-purchase-history-cards data-course-carousel class="course-carousel lg:hidden" aria-label="Purchase history carousel">
                <div class="course-carousel-viewport" data-carousel-viewport tabindex="0">
                    <div class="course-carousel-track" data-carousel-track>
                        @foreach ($orders as $order)
                            @php($payment = $order->payments->firstWhere('status', 'paid') ?? $order->payments->first())
                            @php($invoice = $customerInvoices->get($order->id))
                            @php($displayStatus = $order->status === 'completed' ? 'Completed' : ($order->status === 'cancelled' ? 'Cancelled' : 'Failed'))
                            <div class="course-carousel-item" data-carousel-card>
                    <article class="flex min-w-0 flex-col rounded-ainchors-card border border-ainchors-grey-light/25 bg-ainchors-white p-5 shadow-lg shadow-ainchors-navy/5">
                        <div class="flex items-start justify-between gap-3 border-b border-ainchors-grey-light/20 pb-4">
                            <div class="min-w-0">
                                <p class="font-sans text-xs font-bold uppercase tracking-[0.12em] text-ainchors-green">Order</p>
                                <h2 data-purchase-order-reference title="{{ $order->order_number }}" class="mt-1 min-h-[2.75rem] break-all line-clamp-2 font-heading text-base font-bold leading-snug text-ainchors-navy">{{ $order->order_number }}</h2>
                            </div>
                            <span class="shrink-0 rounded-full bg-ainchors-green-hero px-3 py-1 font-sans text-xs font-semibold text-ainchors-navy">
                                {{ $displayStatus }}
                            </span>
                        </div>

                        <div class="flex-1 space-y-4 py-4 font-sans text-sm text-ainchors-grey-dark">
                            <div data-purchase-product class="min-h-[4.5rem]">
                                <p class="text-xs font-semibold uppercase tracking-wide text-ainchors-grey-light">Product / Course / Package</p>
                                <ul class="mt-1 space-y-1 font-semibold leading-snug text-ainchors-navy">
                                    @foreach ($order->items as $item)
                                        <li class="break-words">{{ $item->product_name }}</li>
                                    @endforeach
                                </ul>
                            </div>

                            <dl class="grid grid-cols-2 gap-x-4 gap-y-3">
                                <div>
                                    <dt class="text-xs font-semibold text-ainchors-grey-light">Date</dt>
                                    <dd class="mt-0.5">{{ $order->placed_at?->format('j M Y') ?? $order->created_at?->format('j M Y') ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-ainchors-grey-light">Amount</dt>
                                    <dd class="mt-0.5 font-bold text-ainchors-navy">{{ $order->currency }} {{ number_format((float) $order->total_amount, 2) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-ainchors-grey-light">Payment status</dt>
                                    <dd class="mt-0.5">{{ $payment ? ucfirst(str_replace('_', ' ', $payment->status)) : '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-ainchors-grey-light">Payment method</dt>
                                    <dd class="mt-0.5">{{ $payment ? ucfirst($payment->provider) : '—' }}</dd>
                                </div>
                            </dl>

                            @if ($payment?->provider_transaction_id)
                                <div>
                                    <p class="text-xs font-semibold text-ainchors-grey-light">Transaction reference</p>
                                    <p class="mt-1 break-all text-xs leading-relaxed">{{ $payment->provider_transaction_id }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="mt-auto flex flex-col gap-2 border-t border-ainchors-grey-light/20 pt-4">
                            @if ($invoice)
                                <a data-purchase-receipt href="{{ route('purchase-history.invoice', $invoice) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center justify-center whitespace-nowrap rounded-ainchors-button bg-ainchors-green px-4 py-2 text-center text-sm font-semibold text-ainchors-white transition hover:bg-ainchors-green-hero hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">
                                    View Receipt
                                </a>
                            @else
                                <span data-purchase-no-action class="whitespace-nowrap py-2 text-center text-xs text-ainchors-grey-light">No action available</span>
                            @endif
                        </div>
                    </article>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="course-carousel-navigation" data-carousel-navigation hidden>
                    <button type="button" class="course-carousel-arrow course-carousel-previous" data-carousel-previous aria-label="Previous purchases">←</button>
                    <div class="course-carousel-pagination" data-carousel-pagination aria-label="Purchase history carousel pagination"></div>
                    <button type="button" class="course-carousel-arrow course-carousel-next" data-carousel-next aria-label="Next purchases">→</button>
                </div>
                <p class="sr-only" data-carousel-status aria-live="polite"></p>
            </div>
        @endif
    </div>
</section>
@endsection
