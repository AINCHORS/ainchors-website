@extends('layouts.auth')

@section('title', 'Login | AINCHORS')

@section('content')
<section class="flex flex-1 items-center justify-center px-4 py-10 sm:px-6 sm:py-14">
    <div class="w-full max-w-md rounded-ainchors-card border border-ainchors-grey-light/25 bg-ainchors-white p-6 shadow-xl shadow-ainchors-navy/10 sm:p-9">
        <span class="font-sans text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">AINCHORS Learning</span>
        <h1 class="mt-3 font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">Welcome back</h1>
        <p class="mt-3 font-sans text-sm leading-relaxed text-ainchors-grey-dark">Log in to continue to checkout or access your courses.</p>

        @if (session('status'))
            <p role="status" class="mt-5 rounded-ainchors-button border border-ainchors-green/35 bg-ainchors-green-hero px-4 py-3 font-sans text-sm text-ainchors-navy">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div role="alert" class="mt-5 rounded-ainchors-button border border-red-200 bg-red-50 px-4 py-3 font-sans text-sm text-red-800">
                <p class="font-semibold">Please check the highlighted field{{ $errors->count() === 1 ? '' : 's' }}.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5">
            @csrf
            <div class="space-y-2">
                <label for="email" class="block font-sans text-sm font-semibold text-ainchors-navy">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus @error('email') aria-describedby="email-error" aria-invalid="true" @enderror class="block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-ainchors-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy shadow-sm outline-none transition placeholder:text-ainchors-grey-light focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                @error('email')<p id="email-error" role="alert" class="font-sans text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            @include('auth.partials.password-field', [
                'id' => 'login-password',
                'name' => 'password',
                'label' => 'Password',
                'autocomplete' => 'current-password',
            ])

            <div class="flex flex-wrap items-center justify-between gap-3">
                <label for="remember" class="flex cursor-pointer items-center gap-2 font-sans text-sm text-ainchors-grey-dark">
                    <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember')) class="h-4 w-4 rounded border-ainchors-grey-light text-ainchors-green focus:ring-ainchors-green">
                    Remember me
                </label>
                <a href="{{ route('password.request') }}" class="font-sans text-sm font-semibold text-ainchors-green transition hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">Forgot Password?</a>
            </div>

            <x-button variant="primary" type="submit" class="w-full">Login</x-button>
        </form>

        <p class="mt-6 text-center font-sans text-sm text-ainchors-grey-dark">New to AINCHORS? <a href="{{ route('register') }}" class="font-semibold text-ainchors-green transition hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">Create an account</a></p>
    </div>
</section>
@endsection
