<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Rules\PhoneForCountry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.profile', [
            'user' => $request->user(),
            'countries' => config('ainchors.countries', []),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $emailRules = [
            'required',
            'email',
            'max:255',
            Rule::unique('users', 'email')->ignore($user->id),
        ];

        if ($user->isAdmin()) {
            $configuredEmail = strtolower(trim((string) config('ainchors.admin.email', '')));
            $emailRules[] = Rule::in([$configuredEmail]);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'email' => $emailRules,
            'country' => ['required', 'string', Rule::in(config('ainchors.countries', []))],
            'phone' => ['required', 'string', 'max:50', new PhoneForCountry((string) $request->input('country'))],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:30'],
        ], [
            'email.in' => 'The administrator email is controlled by AINCHORS_ADMIN_EMAIL and cannot be changed from the website profile.',
        ]);

        $user->update([
            ...$validated,
            'full_name' => trim($validated['first_name'].' '.$validated['last_name']),
        ]);

        return back()->with('profile_success', 'Your profile has been updated.');
    }

    public function complete(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('profileCompletion', [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', Rule::in(config('ainchors.countries', []))],
            'phone' => ['required', 'string', 'max:50', new PhoneForCountry((string) $request->input('country'))],
        ]);

        $request->user()->update([
            ...$validated,
            'full_name' => trim($validated['first_name'].' '.$validated['last_name']),
        ]);

        return back()->with('profile_completion_success', 'Your profile details have been saved.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return back()->with('password_success', 'Your password has been updated.');
    }
}
