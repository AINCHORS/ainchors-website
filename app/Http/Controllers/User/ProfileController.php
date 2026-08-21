<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.profile', ['user' => $request->user()]);
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
            'full_name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
        ], [
            'email.in' => 'The administrator email is controlled by AINCHORS_ADMIN_EMAIL and cannot be changed from the website profile.',
        ]);

        $user->update($validated);

        return back()->with('profile_success', 'Your profile has been updated.');
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
