@extends('layouts.auth')

@section('title', 'Forgot Password | AINCHORS')

@section('content')
<section class="flex flex-1 items-center justify-center px-4 py-10 sm:px-6 sm:py-14">
    <div class="w-full max-w-md rounded-ainchors-card border border-ainchors-grey-light/25 bg-ainchors-white p-6 shadow-xl shadow-ainchors-navy/10 sm:p-9">
        <span class="font-sans text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">AINCHORS Account</span>
        <h1 class="mt-3 font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">Forgot Password?</h1>
        <p class="mt-3 font-sans text-sm leading-relaxed text-ainchors-grey-dark">Enter your email address and we will send a password reset link if the account exists.</p>

        @if (session('status'))
            <p role="status" class="mt-5 rounded-ainchors-button border border-ainchors-green/35 bg-ainchors-green-hero px-4 py-3 font-sans text-sm leading-relaxed text-ainchors-navy">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">
            @csrf
            <div class="space-y-2">
                <label for="forgot-email" class="block font-sans text-sm font-semibold text-ainchors-navy">Email</label>
                <input id="forgot-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus @error('email') aria-describedby="forgot-email-error" aria-invalid="true" @enderror class="block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-ainchors-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                @error('email')<p id="forgot-email-error" role="alert" class="font-sans text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <x-button variant="primary" type="submit" class="w-full">Email Password Reset Link</x-button>
        </form>

        <p class="mt-6 text-center font-sans text-sm text-ainchors-grey-dark"><a href="{{ route('login') }}" class="font-semibold text-ainchors-green transition hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">Back to Login</a></p>
    </div>
</section>
@endsection
