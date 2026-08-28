@extends('layouts.app')

@section('title', 'Join Us | AINCHORS')

@section('content')
    <section class="relative isolate overflow-hidden bg-[linear-gradient(135deg,#e8fff7_0%,#ffffff_48%,#c1eff5_100%)] px-5 py-14 sm:px-6 sm:py-20">
        <div aria-hidden="true" class="absolute -left-24 top-16 h-72 w-72 rounded-full bg-ainchors-card-green/60 blur-3xl"></div>
        <div aria-hidden="true" class="absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-ainchors-card-blue/60 blur-3xl"></div>

        <div class="relative mx-auto max-w-3xl">
            <h1 class="font-sans text-4xl font-bold leading-tight tracking-[-0.035em] text-ainchors-navy sm:text-5xl">Job Application</h1>

            <form method="POST" action="{{ route('job-applications.store') }}" enctype="multipart/form-data" class="mt-10 rounded-[1.25rem] border border-white/90 bg-ainchors-white/95 p-6 shadow-[0_24px_60px_rgba(46,51,65,0.14)] backdrop-blur sm:p-8" novalidate>
                @csrf

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="application-full-name" class="font-sans text-sm font-semibold text-ainchors-navy">Full Name *</label>
                        <input id="application-full-name" name="full_name" type="text" value="{{ old('full_name') }}" required autocomplete="name" class="mt-2 w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy shadow-sm transition focus:border-ainchors-green focus:outline-none focus:ring-4 focus:ring-ainchors-green/15" @error('full_name', 'application') aria-describedby="application-full-name-error" aria-invalid="true" @enderror>
                        @error('full_name', 'application')<p id="application-full-name-error" role="alert" class="mt-2 font-sans text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="application-email" class="font-sans text-sm font-semibold text-ainchors-navy">Email *</label>
                        <input id="application-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-2 w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy shadow-sm transition focus:border-ainchors-green focus:outline-none focus:ring-4 focus:ring-ainchors-green/15" @error('email', 'application') aria-describedby="application-email-error" aria-invalid="true" @enderror>
                        @error('email', 'application')<p id="application-email-error" role="alert" class="mt-2 font-sans text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="application-phone" class="font-sans text-sm font-semibold text-ainchors-navy">Phone *</label>
                        <input id="application-phone" name="phone" type="tel" value="{{ old('phone') }}" required autocomplete="tel" class="mt-2 w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy shadow-sm transition focus:border-ainchors-green focus:outline-none focus:ring-4 focus:ring-ainchors-green/15" @error('phone', 'application') aria-describedby="application-phone-error" aria-invalid="true" @enderror>
                        @error('phone', 'application')<p id="application-phone-error" role="alert" class="mt-2 font-sans text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="application-availability" class="font-sans text-sm font-semibold text-ainchors-navy">Interview Availability</label>
                        <input id="application-availability" name="interview_available_on" type="date" value="{{ old('interview_available_on') }}" class="mt-2 w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy shadow-sm transition focus:border-ainchors-green focus:outline-none focus:ring-4 focus:ring-ainchors-green/15" @error('interview_available_on', 'application') aria-describedby="application-availability-error" aria-invalid="true" @enderror>
                        <p class="mt-2 font-sans text-xs leading-5 text-ainchors-grey-dark">Confirm your availability (date, day, time)</p>
                        @error('interview_available_on', 'application')<p id="application-availability-error" role="alert" class="mt-2 font-sans text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-5">
                    <label for="application-position" class="font-sans text-sm font-semibold text-ainchors-navy">Job Position *</label>
                    <select id="application-position" name="job_position_id" required class="mt-2 w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy shadow-sm transition focus:border-ainchors-green focus:outline-none focus:ring-4 focus:ring-ainchors-green/15" @error('job_position_id', 'application') aria-describedby="application-position-error" aria-invalid="true" @enderror>
                        <option value="">Select a position</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" @selected((string) old('job_position_id') === (string) $position->id)>{{ $position->title }}</option>
                        @endforeach
                    </select>
                    @error('job_position_id', 'application')<p id="application-position-error" role="alert" class="mt-2 font-sans text-sm text-red-700">{{ $message }}</p>@enderror
                </div>

                <div class="mt-5" x-data="{ fileName: '' }">
                    <span class="font-sans text-sm font-semibold text-ainchors-navy">Resume *</span>
                    <label for="application-resume" class="mt-2 flex min-h-36 cursor-pointer flex-col items-center justify-center rounded-ainchors-card border border-dashed border-ainchors-grey-light/60 bg-ainchors-green-hero/35 px-5 text-center transition hover:border-ainchors-green hover:bg-ainchors-green-hero/65 focus-within:border-ainchors-green focus-within:ring-4 focus-within:ring-ainchors-green/15">
                        <svg class="h-8 w-8 text-ainchors-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 16V4m0 0L8 8m4-4 4 4M5 15.5v2.75A1.75 1.75 0 0 0 6.75 20h10.5A1.75 1.75 0 0 0 19 18.25V15.5" /></svg>
                        <span class="mt-3 font-sans text-sm font-semibold text-ainchors-navy" x-text="fileName || 'Choose a file'"></span>
                        <span class="mt-1 font-sans text-xs leading-5 text-ainchors-grey-dark">PDF, DOC/DOCX, XLS/CSV, JPG/JPEG, PNG, GIF · Maximum 25 MB</span>
                    </label>
                    <input id="application-resume" name="resume" type="file" required accept=".pdf,.doc,.docx,.xls,.csv,.jpg,.jpeg,.png,.gif,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,text/csv,image/jpeg,image/png,image/gif" class="sr-only" @change="fileName = $event.target.files[0]?.name || ''" @error('resume', 'application') aria-describedby="application-resume-error" aria-invalid="true" @enderror>
                    @error('resume', 'application')<p id="application-resume-error" role="alert" class="mt-2 font-sans text-sm text-red-700">{{ $message }}</p>@enderror
                </div>

                <div class="mt-5">
                    <label for="application-short-note" class="font-sans text-sm font-semibold text-ainchors-navy">Short Note About Yourself</label>
                    <textarea id="application-short-note" name="short_note" rows="5" maxlength="3000" class="mt-2 w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy shadow-sm transition focus:border-ainchors-green focus:outline-none focus:ring-4 focus:ring-ainchors-green/15" @error('short_note', 'application') aria-describedby="application-short-note-error" aria-invalid="true" @enderror>{{ old('short_note') }}</textarea>
                    <p class="mt-2 font-sans text-xs leading-5 text-ainchors-grey-dark">Tell us briefly why you’re interested in this role and what makes you a strong fit.</p>
                    @error('short_note', 'application')<p id="application-short-note-error" role="alert" class="mt-2 font-sans text-sm text-red-700">{{ $message }}</p>@enderror
                </div>

                <div class="mt-7 rounded-ainchors-button border border-ainchors-grey-light/30 bg-white px-4 py-4">
                    <div class="flex items-start gap-3">
                        <input id="application-recruitment-consent" name="recruitment_consent" type="checkbox" value="1" required @checked(old('recruitment_consent')) class="mt-0.5 h-4 w-4 shrink-0 rounded border-ainchors-grey-light text-ainchors-green focus:ring-ainchors-green" @error('recruitment_consent', 'application') aria-describedby="application-recruitment-consent-error" aria-invalid="true" @enderror>
                        <label for="application-recruitment-consent" class="cursor-pointer font-sans text-sm leading-6 text-ainchors-grey-dark">
                            I agree to AINCHORS’ <a href="{{ route('terms') }}" class="font-semibold text-ainchors-green underline decoration-ainchors-green/60 underline-offset-4 hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">Terms &amp; Conditions</a> and <a href="{{ route('privacy') }}" class="font-semibold text-ainchors-green underline decoration-ainchors-green/60 underline-offset-4 hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">Privacy Policy</a>, and consent to being contacted about my application, interview scheduling, and related updates.
                        </label>
                    </div>
                    @error('recruitment_consent', 'application')<p id="application-recruitment-consent-error" role="alert" class="mt-2 font-sans text-sm text-red-700">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="mt-7 inline-flex min-h-12 w-full items-center justify-center rounded-ainchors-button bg-ainchors-green px-6 py-3 font-sans text-ainchors-body font-semibold text-ainchors-white shadow-[0_10px_22px_rgba(55,173,130,0.22)] transition hover:-translate-y-0.5 hover:bg-ainchors-green/90 focus:outline-none focus:ring-4 focus:ring-ainchors-green/25 focus:ring-offset-2">
                    Send
                </button>
            </form>
        </div>
    </section>
@endsection
