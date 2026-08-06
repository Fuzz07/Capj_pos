<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('username')->paginate(10);
        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:100|unique:users,username',
            'full_name' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:255',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,staff',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        
        if (!empty($validated['email'])) {
            $validated['email_verified_at'] = null;
            $validated['email_verification_token'] = \Illuminate\Support\Str::random(64);
        } else {
            $validated['email_verified_at'] = now();
        }

        $user = User::create($validated);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'USER_CREATED',
            'description' => "Created new user: {$user->username} ({$user->role})"
        ]);

        if (!empty($user->email)) {
            $this->sendVerification($user);
            return redirect()->route('users.index')->with('success', "Verification email sent successfully to " . $user->email);
        }

        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100', Rule::unique('users')->ignore($user->id)],
            'full_name' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:255',
            'role' => 'required|in:admin,staff',
            'password' => 'nullable|string|min:6',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'USER_UPDATED',
            'description' => "Updated user: {$user->username}"
        ]);

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account while logged in.');
        }

        $username = $user->username;
        $user->delete();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'USER_DELETED',
            'description' => "Deleted user: {$username}"
        ]);

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    public function sendVerification(User $user)
    {
        if (empty($user->email)) {
            return back()->with('error', 'This user does not have an email address configured.');
        }

        // Always generate a fresh verification token on send/resend
        $user->email_verification_token = \Illuminate\Support\Str::random(64);
        $user->save();

        // Send verification email using our robust NotificationService method
        $verifyLink = route('email.verify', ['token' => $user->email_verification_token]);
        $subject = "Verify your account email - CAPTAiN J";
        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px; background-color: #ffffff;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <h2 style='color: #dc3545; margin: 0;'>CAPTAiN J</h2>
                    <p style='color: #666; margin: 5px 0 0; font-size: 14px;'>Takoyaki & Milktea Shop</p>
                </div>
                
                <h3 style='color: #333;'>Hello, " . htmlspecialchars($user->full_name ?: $user->username) . "!</h3>
                <p>An administrator has requested verification of your staff email address for your CAPTAiN J account.</p>
                <p>Please confirm and verify your email address by clicking the button below:</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$verifyLink}' style='display: inline-block; padding: 12px 24px; background-color: #0057a3; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;'>Verify My Email Address</a>
                </div>

                <p style='font-size: 12px; color: #666;'>If the button above does not work, copy and paste the following link into your web browser:</p>
                <p style='font-size: 12px; color: #0057a3; word-break: break-all;'>{$verifyLink}</p>
                
                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #888; font-size: 12px;'>
                    <p style='margin: 0;'>CAPTAiN J POS System</p>
                    <p style='margin: 5px 0 0;'>© " . date('Y') . " CAPTAiN J. All rights reserved.</p>
                </div>
            </div>
        ";

        try {
            \App\Services\NotificationService::sendViaGmail($subject, $body, $user->email, $user->full_name ?: $user->username);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send verification email: " . $e->getMessage());
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'VERIFICATION_SENT',
            'description' => "Sent email verification link to user: {$user->username} ({$user->email})"
        ]);

        return back()->with('success', "Verification email sent successfully to " . $user->email);
    }
}
