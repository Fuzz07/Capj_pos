<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Walks through Google's OAuth consent flow and prints a fresh
 * GMAIL_REFRESH_TOKEN for .env, so verification emails keep working
 * without falling back to SMTP.
 */
class GmailAuthorize extends Command
{
    protected $signature = 'gmail:authorize
                            {--port=8419 : Local port used to catch Google\'s redirect}
                            {--write : Write the new token straight into .env}
                            {--manual : Skip the local listener and paste the redirected URL yourself}';

    protected $description = 'Generate a new Gmail API refresh token for sending verification emails';

    private const SCOPE = 'https://mail.google.com/';

    public function handle(): int
    {
        $config = config('pos.gmail');
        $clientId = $config['client_id'] ?? '';
        $clientSecret = $config['client_secret'] ?? '';

        $this->newLine();
        $this->info('Gmail API authorisation — generate a new refresh token');
        $this->line(str_repeat('=', 58));

        if (empty($clientId) || empty($clientSecret)) {
            $this->error('GMAIL_CLIENT_ID and GMAIL_CLIENT_SECRET must be set in .env first.');
            return self::FAILURE;
        }

        $port = (int) $this->option('port');
        $manual = (bool) $this->option('manual');
        $redirectUri = $manual ? 'http://localhost' : "http://localhost:{$port}";

        $this->newLine();
        $this->warn('BEFORE CONTINUING — one-time setup in Google Cloud Console:');
        $this->line('  1. APIs & Services -> Credentials -> open your OAuth 2.0 Client ID');
        $this->line("  2. Under \"Authorised redirect URIs\" add exactly:");
        $this->line("       <fg=yellow>{$redirectUri}</>");
        $this->line('  3. Save, then wait a few seconds for it to take effect.');
        $this->newLine();
        $this->line('  Also make sure APIs & Services -> OAuth consent screen is PUBLISHED.');
        $this->line('  While it is in "Testing", refresh tokens stop working after 7 days —');
        $this->line('  which is exactly what happened to the current one.');
        $this->newLine();

        if (!$this->confirm('Done with the steps above?', true)) {
            $this->line('Aborted. Re-run when the redirect URI has been added.');
            return self::SUCCESS;
        }

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',          // forces a NEW refresh token every time
            'include_granted_scopes' => 'true',
        ]);

        $this->newLine();
        $this->line('<fg=cyan>Open this URL in a browser and sign in as ' . ($config['user'] ?: 'your Gmail account') . ':</>');
        $this->newLine();
        $this->line($authUrl);
        $this->newLine();

        $code = $manual ? $this->askForCodeManually() : $this->waitForRedirect($port);

        if (empty($code)) {
            $this->error('No authorisation code received.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('<fg=cyan>Exchanging the code for a refresh token…</>');

        $tokens = $this->exchange($code, $clientId, $clientSecret, $redirectUri);

        if (!isset($tokens['refresh_token'])) {
            $this->error('Google did not return a refresh token.');
            $this->line('Response: ' . json_encode($tokens));
            if (($tokens['error'] ?? '') === 'redirect_uri_mismatch') {
                $this->newLine();
                $this->warn("The redirect URI must match EXACTLY. Add {$redirectUri} in Google Cloud Console.");
            }
            return self::FAILURE;
        }

        $refreshToken = $tokens['refresh_token'];

        $this->newLine();
        $this->info('SUCCESS — new refresh token generated.');
        $this->newLine();
        $this->line('<fg=yellow>GMAIL_REFRESH_TOKEN=' . $refreshToken . '</>');
        $this->newLine();

        if ($this->option('write') || $this->confirm('Write this into your local .env now?', true)) {
            if ($this->writeToEnv($refreshToken)) {
                $this->info('.env updated.');
                $this->line('Run: php artisan config:clear');
            } else {
                $this->error('Could not update .env — copy the line above in manually.');
            }
        }

        $this->newLine();
        $this->line('Remember to put the SAME line in your PRODUCTION .env (capjpos.com),');
        $this->line('then run "php artisan config:clear" there too.');
        $this->newLine();
        $this->line('Verify with: php artisan mail:diagnose you@example.com');

        return self::SUCCESS;
    }

    /**
     * Spin up a one-shot local listener to catch Google's ?code=... redirect.
     */
    private function waitForRedirect(int $port): ?string
    {
        $server = @stream_socket_server("tcp://127.0.0.1:{$port}", $errno, $errstr);

        if (!$server) {
            $this->warn("Could not listen on port {$port} ({$errstr}).");
            $this->line('Falling back to manual entry.');
            return $this->askForCodeManually();
        }

        $this->line("Waiting for Google to redirect back to http://localhost:{$port} …");
        $this->line('(press Ctrl+C to cancel)');

        stream_set_timeout($server, 300);
        $conn = @stream_socket_accept($server, 300);

        if (!$conn) {
            fclose($server);
            $this->warn('Timed out waiting for the redirect.');
            return $this->askForCodeManually();
        }

        $request = fread($conn, 8192);
        $code = null;
        $error = null;

        if (preg_match('/GET\s+(\S+)\s+HTTP/', (string) $request, $m)) {
            $query = parse_url($m[1], PHP_URL_QUERY) ?? '';
            parse_str($query, $params);
            $code = $params['code'] ?? null;
            $error = $params['error'] ?? null;
        }

        $message = $code
            ? '<h2 style="color:#16a34a">Authorised.</h2><p>You can close this tab and return to the terminal.</p>'
            : '<h2 style="color:#dc2626">Authorisation failed.</h2><p>' . htmlspecialchars((string) $error) . '</p>';

        $body = "<!doctype html><html><body style=\"font-family:sans-serif;text-align:center;padding:60px\">{$message}</body></html>";
        fwrite($conn, "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nContent-Length: " . strlen($body) . "\r\nConnection: close\r\n\r\n{$body}");
        fclose($conn);
        fclose($server);

        if ($error) {
            $this->error("Google returned an error: {$error}");
            return null;
        }

        return $code;
    }

    private function askForCodeManually(): ?string
    {
        $this->newLine();
        $this->line('After approving, the browser will land on a page that fails to load.');
        $this->line('That is expected — copy the FULL address bar URL and paste it here.');
        $this->newLine();

        $input = trim((string) $this->ask('Redirected URL (or just the code)'));
        if ($input === '') {
            return null;
        }

        if (str_contains($input, 'code=')) {
            parse_str((string) parse_url($input, PHP_URL_QUERY), $params);
            return $params['code'] ?? null;
        }

        return $input;
    }

    private function exchange(string $code, string $clientId, string $clientSecret, string $redirectUri): array
    {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => http_build_query([
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]),
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err !== '') {
            return ['error' => $err];
        }

        return json_decode((string) $response, true) ?: ['error' => 'unparseable response'];
    }

    private function writeToEnv(string $refreshToken): bool
    {
        $path = base_path('.env');
        if (!is_writable($path)) {
            return false;
        }

        $contents = file_get_contents($path);
        $line = 'GMAIL_REFRESH_TOKEN=' . $refreshToken;

        $updated = preg_match('/^GMAIL_REFRESH_TOKEN=.*$/m', $contents)
            ? preg_replace('/^GMAIL_REFRESH_TOKEN=.*$/m', $line, $contents)
            : rtrim($contents) . PHP_EOL . $line . PHP_EOL;

        return file_put_contents($path, $updated) !== false;
    }
}
