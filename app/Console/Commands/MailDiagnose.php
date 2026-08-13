<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class MailDiagnose extends Command
{
    protected $signature = 'mail:diagnose {email? : Address to send a test message to}';

    protected $description = 'Check the OTP/notification mail setup and optionally send a real test email';

    public function handle(): int
    {
        $this->newLine();
        $this->info('CAPTAiN J — Mail / OTP delivery diagnostic');
        $this->line(str_repeat('=', 55));

        // ---- 1. Which mailer will Laravel fall back to? ----
        $mailer = config('mail.default');
        $this->newLine();
        $this->line("<fg=cyan>Laravel fallback mailer:</> {$mailer}");

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("  ! MAIL_MAILER={$mailer} does NOT deliver email. It only writes to storage/logs.");
            $this->line('    This is fine ONLY if Gmail API below is working, since that is tried first.');
        } elseif ($mailer === 'smtp') {
            $this->line('  SMTP host: ' . (config('mail.mailers.smtp.host') ?: '(not set)'));
            $this->line('  SMTP port: ' . (config('mail.mailers.smtp.port') ?: '(not set)'));
            $this->line('  SMTP user: ' . (config('mail.mailers.smtp.username') ?: '(not set)'));
            $this->line('  Encryption: ' . (config('mail.mailers.smtp.encryption') ?: '(none)'));
        }

        // ---- 2. Gmail API (OAuth) credentials ----
        $gmail = config('pos.gmail');
        $this->newLine();
        $this->line('<fg=cyan>Gmail API (primary send path):</>');
        $this->line('  Sending account: ' . ($gmail['user'] ?: '(not set)'));

        foreach (['client_id' => 'Client ID', 'client_secret' => 'Client secret', 'refresh_token' => 'Refresh token'] as $key => $label) {
            $value = $gmail[$key] ?? '';
            if (empty($value)) {
                $this->error("  MISSING  {$label}");
            } else {
                $this->line("  OK       {$label} (" . $this->mask($value) . ')');
            }
        }

        $oauthReady = false;

        if (empty($gmail['refresh_token'])) {
            $this->error('  Gmail API is not configured — the app will fall back to the mailer above.');
            $this->line('  Set GMAIL_CLIENT_ID, GMAIL_CLIENT_SECRET and GMAIL_REFRESH_TOKEN in .env.');
        } else {
            // ---- 3. Can we actually exchange the refresh token? ----
            $this->newLine();
            $this->line('<fg=cyan>Testing Gmail OAuth token exchange…</>');

            $ch = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POSTFIELDS => http_build_query([
                    'client_id' => $gmail['client_id'],
                    'client_secret' => $gmail['client_secret'],
                    'refresh_token' => $gmail['refresh_token'],
                    'grant_type' => 'refresh_token',
                ]),
            ]);
            $response = curl_exec($ch);
            $curlErr = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($response ?: '', true);

            if ($curlErr !== '') {
                $this->error("  Network error: {$curlErr}");
                $this->line('  This server could not reach oauth2.googleapis.com.');
                $this->line('  Check outbound HTTPS access and the CA bundle (curl.cainfo) on this host.');
            } elseif (empty($data['access_token'])) {
                $this->error("  Token exchange FAILED (HTTP {$httpCode})");
                $this->line('  Response: ' . trim(preg_replace('/\s+/', ' ', substr((string) $response, 0, 300))));

                if (($data['error'] ?? '') === 'invalid_grant') {
                    $this->newLine();
                    $this->warn('  The refresh token has expired or been revoked.');
                    $this->line('  Most common cause: the Google Cloud OAuth consent screen is still in');
                    $this->line('  "Testing" mode, where refresh tokens stop working after 7 days.');
                    $this->newLine();
                    $this->line('  Fix it one of these ways:');
                    $this->line('   a) Google Cloud Console -> OAuth consent screen -> PUBLISH APP,');
                    $this->line('      then generate a new refresh token and update GMAIL_REFRESH_TOKEN.');
                    $this->line('   b) Simpler and it never expires — use Gmail SMTP with an App Password:');
                    $this->line('        MAIL_MAILER=smtp');
                    $this->line('        MAIL_HOST=smtp.gmail.com');
                    $this->line('        MAIL_PORT=587');
                    $this->line('        MAIL_USERNAME=' . ($gmail['user'] ?: 'your@gmail.com'));
                    $this->line('        MAIL_PASSWORD=<16-char Google App Password>');
                    $this->line('        MAIL_ENCRYPTION=tls');
                }
            } else {
                $oauthReady = true;
                $this->info('  OK — access token obtained.');
                $this->line('  Granted scope: ' . ($data['scope'] ?? '(none reported)'));
            }
        }

        // ---- 4. Optional real send (exercises the same path the app uses) ----
        $email = $this->argument('email');
        if (!$email) {
            $this->newLine();
            if ($oauthReady) {
                $this->info('Mail setup looks healthy.');
            } else {
                $this->warn('Gmail API is unavailable. OTP emails will only send if the fallback mailer works.');
            }
            $this->line('To send a real test message, run:');
            $this->line('  php artisan mail:diagnose you@example.com');
            return $oauthReady ? self::SUCCESS : self::FAILURE;
        }

        $this->newLine();
        $this->line("<fg=cyan>Sending a test OTP email to {$email}…</>");

        $code = (string) random_int(100000, 999999);
        $sent = NotificationService::sendViaGmail(
            'CAPTAiN J — test verification code',
            "<p>This is a test message from the CAPTAiN J POS mail diagnostic.</p>
             <p style='font-size:28px;font-weight:bold;letter-spacing:5px;'>{$code}</p>
             <p>If you received this, OTP verification emails are working.</p>",
            $email,
            'CAPTAiN J Test'
        );

        $this->newLine();
        if ($sent) {
            $this->info("SUCCESS — test email accepted for delivery to {$email} (code {$code}).");
            $this->line('If it does not arrive, check the Spam and Promotions folders.');
            return self::SUCCESS;
        }

        $reason = NotificationService::$lastError ?: 'Unknown error.';
        $this->error('FAILED — the message was not delivered.');
        $this->line('Reason: ' . $reason);
        $this->explainSmtpFailure($reason);
        $this->newLine();
        $this->line('Full details are in storage/logs/laravel.log.');
        return self::FAILURE;
    }

    /**
     * Turn the common (and cryptic) Gmail SMTP rejections into concrete steps.
     */
    private function explainSmtpFailure(string $reason): void
    {
        if (!str_contains($reason, 'SMTP')) {
            return;
        }

        $this->newLine();

        if (str_contains($reason, '535') || stripos($reason, 'BadCredentials') !== false || stripos($reason, 'Username and Password not accepted') !== false) {
            $this->warn('Gmail rejected the username/password (535 BadCredentials).');
            $this->line('Gmail does NOT accept your normal account password over SMTP.');
            $this->line('You must use a 16-character App Password:');
            $this->newLine();
            $this->line('  1. Turn ON 2-Step Verification:');
            $this->line('     https://myaccount.google.com/signinoptions/two-step-verification');
            $this->line('     (App Passwords do not exist until 2-Step is enabled.)');
            $this->line('  2. Create an App Password:');
            $this->line('     https://myaccount.google.com/apppasswords');
            $this->line('  3. Put it in .env as MAIL_PASSWORD, and make sure:');
            $this->line('       MAIL_MAILER=smtp');
            $this->line('       MAIL_HOST=smtp.gmail.com');
            $this->line('       MAIL_PORT=587');
            $this->line('       MAIL_ENCRYPTION=tls');
            $this->line('       MAIL_USERNAME must be the SAME Gmail address that owns the App Password');
            $this->line('  4. Run: php artisan config:clear');
            $this->newLine();
            $this->line('Also check: no surrounding quotes in .env, and MAIL_FROM_ADDRESS should');
            $this->line('match MAIL_USERNAME — Gmail refuses to send as a different address.');
            return;
        }

        if (stripos($reason, 'Connection could not be established') !== false || stripos($reason, 'timed out') !== false || stripos($reason, 'timeout') !== false) {
            $this->warn('Could not open a connection to the SMTP server.');
            $this->line('The host is likely blocking outbound SMTP. Try MAIL_PORT=465 with');
            $this->line('MAIL_ENCRYPTION=ssl, or ask the host to open port 587.');
            return;
        }

        if (stripos($reason, 'certificate') !== false || stripos($reason, 'SSL') !== false) {
            $this->warn('TLS/SSL negotiation failed.');
            $this->line('Check that MAIL_PORT and MAIL_ENCRYPTION agree: 587+tls, or 465+ssl.');
        }
    }

    private function mask(string $value): string
    {
        if (strlen($value) <= 12) {
            return str_repeat('*', strlen($value));
        }
        return substr($value, 0, 6) . str_repeat('*', 6) . substr($value, -4);
    }
}
