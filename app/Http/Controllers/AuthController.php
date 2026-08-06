<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'USER_LOGIN',
                'description' => "User logged in: " . (Auth::user()->username)
            ]);

            $targetRoute = Auth::user()->isAdmin() ? 'dashboard' : 'pos.index';
            return redirect()->route($targetRoute);
        }

        return back()->withErrors([
            'username' => 'Invalid username or password credentials.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
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

    public function verifyEmail($token)
    {
        $user = User::where('email_verification_token', $token)->first();

        if (!$user) {
            return redirect()->route('login')->withErrors(['token' => 'Invalid or expired email verification token.']);
        }

        $user->update([
            'email_verified_at' => now(),
            'email_verification_token' => null,
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'EMAIL_VERIFIED',
            'description' => "User account email verified successfully: {$user->username}"
        ]);

        return redirect()->route('login')->with('status', 'Email verified successfully! You can now log in.');
    }
}
