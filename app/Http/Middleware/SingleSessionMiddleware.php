<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * SingleSessionMiddleware
 *
 * Enforces one active login session per user, across all devices and browsers.
 *
 * How it works:
 *  - On login, the user's `session_token` column is updated to a fresh random
 *    string, and that same string is stored in the PHP session under the key
 *    'auth_session_token'.
 *  - On every subsequent authenticated request this middleware compares the
 *    value stored in the session against the value stored in the database.
 *  - If they differ (because the user logged in from another device/browser,
 *    which rotated the DB token), the old session is invalidated and the user
 *    is redirected to /login silently — no error banner needed.
 */
class SingleSessionMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (Auth::check()) {
            $user              = Auth::user();
            $sessionToken      = $request->session()->get('auth_session_token');
            $dbToken           = $user->session_token;

            // If there's no token stored yet (e.g. existing sessions from before
            // this feature was deployed), just write the current DB value so the
            // user isn't kicked out on the first request.
            if ($sessionToken === null && $dbToken !== null) {
                $request->session()->put('auth_session_token', $dbToken);
                return $next($request);
            }

            if ($sessionToken !== $dbToken) {
                // Another login has taken over — invalidate this session.
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('status', 'You have been logged out because your account was accessed from another device or browser.');
            }
        }

        return $next($request);
    }
}
