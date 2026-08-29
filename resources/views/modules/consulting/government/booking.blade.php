@extends('layouts.app')

@section('title', 'Request a Consultation | AINCHORS')

@section('content')
    <section class="relative isolate overflow-hidden bg-[linear-gradient(135deg,#e8fff7_0%,#ffffff_48%,#c1eff5_100%)] px-5 py-7 sm:px-6 sm:py-8 lg:py-6">
        <div aria-hidden="true" class="absolute -left-24 top-16 h-72 w-72 rounded-full bg-ainchors-card-green/60 blur-3xl"></div>
        <div aria-hidden="true" class="absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-ainchors-card-blue/60 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-ainchors-container items-start gap-8 lg:grid-cols-[minmax(0,0.92fr)_minmax(25rem,0.68fr)] lg:gap-12">
            <header class="max-w-2xl lg:self-center">
                <div aria-hidden="true" class="mb-6 flex items-center gap-3">
                    <span class="h-px w-16 bg-ainchors-green"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-ainchors-green"></span>
                </div>
                <p class="mb-3 font-sans text-sm font-bold uppercase tracking-[0.16em] text-ainchors-green">Consulting request</p>
                <h1 class="font-sans text-4xl font-bold leading-tight tracking-[-0.035em] text-ainchors-navy sm:text-[2.75rem]">Request a Consultation</h1>
                <p class="mt-4 max-w-xl font-sans text-base leading-relaxed text-ainchors-grey-dark">Tell us what your organisation needs and our team will review your request.</p>
                <div aria-hidden="true" class="mt-7 grid max-w-md grid-cols-3 gap-3">
                    <span class="h-2 rounded-full bg-ainchors-green"></span>
                    <span class="h-2 rounded-full bg-ainchors-card-blue"></span>
                    <span class="h-2 rounded-full bg-ainchors-card-orange"></span>
                </div>
            </header>

            <div class="w-full max-w-[39rem] justify-self-end rounded-[1.25rem] border border-white/90 bg-ainchors-white/95 p-4 shadow-[0_24px_60px_rgba(46,51,65,0.14)] backdrop-blur sm:p-5">
                @if ($bookingComplete)
                    <div class="rounded-ainchors-button border border-ainchors-green/35 bg-ainchors-green-hero px-5 py-5 font-sans text-ainchors-navy" role="status">
                        <h2 class="font-heading text-xl font-bold">Request received</h2>
                        <p class="mt-2 text-sm leading-relaxed">{{ session('booking_success') }}</p>
                    </div>
                    <a href="{{ route('consulting.main') }}" class="mt-6 inline-flex min-h-12 w-full items-center justify-center rounded-ainchors-button bg-ainchors-green px-6 py-3 font-sans text-ainchors-body font-semibold text-white transition hover:bg-ainchors-green/90 focus:outline-none focus:ring-4 focus:ring-ainchors-green/25 focus:ring-offset-2">Return to Consulting</a>
                @else
                <div class="mb-3 rounded-ainchors-button border border-ainchors-green/25 bg-ainchors-green-hero px-3.5 py-2">
                    <p class="font-sans text-xs font-bold uppercase tracking-[0.14em] text-ainchors-green">Consultation Type</p>
                    <p class="mt-1 font-sans text-base font-semibold text-ainchors-navy">{{ $consultingType === 'government' ? 'Government Consulting' : 'Private Consulting' }}</p>
                </div>

                <form method="POST" action="{{ route('consulting.booking.store') }}" class="space-y-2.5" novalidate>
                    @csrf

                    <div>
                        <label for="booking-full-name" class="font-sans text-sm font-semibold text-ainchors-navy">Full Name *</label>
                        <input id="booking-full-name" name="full_name" type="text" value="{{ old('full_name') }}" required autocomplete="name" class="mt-1 w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2 font-sans text-sm text-ainchors-navy shadow-sm transition placeholder:text-ainchors-grey-light focus:border-ainchors-green focus:outline-none focus:ring-4 focus:ring-ainchors-green/15" @error('full_name', 'booking') aria-describedby="booking-full-name-error" @enderror>
                        @error('full_name', 'booking')<p id="booking-full-name-error" role="alert" class="mt-2 font-sans text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="booking-email" class="font-sans text-sm font-semibold text-ainchors-navy">Email *</label>
                        <input id="booking-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-1 w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2 font-sans text-sm text-ainchors-navy shadow-sm transition placeholder:text-ainchors-grey-light focus:border-ainchors-green focus:outline-none focus:ring-4 focus:ring-ainchors-green/15" @error('email', 'booking') aria-describedby="booking-email-error" @enderror>
                        @error('email', 'booking')<p id="booking-email-error" role="alert" class="mt-2 font-sans text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-2.5 sm:grid-cols-2">
                        <div>
                            <label for="booking-country" class="font-sans text-sm font-semibold text-ainchors-navy">Country *</label>
                            <select id="booking-country" name="country" required autocomplete="country-name" class="mt-1 w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2 font-sans text-sm text-ainchors-navy shadow-sm transition focus:border-ainchors-green focus:outline-none focus:ring-4 focus:ring-ainchors-green/15" @error('country', 'booking') aria-describedby="booking-country-error" @enderror>
                                <option value="">Select country</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country }}" @selected(old('country') === $country)>{{ $country }}</option>
                                @endforeach
                            </select>
                            @error('country', 'booking')<p id="booking-country-error" role="alert" class="mt-2 font-sans text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="booking-phone" class="font-sans text-sm font-semibold text-ainchors-navy">Phone *</label>
                            <input id="booking-phone" name="phone" type="tel" value="{{ old('phone') }}" required autocomplete="tel" inputmode="tel" pattern="[0-9+() .-]+" placeholder="e.g. +60 12 345 6789" class="mt-1 w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2 font-sans text-sm text-ainchors-navy shadow-sm transition placeholder:text-ainchors-grey-light focus:border-ainchors-green focus:outline-none focus:ring-4 focus:ring-ainchors-green/15" @error('phone', 'booking') aria-describedby="booking-phone-error" @enderror>
                            @error('phone', 'booking')<p id="booking-phone-error" role="alert" class="mt-2 font-sans text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="booking-company" class="font-sans text-sm font-semibold text-ainchors-navy">Company Name (optional)</label>
                        <input id="booking-company" name="company_name" type="text" value="{{ old('company_name') }}" autocomplete="organization" class="mt-1 w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2 font-sans text-sm text-ainchors-navy shadow-sm transition placeholder:text-ainchors-grey-light focus:border-ainchors-green focus:outline-none focus:ring-4 focus:ring-ainchors-green/15">
                    </div>

                    <div>
                        <label for="booking-requirements" class="font-sans text-sm font-semibold text-ainchors-navy">How can we help? *</label>
                        <textarea id="booking-requirements" name="requirements" rows="3" maxlength="5000" required placeholder="Briefly tell us about your consulting needs." class="mt-1 w-full resize-y rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2 font-sans text-sm text-ainchors-navy shadow-sm transition placeholder:text-ainchors-grey-light focus:border-ainchors-green focus:outline-none focus:ring-4 focus:ring-ainchors-green/15" @error('requirements', 'booking') aria-describedby="booking-requirements-error" @enderror>{{ old('requirements') }}</textarea>
                        @error('requirements', 'booking')<p id="booking-requirements-error" role="alert" class="mt-2 font-sans text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center rounded-ainchors-button bg-ainchors-green px-5 py-2.5 font-sans text-sm font-semibold text-ainchors-white shadow-[0_10px_22px_rgba(55,173,130,0.22)] transition hover:-translate-y-0.5 hover:bg-ainchors-green/90 focus:outline-none focus:ring-4 focus:ring-ainchors-green/25 focus:ring-offset-2">
                        Request Consultation
                    </button>
                </form>

                <p class="mt-3 text-center font-sans text-xs text-ainchors-grey-dark">
                    <a href="{{ route('privacy') }}" class="underline decoration-ainchors-green/60 underline-offset-4 transition hover:text-ainchors-green">Privacy Policy</a>
                    <span aria-hidden="true" class="mx-1.5">|</span>
                    <a href="{{ route('terms') }}" class="underline decoration-ainchors-green/60 underline-offset-4 transition hover:text-ainchors-green">Terms of Service</a>
                </p>
                @endif
            </div>
        </div>
    </section>

    @unless ($bookingComplete)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const country = document.getElementById('booking-country');
                const phone = document.getElementById('booking-phone');
                if (!country || !phone) return;

                const formats = {
                    'Australia': { dial: '+61', example: '+61 4 1234 5678' },
                    'Canada': { dial: '+1', example: '+1 416 555 0123' },
                    'China': { dial: '+86', example: '+86 138 0013 8000' },
                    'Hong Kong': { dial: '+852', example: '+852 9123 4567' },
                    'Japan': { dial: '+81', example: '+81 90 1234 5678' },
                    'Malaysia': { dial: '+60', example: '+60 12 345 6789' },
                    'New Zealand': { dial: '+64', example: '+64 21 123 4567' },
                    'Singapore': { dial: '+65', example: '+65 8123 4567' },
                    'United Kingdom': { dial: '+44', example: '+44 7700 900123' },
                    'United States': { dial: '+1', example: '+1 202 555 0123' },
                    'Other': { dial: '', example: 'Include the international country code' },
                };

                const countriesByDial = Object.entries(formats)
                    .filter(([name, item]) => item.dial && name !== 'Canada')
                    .sort((a, b) => b[1].dial.length - a[1].dial.length);

                const applyCountryFormat = () => {
                    const selected = formats[country.value];
                    if (!selected) return;

                    const current = phone.value.trim();
                    const previousDial = phone.dataset.selectedDial || '';
                    phone.placeholder = selected.example;
                    if (selected.dial && (!current || current === previousDial)) {
                        phone.value = `${selected.dial} `;
                    }
                    phone.dataset.selectedDial = selected.dial;
                };

                country.addEventListener('change', applyCountryFormat);
                phone.addEventListener('input', () => {
                    phone.value = phone.value.replace(/[^0-9+() .-]/g, '');
                    const compact = phone.value.replace(/[\s().-]/g, '');
                    if (!compact.startsWith('+')) return;

                    const match = countriesByDial.find(([, item]) => compact.startsWith(item.dial));
                    if (match && country.value !== match[0]) {
                        country.value = match[0];
                        phone.dataset.selectedDial = match[1].dial;
                        phone.placeholder = match[1].example;
                    }
                });

                if (country.value) applyCountryFormat();
                phone.dispatchEvent(new Event('input'));
            });
        </script>
    @endunless
@endsection
