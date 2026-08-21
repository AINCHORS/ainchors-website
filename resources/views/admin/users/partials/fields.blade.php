@php
    $editing = isset($user) && $user;
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="user-full-name" class="block text-sm font-semibold text-ainchors-navy">Full name</label>
        <input id="user-full-name" name="full_name" type="text" value="{{ old('full_name', $editing ? $user->full_name : '') }}" autocomplete="name" required @error('full_name') aria-invalid="true" aria-describedby="user-full-name-error" @enderror class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        @error('full_name')<p id="user-full-name-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="user-email" class="block text-sm font-semibold text-ainchors-navy">Email address</label>
        <input id="user-email" name="email" type="email" value="{{ old('email', $editing ? $user->email : '') }}" autocomplete="email" required @error('email') aria-invalid="true" aria-describedby="user-email-error" @enderror class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        @error('email')<p id="user-email-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>

    <div>
        <span class="block text-sm font-semibold text-ainchors-navy">Role</span>
        <p class="mt-2 rounded-ainchors-button border border-ainchors-grey-light/25 bg-slate-50 px-3.5 py-2.5 text-sm text-ainchors-grey-dark">{{ $editing && $user->isAdmin() ? 'Administrator (single protected account)' : 'User' }}</p>
    </div>

    @if (! $editing)
        <div>
            <label for="user-status" class="block text-sm font-semibold text-ainchors-navy">Initial account status</label>
            <select id="user-status" name="status" required class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                <option value="blocked" @selected(old('status') === 'blocked')>Blocked</option>
            </select>
            @error('status')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="user-password" class="block text-sm font-semibold text-ainchors-navy">Temporary password</label>
            <input id="user-password" name="password" type="password" autocomplete="new-password" required @error('password') aria-invalid="true" aria-describedby="user-password-error" @enderror class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
            @error('password')<p id="user-password-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="user-password-confirmation" class="block text-sm font-semibold text-ainchors-navy">Confirm temporary password</label>
            <input id="user-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        </div>
    @endif
</div>
