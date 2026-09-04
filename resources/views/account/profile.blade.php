@extends('layouts.app')

@section('title', 'My Profile | AINCHORS')

@section('content')
<section class="bg-gradient-to-br from-ainchors-green-hero via-ainchors-white to-ainchors-card-blue/35 py-10 sm:py-14">
    <div class="mx-auto max-w-ainchors-container px-4 sm:px-6">
        <div class="mb-8 max-w-3xl">
            <span class="font-sans text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">Your account</span>
            <h1 class="mt-3 font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">My Profile</h1>
            <p class="mt-3 font-sans text-ainchors-body text-ainchors-grey-dark">Manage the details stored for your AINCHORS account.</p>
        </div>

        @if ($user->must_change_password)
            <div role="alert" class="mb-8 rounded-ainchors-card border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900 shadow-sm">
                <p class="font-semibold">Password change required</p>
                <p class="mt-1 leading-relaxed">An administrator reset your password. Please use the temporary password as your current password below, then choose your own new password before continuing.</p>
            </div>
        @endif

        <nav aria-label="Account navigation" class="mb-8 flex flex-wrap gap-2">
            <a href="{{ route('profile') }}" aria-current="page" class="rounded-ainchors-button bg-ainchors-green px-4 py-2 font-sans text-sm font-semibold text-ainchors-white">My Profile</a>
            <a href="{{ route('my-courses') }}" class="rounded-ainchors-button border border-ainchors-green/40 bg-ainchors-white px-4 py-2 font-sans text-sm font-semibold text-ainchors-green transition hover:bg-ainchors-green hover:text-ainchors-white">My Courses</a>
            <a href="{{ route('purchase-history') }}" class="rounded-ainchors-button border border-ainchors-green/40 bg-ainchors-white px-4 py-2 font-sans text-sm font-semibold text-ainchors-green transition hover:bg-ainchors-green hover:text-ainchors-white">Purchase History</a>
        </nav>

        <div class="grid gap-6 lg:grid-cols-2">
            <article class="rounded-ainchors-card border border-ainchors-grey-light/25 bg-ainchors-white p-6 shadow-lg shadow-ainchors-navy/5 sm:p-8">
                <h2 class="font-heading text-2xl font-bold text-ainchors-navy">Personal details</h2>
                <p class="mt-2 font-sans text-sm leading-relaxed text-ainchors-grey-dark">Keep your identity, contact details and home address up to date.</p>

                @if (session('profile_success'))
                    <p role="status" class="mt-5 rounded-ainchors-button border border-ainchors-green/35 bg-ainchors-green-hero px-4 py-3 font-sans text-sm text-ainchors-navy">{{ session('profile_success') }}</p>
                @endif

                <form method="POST" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
                    @csrf
                    @method('PATCH')
                    @include('account.partials.personal-details-fields', ['user' => $user])
                    <div class="space-y-2">
                        <label for="profile-email" class="block font-sans text-sm font-semibold text-ainchors-navy">Email</label>
                        <input id="profile-email" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email" required @error('email') aria-describedby="profile-email-error" aria-invalid="true" @enderror class="block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-ainchors-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                        @error('email')<p id="profile-email-error" role="alert" class="font-sans text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    @if ($user->created_at)
                        <p class="font-sans text-sm text-ainchors-grey-dark">Account created {{ $user->created_at->format('j M Y') }}.</p>
                    @endif
                    <x-button variant="primary" type="submit">Save Profile</x-button>
                </form>
            </article>

            <article class="rounded-ainchors-card border border-ainchors-grey-light/25 bg-ainchors-white p-6 shadow-lg shadow-ainchors-navy/5 sm:p-8">
                <h2 class="font-heading text-2xl font-bold text-ainchors-navy">Change Password</h2>
                <p class="mt-2 font-sans text-sm leading-relaxed text-ainchors-grey-dark">Use your current password to confirm this change.</p>

                @if (session('password_success'))
                    <p role="status" class="mt-5 rounded-ainchors-button border border-ainchors-green/35 bg-ainchors-green-hero px-4 py-3 font-sans text-sm text-ainchors-navy">{{ session('password_success') }}</p>
                @endif

                <form method="POST" action="{{ route('profile.password.update') }}" class="mt-6 space-y-5">
                    @csrf
                    @method('PUT')
                    @include('auth.partials.password-field', [
                        'id' => 'current-password',
                        'name' => 'current_password',
                        'label' => 'Current Password',
                        'autocomplete' => 'current-password',
                    ])
                    @include('auth.partials.password-field', [
                        'id' => 'new-password',
                        'name' => 'password',
                        'label' => 'New Password',
                        'autocomplete' => 'new-password',
                    ])
                    @include('auth.partials.password-field', [
                        'id' => 'new-password-confirmation',
                        'name' => 'password_confirmation',
                        'label' => 'Confirm New Password',
                        'autocomplete' => 'new-password',
                        'errorName' => 'password_confirmation',
                    ])
                    <x-button variant="primary" type="submit">Update Password</x-button>
                </form>
            </article>
        </div>
    </div>
</section>
@endsection
