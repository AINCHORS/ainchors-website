@extends('layouts.app')

@section('title', 'Contact Us | AINCHORS')

@php
    $enquiryTypes = [
        'general_enquiry' => 'General Enquiry',
        'training_enquiry' => 'Training Enquiry',
        'consulting_enquiry' => 'Consulting Enquiry',
        'course_support' => 'Course Support',
        'partnership' => 'Partnership',
        'feedback_complaint' => 'Feedback / Complaint',
    ];
    $aliases = ['training' => 'training_enquiry', 'consulting' => 'consulting_enquiry', 'support' => 'course_support', 'feedback' => 'feedback_complaint'];
    $requestedType = request()->query('type');
    $preselectedType = $aliases[$requestedType] ?? $requestedType;
    $selectedType = old('feedback_type', array_key_exists((string) $preselectedType, $enquiryTypes) ? $preselectedType : '');
    $fieldClass = 'mt-1 min-h-9 w-full rounded border border-slate-300 bg-white px-3 py-1.5 font-sans text-sm text-ainchors-navy shadow-sm transition placeholder:text-slate-400 focus:border-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green/15';
@endphp

@section('content')
<div data-contact-page>
    <section class="border-b border-slate-200 bg-gradient-to-br from-white via-[#E8FFF7] to-sky-50">
        <div class="mx-auto grid max-w-[1180px] items-center gap-8 px-5 py-6 sm:px-8 lg:grid-cols-[1fr_430px] lg:px-10">
            <div class="max-w-2xl">
                <p class="font-sans text-sm font-semibold uppercase tracking-[0.18em] text-ainchors-green">Let’s connect</p>
                <h1 class="mt-3 font-display text-4xl font-bold leading-tight text-[#2E3341] sm:text-5xl">Contact Us</h1>
                <p class="mt-3 max-w-xl font-sans text-base leading-7 text-slate-600">Feel free to reach out to us — we'd be happy to assist you with any questions or concerns.</p>
            </div>
            <div class="grid grid-cols-2 gap-3" aria-label="Contact AINCHORS">
                <img src="{{ asset('assets/site/69842dcb1dfc023da8722750.png') }}" alt="Contact us on a laptop screen" class="h-40 w-full rounded-2xl object-cover shadow-lg">
                <img src="{{ asset('assets/site/69842cb62dd98570ab278007.png') }}" alt="Working at a laptop" class="h-40 w-full rounded-2xl object-cover shadow-lg">
            </div>
        </div>
    </section>

    <section class="relative bg-cover bg-center" style="background-image: linear-gradient(rgba(255,255,255,.72), rgba(255,255,255,.72)), url('{{ asset('assets/site/69720b1210cc273b80021c38.webp') }}');">
        <div class="mx-auto grid max-w-[1180px] items-start gap-8 px-5 py-6 sm:px-8 lg:grid-cols-[minmax(0,38fr)_minmax(0,62fr)] lg:px-10">
            <aside class="py-5 sm:py-6" aria-label="Contact details">
                <p class="font-sans text-xs font-semibold uppercase tracking-[0.16em] text-ainchors-green">Contact details</p>
                <h2 class="mb-7 mt-2 font-display text-2xl font-bold text-[#2E3341]">Get in touch</h2>
                <div class="space-y-7">
                    <div class="flex items-start gap-4" x-data="{ qrOpen: false }">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#252525] text-white" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 11.5a8.5 8.5 0 0 1-12.6 7.45L3 20l1.08-4.18A8.5 8.5 0 1 1 20 11.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.2 8.1c.25 2.75 2.05 4.55 4.8 4.8l1.2-1.2 2.1 1v1.4c0 .75-.6 1.35-1.35 1.35A7.9 7.9 0 0 1 7.65 8.15C7.65 7.4 8.25 6.8 9 6.8h1.4l1 2.1-1.2 1.2"/></svg></span>
                        <div class="min-w-0 pt-0.5">
                            <h2 class="font-sans text-sm font-semibold text-slate-600">WhatsApp</h2>
                            <button type="button" @click="qrOpen = !qrOpen" :aria-expanded="qrOpen.toString()" aria-controls="contact-whatsapp-qr" class="mt-2 inline-flex min-h-10 items-center justify-center rounded-lg bg-ainchors-green px-5 py-2 font-sans text-sm font-semibold text-white transition hover:bg-[#2f9874] focus:outline-none focus:ring-2 focus:ring-ainchors-green/30"><span x-text="qrOpen ? 'Hide QR Code' : 'Show QR Code'">Show QR Code</span></button>
                            <div id="contact-whatsapp-qr" x-cloak x-show="qrOpen" x-transition class="mt-4">
                                <img src="{{ asset('assets/site/699e42092552e408a75e24ce.png') }}" alt="WhatsApp QR code for AINCHORS" class="h-auto w-36 rounded-lg bg-white">
                                <a href="https://wa.me/+61418802086" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex min-h-10 items-center justify-center rounded-lg border border-ainchors-green bg-white px-4 py-2 font-sans text-sm font-semibold text-ainchors-green transition hover:bg-[#E8FFF7] focus:outline-none focus:ring-2 focus:ring-ainchors-green/30">Open WhatsApp</a>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#252525] text-white" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m3 6 9 6 9-6M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z"/></svg></span>
                        <div class="min-w-0 pt-0.5"><h2 class="font-sans text-sm font-semibold text-slate-600">Email</h2><a href="https://mail.google.com/mail/?view=cm&amp;fs=1&amp;to=info@ainchors.com" target="_blank" rel="noopener noreferrer" class="mt-1 inline-block break-words font-sans text-sm font-semibold text-[#2E3341] transition hover:text-ainchors-green">info@ainchors.com</a></div>
                    </div>
                    <div class="flex items-start gap-4">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#252525] text-white" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.25 7-12a7 7 0 1 0-14 0c0 6.75 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg></span>
                        <div class="min-w-0 pt-0.5"><h2 class="font-sans text-sm font-semibold text-slate-600">Location</h2><address class="mt-1 space-y-3 font-sans text-sm not-italic leading-5 text-[#2E3341]"><p><strong>Australia:</strong> U803 5 Waterways Street Wentworth Point NSW 2127 Australia</p><p><strong>Malaysia:</strong> Level 13A, Wisma Mont Kiara, 1, Jalan Kiara, Mont Kiara, 50480 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur</p></address></div>
                    </div>
                </div>
            </aside>

            <section class="rounded-2xl border border-slate-300 bg-white p-5 shadow-md sm:p-6" aria-labelledby="contact-form-title">
                <p class="font-sans text-xs font-semibold uppercase tracking-[0.16em] text-ainchors-green">Contact AINCHORS</p>
                <h2 id="contact-form-title" class="mt-2 font-display text-2xl font-bold text-[#2E3341]">Send us a message</h2>
                <p class="mt-2 font-sans text-sm leading-5 text-[#2E3341]">Send us your enquiry, question, suggestion, concern or feedback using the form below.</p>

                @if (session('contact_success'))<div class="mt-4 rounded border border-ainchors-green/30 bg-[#E8FFF7] px-3 py-2 font-sans text-sm text-[#236f56]" role="status">{{ session('contact_success') }}</div>@endif
                @if ($errors->contact->any())<div class="mt-4 rounded border border-red-200 bg-red-50 px-3 py-2 font-sans text-sm text-red-700" role="alert">Please check the highlighted fields and try again.</div>@endif

                <form method="POST" action="{{ route('contact.submit') }}" class="mt-4 space-y-2.5">
                    @csrf
                    <input type="hidden" name="source" value="contact_page">
                    <div>
                        <label for="feedback_type" class="font-sans text-xs font-semibold text-[#2E3341]">Enquiry Type <span class="text-red-600">*</span></label>
                        <select id="feedback_type" name="feedback_type" required class="{{ $fieldClass }}"><option value="">Choose an enquiry type</option>@foreach ($enquiryTypes as $value => $label)<option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>@endforeach</select>
                        @error('feedback_type', 'contact')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="full_name" class="font-sans text-xs font-semibold text-[#2E3341]">Full Name <span class="text-red-600">*</span></label>
                        <input id="full_name" name="full_name" type="text" value="{{ old('full_name') }}" required autocomplete="name" class="{{ $fieldClass }}">
                        @error('full_name', 'contact')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="phone" class="font-sans text-xs font-semibold text-[#2E3341]">Phone</label><input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" class="{{ $fieldClass }}">@error('phone', 'contact')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="font-sans text-xs font-semibold text-[#2E3341]">Email <span class="text-red-600">*</span></label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="{{ $fieldClass }}">@error('email', 'contact')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="message" class="font-sans text-xs font-semibold text-[#2E3341]">Message <span class="text-red-600">*</span></label>
                        <textarea id="message" name="message" rows="3" required class="{{ $fieldClass }} min-h-[84px] resize-y">{{ old('message') }}</textarea>
                        @error('message', 'contact')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center rounded-lg bg-ainchors-green px-5 py-2 font-sans text-sm font-semibold text-white transition hover:bg-[#2f9874] focus:outline-none focus:ring-2 focus:ring-ainchors-green/30">Send Message</button>
                    <p class="text-center font-sans text-xs leading-5 text-slate-600"><a href="{{ route('privacy') }}" class="underline underline-offset-2 transition-colors hover:text-ainchors-green focus-visible:text-ainchors-green focus-visible:outline-none">Privacy Policy</a> <span aria-hidden="true">|</span> <a href="{{ route('terms') }}" class="underline underline-offset-2 transition-colors hover:text-ainchors-green focus-visible:text-ainchors-green focus-visible:outline-none">Terms of Service</a></p>
                </form>
            </section>
        </div>
    </section>
</div>
@endsection
