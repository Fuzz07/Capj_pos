<?php
// One-time helper: obtain a Gmail API OAuth refresh token.
// Before using this, you must:
//   1. Set GMAIL_CLIENT_ID and GMAIL_CLIENT_SECRET in includes/config.php
//   2. In Google Cloud Console, add THIS page's URL (shown on screen) as an
//      "Authorized redirect URI" for your OAuth Client ID.
require_once __DIR__ . '/../includes/config.php';

$self = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
      . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
      . ($_SERVER['SCRIPT_NAME'] ?? '/gmail-oauth-setup.php');

function page_top($title) {
    echo "<!doctype html><html><head><meta charset='utf-8'><title>" . htmlspecialchars($title) . "</title></head>";
    echo "<body style='font-family:system-ui,Segoe UI,Arial,sans-serif;max-width:780px;margin:40px auto;padding:0 16px;line-height:1.6;color:#222'>";
    echo "<h2>" . htmlspecialchars($title) . "</h2>";
}
function page_bottom() {
    echo "</body></html>";
}

if (empty(GMAIL_CLIENT_ID) || empty(GMAIL_CLIENT_SECRET)) {
    page_top('Gmail API Setup — Step 1');
    echo "<p>Fill in <code>GMAIL_CLIENT_ID</code> and <code>GMAIL_CLIENT_SECRET</code> in <code>includes/config.php</code> first, then reload this page.</p>";
    echo "<p>If you don't have a Client ID/Secret yet, follow these steps in the browser (signed in as <b>admincapj@gmail.com</b>):</p>";
    echo "<ol>";
    echo "<li>Open <a href='https://console.cloud.google.com/'>console.cloud.google.com</a> and create a project (e.g. \"Captain J\").</li>";
    echo "<li>Go to <b>APIs &amp; Services → Library</b>, search <b>Gmail API</b>, open it and click <b>Enable</b>.</li>";
    echo "<li>Go to <b>APIs &amp; Services → OAuth consent screen</b>. Choose <b>External</b> → create.<br>App name: <code>Captain J</code>, User support email: <code>admincapj@gmail.com</code>.<br>Save, then add <code>admincapj@gmail.com</code> under <b>Test users</b>.</li>";
    echo "<li>Go to <b>APIs &amp; Services → Credentials → Create Credentials → OAuth client ID</b>.<br>Application type: <b>Web application</b>.<br><b>Authorized redirect URIs</b>: add exactly this URL:<br><code style='background:#f1f1f1;padding:2px 6px;border-radius:4px'>" . htmlspecialchars($self) . "</code><br>Click <b>Create</b> and copy the <b>Client ID</b> and <b>Client Secret</b> into config.php.</li>";
    echo "<li>Reload this page when done.</li>";
    echo "</ol>";
    page_bottom();
    exit;
}

// Google sent the user back with an error (e.g. redirect_uri_mismatch).
if (isset($_GET['error'])) {
    page_top('Gmail API Setup — Google Error');
    echo "<p style='color:#b00'><b>" . htmlspecialchars($_GET['error']) . "</b>"
       . (!empty($_GET['error_description']) ? " — " . htmlspecialchars($_GET['error_description']) : "") . "</p>";
    echo "<p>Your redirect URI for this page is:</p>";
    echo "<p><code style='background:#f1f1f1;padding:4px 8px;border-radius:4px;display:inline-block'>" . htmlspecialchars($self) . "</code></p>";
    echo "<p>Open <a href='https://console.cloud.google.com/apis/credentials'>Google Cloud Console → Credentials</a>, click your OAuth client, and under <b>Authorized redirect URIs</b> make sure that exact URL (no trailing slash, same <code>http</code>/<code>https</code>, same <code>localhost</code>) is listed, then <b>Save</b>.</p>";
    echo "<p><a href='" . htmlspecialchars($self) . "' style='display:inline-block;padding:8px 16px;background:#1a73e8;color:#fff;text-decoration:none;border-radius:6px'>Try again</a></p>";
    page_bottom();
    exit;
}

// Step 2: Google redirected back with a code → exchange it for tokens.
if (isset($_GET['code'])) {
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POSTFIELDS => http_build_query([
            'code' => $_GET['code'],
            'client_id' => GMAIL_CLIENT_ID,
            'client_secret' => GMAIL_CLIENT_SECRET,
            'redirect_uri' => $self,
            'grant_type' => 'authorization_code',
        ]),
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    page_top('Gmail API Setup — Result');

    if ($curlErr) {
        echo "<p style='color:#b00'><b>cURL error:</b> " . htmlspecialchars($curlErr) . "</p>";
        page_bottom();
        exit;
    }
    if (!empty($data['error'])) {
        echo "<p style='color:#b00'><b>Error:</b> " . htmlspecialchars($data['error']) . " — " . htmlspecialchars($data['error_description'] ?? '') . "</p>";
        echo "<p>Most common cause: the <b>redirect URI</b> in Google Cloud Console doesn't exactly match <code>" . htmlspecialchars($self) . "</code>.</p>";
        page_bottom();
        exit;
    }

    $refresh = $data['refresh_token'] ?? '';
    if ($refresh === '') {
        echo "<p style='color:#b00'><b>No refresh_token returned.</b> Go back to Google Cloud Console and add your email as a <b>Test user</b>, then run this page again.</p>";
        page_bottom();
        exit;
    }

    echo "<p style='color:#080'><b>Success!</b> Copy the refresh token below and paste it into <code>includes/config.php</code> as <code>GMAIL_REFRESH_TOKEN</code>.</p>";
    echo "<textarea rows='4' readonly style='width:100%;font-size:13px;'>" . htmlspecialchars($refresh) . "</textarea>";
    echo "<p>Then test sending via <code>admin/test-mail.php</code>.</p>";
    page_bottom();
    exit;
}

// Step 1 (confirmation page): show the exact redirect URI, then go to Google.
page_top('Gmail API Setup');
echo "<p>Before continuing, make sure <b>this exact URL</b> is listed under <b>Authorized redirect URIs</b> in your OAuth client:</p>";
echo "<p><code style='background:#f1f1f1;padding:4px 8px;border-radius:4px;display:inline-block'>" . htmlspecialchars($self) . "</code></p>";
echo "<p>Check in <a href='https://console.cloud.google.com/apis/credentials'>Google Cloud Console → Credentials</a>.<br>"
   . "Match it character-for-character: <code>http</code> (not https), <code>localhost</code> (not 127.0.0.1), no trailing slash.</p>";

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?'
         . http_build_query([
             'client_id'     => GMAIL_CLIENT_ID,
             'redirect_uri'  => $self,
             'response_type' => 'code',
             'scope'         => 'https://www.googleapis.com/auth/gmail.send',
             'access_type'   => 'offline',
             'prompt'        => 'consent',
         ]);
echo "<p><a href='" . htmlspecialchars($authUrl) . "' style='display:inline-block;padding:10px 20px;background:#1a73e8;color:#fff;text-decoration:none;border-radius:6px'>Continue to Google</a></p>";
page_bottom();
exit;
