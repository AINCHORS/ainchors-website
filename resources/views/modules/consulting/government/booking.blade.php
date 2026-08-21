@extends('layouts.app')

@section('title', 'Booking | AINCHORS')

@section('content')
    <section class="relative isolate overflow-hidden bg-[linear-gradient(135deg,#e8fff7_0%,#ffffff_48%,#c1eff5_100%)] px-5 py-14 sm:px-6 sm:py-20">
        <div aria-hidden="true" class="absolute -left-24 top-16 h-72 w-72 rounded-full bg-ainchors-card-green/60 blur-3xl"></div>
        <div aria-hidden="true" class="absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-ainchors-card-blue/60 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-ainchors-container items-center gap-10 lg:grid-cols-[minmax(0,0.86fr)_minmax(26rem,0.74fr)] lg:gap-16">
            <header class="max-w-2xl">
                <div aria-hidden="true" class="mb-8 flex items-center gap-3">
                    <span class="h-px w-16 bg-ainchors-green"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-ainchors-green"></span>
                </div>
                <h1 class="font-sans text-4xl font-bold leading-tight tracking-[-0.035em] text-ainchors-navy sm:text-5xl">
                    Fill out your details below so we can confirm your booking!
                </h1>
                <div aria-hidden="true" class="mt-10 grid max-w-md grid-cols-3 gap-3">
                    <span class="h-2 rounded-full bg-ainchors-green"></span>
                    <span class="h-2 rounded-full bg-ainchors-card-blue"></span>
                    <span class="h-2 rounded-full bg-ainchors-card-orange"></span>
                </div>
            </header>

            <div class="rounded-[1.25rem] border border-white/90 bg-ainchors-white/95 p-6 shadow-[0_24px_60px_rgba(46,51,65,0.14)] backdrop-blur sm:p-8">
                @if (session('booking_success'))
                    <div class="mb-6 rounded-ainchors-button border border-ainchors-green/35 bg-ainchors-green-hero px-4 py-3 font-sans text-sm text-ainchors-navy" role="status">
                        {{ session('booking_success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('consulting.government.booking.store') }}" class="space-y-5" novalidate>
                    @csrf

                    <div>
                        <label for="booking-full-name" class="font-sans text-sm font-semibold text-ainchors-navy">Full Name *</label>
                        <input id="booking-full-name" name="full_name" type="text" value="{{ old('full_name') }}" required autocomplete="name" class="mt-2 w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy shadow-sm transition placeholder:text-ainchors-grey-light focus:border-ainchors-green focus:outline-none focus:ring-4 focus:ring-ainchors-green/15" @error('full_name', 'booking') aria-describedby="booking-full-name-error" @enderror>
                        @error('full_name', 'booking')<p id="booking-full-name-error" role="alert" class="mt-2 font-sans text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="booking-email" class="font-sans text-sm font-semibold text-ainchors-navy">Email *</label>
                        <input id="booking-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-2 w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy shadow-sm transition placeholder:text-ainchors-grey-light focus:border-ainchors-green focus:outline-none focus:ring-4 focus:ring-ainchors-green/15" @error('email', 'booking') aria-describedby="booking-email-error" @enderror>
                        @error('email', 'booking')<p id="booking-email-error" role="alert" class="mt-2 font-sans text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="booking-phone" class="font-sans text-sm font-semibold text-ainchors-navy">Phone *</label>
                        <input id="booking-phone" name="phone" type="tel" value="{{ old('phone') }}" required autocomplete="tel" class="mt-2 w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy shadow-sm transition placeholder:text-ainchors-grey-light focus:border-ainchors-green focus:outline-none focus:ring-4 focus:ring-ainchors-green/15" @error('phone', 'booking') aria-describedby="booking-phone-error" @enderror>
                        @error('phone', 'booking')<p id="booking-phone-error" role="alert" class="mt-2 font-sans text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="booking-company" class="font-sans text-sm font-semibold text-ainchors-navy">Company Name (If applicable)</label>
                        <input id="booking-company" name="company_name" type="text" value="{{ old('company_name') }}" autocomplete="organization" class="mt-2 w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy shadow-sm transition placeholder:text-ainchors-grey-light focus:border-ainchors-green focus:outline-none focus:ring-4 focus:ring-ainchors-green/15">
                    </div>

                    <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-ainchors-button bg-ainchors-green px-6 py-3 font-sans text-ainchors-body font-semibold text-ainchors-white shadow-[0_10px_22px_rgba(55,173,130,0.22)] transition hover:-translate-y-0.5 hover:bg-ainchors-green/90 focus:outline-none focus:ring-4 focus:ring-ainchors-green/25 focus:ring-offset-2">
                        Submit
                    </button>
                </form>

                <p class="mt-6 text-center font-sans text-sm text-ainchors-grey-dark">
                    <a href="{{ route('privacy') }}" class="underline decoration-ainchors-green/60 underline-offset-4 transition hover:text-ainchors-green">Privacy Policy</a>
                    <span aria-hidden="true" class="mx-1.5">|</span>
                    <a href="{{ route('terms') }}" class="underline decoration-ainchors-green/60 underline-offset-4 transition hover:text-ainchors-green">Terms of Service</a>
                </p>
            </div>
        </div>
    </section>
@endsection
