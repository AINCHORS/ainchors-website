@extends('layouts.auth')

@section('title', 'Register | AINCHORS')

@section('content')
<section class="flex flex-1 items-center justify-center px-4 py-10 sm:px-6 sm:py-14">
    <div class="w-full max-w-lg rounded-ainchors-card border border-ainchors-grey-light/25 bg-ainchors-white p-6 shadow-xl shadow-ainchors-navy/10 sm:p-9">
        <span class="font-sans text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">AINCHORS Learning</span>
        <h1 class="mt-3 font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">Create your account</h1>
        <p class="mt-3 font-sans text-sm leading-relaxed text-ainchors-grey-dark">Register to purchase and access protected self-learning courses.</p>

        @if ($errors->any())
            <div role="alert" class="mt-5 rounded-ainchors-button border border-red-200 bg-red-50 px-4 py-3 font-sans text-sm text-red-800">
                <p class="font-semibold">Please check the highlighted field{{ $errors->count() === 1 ? '' : 's' }}.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="mt-7 space-y-5">
            @csrf
            <div class="space-y-2">
                <label for="name" class="block font-sans text-sm font-semibold text-ainchors-navy">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus @error('name') aria-describedby="name-error" aria-invalid="true" @enderror class="block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-ainchors-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                @error('name')<p id="name-error" role="alert" class="font-sans text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div class="space-y-2">
                <label for="email" class="block font-sans text-sm font-semibold text-ainchors-navy">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required @error('email') aria-describedby="email-error" aria-invalid="true" @enderror class="block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-ainchors-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                @error('email')<p id="email-error" role="alert" class="font-sans text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            @include('auth.partials.password-field', [
                'id' => 'register-password',
                'name' => 'password',
                'label' => 'Password',
                'autocomplete' => 'new-password',
            ])

            @include('auth.partials.password-field', [
                'id' => 'register-password-confirmation',
                'name' => 'password_confirmation',
                'label' => 'Confirm Password',
                'autocomplete' => 'new-password',
                'errorName' => 'password_confirmation',
            ])

            <div>
                <label for="terms" class="flex cursor-pointer items-start gap-3 font-sans text-sm leading-relaxed text-ainchors-grey-dark">
                    <input id="terms" name="terms" type="checkbox" value="1" @checked(old('terms')) @error('terms') aria-describedby="terms-error" aria-invalid="true" @enderror class="mt-0.5 h-4 w-4 shrink-0 rounded border-ainchors-grey-light text-ainchors-green focus:ring-ainchors-green">
                    <span>I agree to the <a href="{{ route('terms') }}" class="font-semibold text-ainchors-green underline-offset-2 hover:underline">Terms &amp; Conditions</a> and <a href="{{ route('privacy') }}" class="font-semibold text-ainchors-green underline-offset-2 hover:underline">Privacy Policy</a>.</span>
                </label>
                @error('terms')<p id="terms-error" role="alert" class="mt-2 font-sans text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <x-button variant="primary" type="submit" class="w-full">Register</x-button>
        </form>

        <p class="mt-6 text-center font-sans text-sm text-ainchors-grey-dark">Already registered? <a href="{{ route('login') }}" class="font-semibold text-ainchors-green transition hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">Login</a></p>
    </div>
</section>
@endsection
