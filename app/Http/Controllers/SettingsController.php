<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::values();

        $mail = [
            'mailer' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'username' => config('mail.mailers.smtp.username'),
            'password_set' => !empty(config('mail.mailers.smtp.password')),
            'encryption' => config('mail.mailers.smtp.scheme') ?: config('mail.mailers.smtp.encryption'),
            'from' => config('mail.from.address'),
            'gmail_api' => !empty(config('pos.gmail.refresh_token')),
        ];

        return view('settings.index', compact('settings', 'mail'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'shop_name' => 'required|string|max:100',
            'shop_tagline' => 'nullable|string|max:150',
            'shop_address' => 'nullable|string|max:255',
            'shop_contact' => 'nullable|string|max:50',
            'receipt_footer' => 'nullable|string|max:255',

            'takeout_fee_amount' => 'required|numeric|min:0|max:9999',
            'takeout_fee_per_items' => 'required|integer|min:1|max:100',

            'gcash_number' => 'nullable|string|max:30',
            'gcash_name' => 'nullable|string|max:100',

            'low_stock_threshold' => 'required|integer|min:0|max:9999',

            'login_max_attempts' => 'required|integer|min:1|max:20',
            'login_lockout_minutes' => 'required|integer|min:1|max:1440',
            'otp_expiry_minutes' => 'required|integer|min:1|max:1440',
        ], [], [
            'takeout_fee_amount' => 'take-out fee',
            'takeout_fee_per_items' => 'items per take-out fee',
            'low_stock_threshold' => 'low stock threshold',
            'login_max_attempts' => 'maximum login attempts',
            'login_lockout_minutes' => 'lockout duration',
            'otp_expiry_minutes' => 'OTP expiry',
        ]);

        Setting::put($validated);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'SETTINGS_UPDATED',
            'description' => 'Updated system settings: ' . implode(', ', array_keys($validated)),
        ]);

        return redirect()->route('settings.index')->with('success', 'Settings saved successfully.');
    }

    /**
     * Send a real test email so mail problems can be diagnosed from the browser
     * (useful on shared hosting with no terminal access).
     */
    public function testMail(Request $request)
    {
        $validated = $request->validate([
            'test_email' => 'required|email',
        ]);

        $code = (string) random_int(100000, 999999);
        $sent = NotificationService::sendViaGmail(
            'CAPTAiN J — mail test',
            "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color:#dc3545;'>CAPTAiN J</h2>
                <p>This is a test message sent from Admin Panel &rarr; Settings.</p>
                <p style='font-size:28px;font-weight:bold;letter-spacing:5px;font-family:monospace;'>{$code}</p>
                <p>If you received this, verification (OTP) emails are working.</p>
             </div>",
            $validated['test_email'],
            'CAPTAiN J Test'
        );

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $sent ? 'MAIL_TEST_OK' : 'MAIL_TEST_FAILED',
            'description' => "Mail test to {$validated['test_email']}" . ($sent ? ' succeeded.' : ' failed: ' . NotificationService::$lastError),
        ]);

        if ($sent) {
            return back()->with('success', "Test email sent to {$validated['test_email']}. Check the Inbox and Spam folder.");
        }

        return back()
            ->with('error', 'Mail test failed: ' . (NotificationService::$lastError ?: 'Unknown error.'))
            ->with('mail_hint', $this->hintFor(NotificationService::$lastError ?: ''));
    }

    /**
     * Plain-language next step for the common mail failures.
     */
    private function hintFor(string $reason): ?string
    {
        if (str_contains($reason, '535') || stripos($reason, 'BadCredentials') !== false || stripos($reason, 'Username and Password not accepted') !== false) {
            return 'Gmail will not accept your normal account password over SMTP. Turn on 2-Step Verification, then create a 16-character App Password at myaccount.google.com/apppasswords and use it as MAIL_PASSWORD. MAIL_USERNAME and MAIL_FROM_ADDRESS must both be that same Gmail address. Run "php artisan config:clear" after editing .env.';
        }

        if (stripos($reason, 'invalid_grant') !== false) {
            return 'The Gmail API refresh token has expired. Either publish the OAuth app in Google Cloud Console and regenerate GMAIL_REFRESH_TOKEN, or switch to SMTP with an App Password.';
        }

        if (stripos($reason, 'MAIL_MAILER=log') !== false) {
            return 'Set MAIL_MAILER=smtp in .env (with MAIL_HOST, MAIL_PORT, MAIL_USERNAME and MAIL_PASSWORD), then run "php artisan config:clear".';
        }

        if (stripos($reason, 'Connection could not be established') !== false || stripos($reason, 'timed out') !== false) {
            return 'The server could not reach the mail host. Try MAIL_PORT=465 with MAIL_ENCRYPTION=ssl, or ask your hosting provider to allow outbound SMTP on port 587.';
        }

        return null;
    }
}
