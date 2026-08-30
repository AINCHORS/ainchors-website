@auth
    @php
        $profileCompletionErrors = $errors->getBag('profileCompletion');
        $shouldOpenProfileCompletion = ! auth()->user()->isAdmin()
            && ! auth()->user()->hasBasicProfile()
            && (session('show_profile_completion') || $profileCompletionErrors->any());
        $fieldClass = 'block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3 py-2 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25';
    @endphp

    @if ($shouldOpenProfileCompletion)
        <div
            x-data="{ open: true }"
            x-show="open"
            x-cloak
            x-on:keydown.escape.window="open = false"
            class="fixed inset-0 z-[90] flex items-center justify-center overflow-y-auto bg-slate-950/55 p-4 sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="profile-completion-title"
        >
            <div x-on:click.outside="open = false" class="my-auto w-full max-w-[760px] rounded-ainchors-card bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-5 border-b border-ainchors-navy/10 px-5 py-3 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">One quick step</p>
                        <h2 id="profile-completion-title" class="mt-0.5 font-heading text-xl font-bold text-ainchors-navy sm:text-2xl">Complete your profile</h2>
                        <p class="mt-0.5 max-w-xl text-sm text-ainchors-grey-dark">Add a few basic details to keep your account information up to date. You can complete the rest later from Profile.</p>
                    </div>
                    <button type="button" x-on:click="open = false" class="rounded-full p-2 text-ainchors-grey-dark transition hover:bg-ainchors-mint hover:text-ainchors-navy" aria-label="Complete profile later">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('profile.complete') }}" class="px-5 py-4 sm:px-6">
                    @csrf
                    @method('PATCH')

                    @if ($profileCompletionErrors->any())
                        <div role="alert" class="mb-3 rounded-ainchors-button border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800">Please check the highlighted profile fields.</div>
                    @endif

                    <div class="grid gap-x-4 gap-y-3 sm:grid-cols-2">
                        <div><label for="profile-completion-first-name" class="mb-1 block text-sm font-semibold text-ainchors-navy">First Name</label><input id="profile-completion-first-name" name="first_name" type="text" value="{{ old('first_name', auth()->user()->first_name) }}" autocomplete="given-name" required class="{{ $fieldClass }}">@error('first_name', 'profileCompletion')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                        <div><label for="profile-completion-last-name" class="mb-1 block text-sm font-semibold text-ainchors-navy">Last Name</label><input id="profile-completion-last-name" name="last_name" type="text" value="{{ old('last_name', auth()->user()->last_name) }}" autocomplete="family-name" required class="{{ $fieldClass }}">@error('last_name', 'profileCompletion')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                        <div><label for="profile-completion-phone" class="mb-1 block text-sm font-semibold text-ainchors-navy">Phone Number</label><input id="profile-completion-phone" name="phone" type="tel" value="{{ old('phone', auth()->user()->phone) }}" autocomplete="tel" inputmode="tel" pattern="[0-9+() .-]+" oninput="this.value = this.value.replace(/[^0-9+() .-]/g, '')" placeholder="e.g. +60 12 345 6789" required class="{{ $fieldClass }}">@error('phone', 'profileCompletion')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                        <div><label for="profile-completion-country" class="mb-1 block text-sm font-semibold text-ainchors-navy">Country</label><select id="profile-completion-country" name="country" autocomplete="country-name" required class="{{ $fieldClass }}"><option value="">Select your country</option>@foreach (config('ainchors.countries', []) as $country)<option value="{{ $country }}" @selected(old('country', auth()->user()->country) === $country)>{{ $country }}</option>@endforeach</select>@error('country', 'profileCompletion')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                    </div>

                    <div class="mt-4 flex flex-col-reverse gap-2 border-t border-ainchors-navy/10 pt-3 sm:flex-row sm:justify-end">
                        <button type="button" x-on:click="open = false" class="rounded-ainchors-button border border-ainchors-navy/15 px-5 py-2 text-sm font-semibold text-ainchors-grey-dark transition hover:border-ainchors-green hover:text-ainchors-green">Maybe Later</button>
                        <button type="submit" class="rounded-ainchors-button bg-ainchors-green px-5 py-2 text-sm font-semibold text-white transition hover:bg-ainchors-mint hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">Save Profile</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endauth
