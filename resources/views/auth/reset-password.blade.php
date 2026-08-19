@extends('layouts.auth')

@section('title', 'Reset Password | AINCHORS')

@section('content')
<section class="flex flex-1 items-center justify-center px-4 py-10 sm:px-6 sm:py-14">
    <div class="w-full max-w-md rounded-ainchors-card border border-ainchors-grey-light/25 bg-ainchors-white p-6 shadow-xl shadow-ainchors-navy/10 sm:p-9">
        <span class="font-sans text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">AINCHORS Account</span>
        <h1 class="mt-3 font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">Reset Password</h1>
        <p class="mt-3 font-sans text-sm leading-relaxed text-ainchors-grey-dark">Choose a new password for your AINCHORS account.</p>

        @if ($errors->any())
            <div role="alert" class="mt-5 rounded-ainchors-button border border-red-200 bg-red-50 px-4 py-3 font-sans text-sm text-red-800">
                <p class="font-semibold">Please check the highlighted field{{ $errors->count() === 1 ? '' : 's' }}.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="mt-7 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="space-y-2">
                <label for="reset-email" class="block font-sans text-sm font-semibold text-ainchors-navy">Email</label>
                <input id="reset-email" name="email" type="email" value="{{ old('email', $request->email) }}" autocomplete="email" required autofocus @error('email') aria-describedby="reset-email-error" aria-invalid="true" @enderror class="block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-ainchors-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                @error('email')<p id="reset-email-error" role="alert" class="font-sans text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            @include('auth.partials.password-field', [
                'id' => 'reset-password',
                'name' => 'password',
                'label' => 'New Password',
                'autocomplete' => 'new-password',
            ])

            @include('auth.partials.password-field', [
                'id' => 'reset-password-confirmation',
                'name' => 'password_confirmation',
                'label' => 'Confirm New Password',
                'autocomplete' => 'new-password',
                'errorName' => 'password_confirmation',
            ])

            <x-button variant="primary" type="submit" class="w-full">Reset Password</x-button>
        </form>
    </div>
</section>
@endsection
