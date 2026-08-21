<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['status'] = 'active';

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();

        if ($user->isAdmin() && ! $user->isAuthorizedAdmin()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'This administrator account is not authorized for the AINCHORS administration portal.']);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        if ($user->isAuthorizedAdmin()) {
            return redirect($this->adminDestination($request));
        }

        return redirect()->intended(route('my-courses'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $wasAdmin = $request->user()?->isAuthorizedAdmin() ?? false;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $wasAdmin
            ? redirect()->route('login')
            : redirect()->route('home');
    }

    private function adminDestination(Request $request): string
    {
        $intended = $request->session()->pull('url.intended');

        if (is_string($intended) && $intended !== '') {
            $path = parse_url($intended, PHP_URL_PATH);

            if (is_string($path) && ($path === '/admin' || str_starts_with($path, '/admin/'))) {
                $query = parse_url($intended, PHP_URL_QUERY);

                return $path.(is_string($query) && $query !== '' ? '?'.$query : '');
            }
        }

        return route('admin.dashboard');
    }
}
