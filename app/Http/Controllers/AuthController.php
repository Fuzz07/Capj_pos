<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Limit failed attempts per username+IP, then lock out (both configurable
        // in Admin Panel -> Settings)
        $maxAttempts = max(1, \App\Models\Setting::getInt('login_max_attempts', 3));
        $lockoutSeconds = max(60, \App\Models\Setting::getInt('login_lockout_minutes', 10) * 60);

        $throttleKey = Str::transliterate(strtolower($credentials['username']) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $minutes = (int) ceil(RateLimiter::availableIn($throttleKey) / 60);
            return back()->withErrors([
                'username' => "Too many failed login attempts. Please wait {$minutes} " . ($minutes === 1 ? 'minute' : 'minutes') . " before trying again.",
            ])->onlyInput('username');
        }

        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $user = Auth::user();

            // Check whether an active session already exists for this user.
            // We verify against the database sessions table so stale/expired
            // sessions (closed tabs that never sent a beacon) are ignored.
            $hasActiveSession = $user->session_token !== null
                && \Illuminate\Support\Facades\DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->where('id', '!=', $request->session()->getId())
                    ->exists();

            if ($hasActiveSession) {
                // ── Window 2: another session is already active ──
                // Store a deliberately mismatched token so SingleSessionMiddleware
                // kicks this session out on the very first authenticated request.
                // Window 1 (the original) is completely unaffected.
                $request->session()->put('auth_session_token', 'blocked_' . Str::random(16));
            } else {
                // ── Window 1 / fresh login ──
                // Issue a new token and record it.
                $token = Str::random(64);
                $user->update(['session_token' => $token]);
                $request->session()->put('auth_session_token', $token);

                ActivityLog::create([
                    'user_id' => $user->id,
                    'action' => 'USER_LOGIN',
                    'description' => "User logged in: " . $user->username,
                ]);
            }

            $targetRoute = $user->isAdmin() ? 'dashboard' : 'pos.index';
            return redirect()->route($targetRoute);
        }

        RateLimiter::hit($throttleKey, $lockoutSeconds);
        $remaining = RateLimiter::remaining($throttleKey, $maxAttempts);
        $lockMinutes = (int) round($lockoutSeconds / 60);

        return back()->withErrors([
            'username' => $remaining > 0
                ? "Invalid username or password credentials. You have {$remaining} " . ($remaining === 1 ? 'attempt' : 'attempts') . " remaining."
                : "Too many failed login attempts. Login is now locked — please wait {$lockMinutes} " . ($lockMinutes === 1 ? 'minute' : 'minutes') . " before trying again.",
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            // Clear the session token so the next login can proceed normally.
            Auth::user()->update(['session_token' => null]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'USER_LOGOUT',
                'description' => "User logged out: " . (Auth::user()->username)
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'No user found with that email address.']);
        }

        // BLOCK unverified emails
        if (empty($user->email_verified_at)) {
            return back()->withErrors(['email' => 'This email address is unverified. Please contact the administrator to verify your account first.']);
        }

        // Generate 6-digit OTP code
        $otp = (string) rand(100000, 999999);
        $user->update(['email_verification_token' => $otp]);

        // Send OTP email
        $subject = "Your OTP Password Reset Code - CAPTAiN J";
        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px; background-color: #fcfcfc;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <h2 style='color: #dc3545; margin: 0;'>CAPTAiN J</h2>
                    <p style='color: #666; margin: 5px 0 0; font-size: 14px;'>Takoyaki & Milktea Shop</p>
                </div>
                
                <h3 style='color: #333;'>Hello, " . htmlspecialchars($user->full_name ?: $user->username) . "!</h3>
                <p>You have requested to reset your password. Use the verification OTP code below to proceed:</p>
                
                <div style='text-align: center; margin: 35px 0;'>
                    <span style='display: inline-block; padding: 15px 35px; background-color: #f1f5f9; color: #0057a3; font-size: 32px; font-weight: bold; letter-spacing: 6px; border-radius: 8px; border: 1px solid #e2e8f0; font-family: monospace;'>{$otp}</span>
                </div>

                <p style='font-size: 13px; color: #666; text-align: center;'>This OTP code is valid for 5 minutes. Do not share this code with anyone.</p>
                
                <div style='text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; color: #888; font-size: 12px;'>
                    <p style='margin: 0;'>CAPTAiN J POS System</p>
                    <p style='margin: 5px 0 0;'>© " . date('Y') . " CAPTAiN J. All rights reserved.</p>
                </div>
            </div>
        ";

        $sent = \App\Services\NotificationService::sendViaGmail($subject, $body, $user->email, $user->full_name ?: $user->username);

        if ($sent) {
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'RESET_OTP_SENT',
                'description' => "Sent password reset OTP code to verified user: {$user->username} ({$user->email})"
            ]);

            // Show OTP entry page
            return view('auth.verify-otp', ['email' => $user->email])->with('status', 'An OTP verification code has been sent to your verified Gmail!');
        }

        return back()->withErrors(['email' => 'Failed to send OTP code email. Please check configuration.']);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)
                    ->where('email_verification_token', $request->otp)
                    ->first();

        if (!$user) {
            return view('auth.verify-otp', ['email' => $request->email])->withErrors(['otp' => 'Invalid OTP code. Please check your email and try again.']);
        }

        // Generate secure 64-character reset token
        $token = Str::random(64);
        $user->update(['email_verification_token' => $token]);

        return redirect()->route('password.reset', ['token' => $token]);
    }

    public function showResetPassword($token)
    {
        $user = User::where('email_verification_token', $token)->firstOrFail();
        return view('auth.reset-password', compact('token', 'user'));
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::where('email_verification_token', $request->token)->first();

        if (!$user) {
            return back()->withErrors(['token' => 'Invalid or expired token.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'email_verification_token' => null,
        ]);

        return redirect()->route('login')->with('status', 'Password reset successful! Please log in.');
    }

    public function showVerifyEmail(Request $request)
    {
        return view('auth.verify-account', ['email' => $request->query('email', '')]);
    }

    public function verifyEmailOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)
                    ->whereNull('email_verified_at')
                    ->where('email_verification_token', $request->otp)
                    ->first();

        if (!$user) {
            return view('auth.verify-account', ['email' => $request->email])
                ->withErrors(['otp' => 'Invalid OTP code. Please check your email and try again.']);
        }

        if ($user->email_verification_expires_at && now()->greaterThan($user->email_verification_expires_at)) {
            return view('auth.verify-account', ['email' => $request->email])
                ->withErrors(['otp' => 'This OTP code has expired. Please ask your administrator to resend a new verification code.']);
        }

        $user->update([
            'email_verified_at' => now(),
            'email_verification_token' => null,
            'email_verification_expires_at' => null,
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'EMAIL_VERIFIED',
            'description' => "User account email verified successfully via OTP: {$user->username}"
        ]);

        // Clear any active session so verification never lands on the dashboard —
        // the user must always sign in from the login page after verifying.
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login')->with('status', 'Email verified successfully! Please log in with your username and password.');
    }
}
