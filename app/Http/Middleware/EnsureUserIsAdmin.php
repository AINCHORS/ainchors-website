<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Reject every unauthenticated or non-administrator request with a 403.
     * Routes using this alias should also apply Laravel's `auth` middleware
     * when a login redirect is the desired guest experience.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->isAdmin(), 403);

        return $next($request);
    }
}
