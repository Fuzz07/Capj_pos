<?php
require_once __DIR__ . '/config.php';

function send_low_stock_email($items, $pdo = null) {
    if (empty($items)) return ['success' => false, 'error' => 'No items provided'];

    if (empty(GMAIL_REFRESH_TOKEN)) {
        return ['success' => false, 'error' => 'Gmail API is not configured (missing refresh token)'];
    }

    $oos = array_filter($items, fn($i) => (int)$i['stock_qty'] <= 0);
    $low = array_filter($items, fn($i) => (int)$i['stock_qty'] > 0);

    $hasOos = !empty($oos);
    $hasLow = !empty($low);

    $subject = ($hasOos ? "Out of Stock" : "Low Stock") . " Alert - " . APP_NAME;

    $body = "<h2>" . ($hasOos ? "Out of Stock & Low Stock Alert" : "Low Stock Alert") . "</h2>";

    if ($hasOos) {
        $body .= "<h3 style='color:#dc3545;'>Out of Stock</h3>";
        $body .= "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse;margin-bottom:15px;'>";
        $body .= "<tr style='background:#dc3545;color:#fff;'><th>Item</th><th>Status</th></tr>";
        foreach ($oos as $item) {
            $body .= "<tr><td>" . htmlspecialchars($item['name']) . "</td><td style='text-align:center;font-weight:bold;color:red;'>Out of Stock</td></tr>";
        }
        $body .= "</table>";
    }

    if ($hasLow) {
        $body .= "<h3 style='color:#ffc107;'>Low Stock</h3>";
        $body .= "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse;margin-bottom:15px;'>";
        $body .= "<tr style='background:#f10000;color:#fff;'><th>Item</th><th>Stock Left</th></tr>";
        foreach ($low as $item) {
            $body .= "<tr><td>" . htmlspecialchars($item['name']) . "</td><td style='text-align:center;font-weight:bold;color:red;'>" . (int)$item['stock_qty'] . "</td></tr>";
        }
        $body .= "</table>";
    }

    $body .= "<p>Please restock as soon as possible.</p>";

    $result = send_via_gmail($subject, $body, ADMIN_EMAIL, ADMIN_NAME);

    if ($pdo) {
        $stmt = $pdo->query("SELECT email, full_name FROM users WHERE role = 'staff' AND email IS NOT NULL AND email_verified_at IS NOT NULL AND email != ''");
        $staff = $stmt->fetchAll();
        foreach ($staff as $s) {
            send_via_gmail($subject, $body, $s['email'], $s['full_name'] ?: 'Staff');
        }
    }

    return $result;
}

function send_verification_email($to_email, $token, $full_name) {
    if (empty(GMAIL_REFRESH_TOKEN)) return ['success' => false, 'error' => 'Gmail API not configured'];

    $verify_link = APP_PUBLIC_URL . "/verify-email.php?token=" . urlencode($token);
    $subject = "Verify your email - " . APP_NAME;
    $body = "<h2>Welcome, " . htmlspecialchars($full_name) . "!</h2>";
    $body .= "<p>Your staff account has been created. Please verify your email address by clicking the link below:</p>";
    $body .= "<p style='text-align:center;'><a href='" . $verify_link . "' style='display:inline-block;padding:12px 24px;background:#18b318;color:#fff;text-decoration:none;border-radius:6px;'>Verify Email</a></p>";
    $body .= "<p>Or copy and paste this link in your browser:</p>";
    $body .= "<p>" . $verify_link . "</p>";
    $body .= "<p>This link expires in 24 hours.</p>";
    $body .= "<p>If you did not create this account, please ignore this email.</p>";

    return send_via_gmail($subject, $body, $to_email, $full_name);
}

function send_otp_email($to_email, $otp, $full_name) {
    if (empty(GMAIL_REFRESH_TOKEN)) return ['success' => false, 'error' => 'Gmail API not configured'];

    $subject = "Your OTP Code - " . APP_NAME;
    $body = "<h2>Hello, " . htmlspecialchars($full_name) . "!</h2>";
    $body .= "<p>You requested to reset your password. Use the OTP code below to proceed:</p>";
    $body .= "<p style='text-align:center;font-size:32px;font-weight:bold;letter-spacing:8px;background:#f5f5f5;padding:15px;border-radius:8px;'>" . $otp . "</p>";
    $body .= "<p>This code expires in 5 minutes.</p>";
    $body .= "<p>If you did not request this, please ignore this email.</p>";

    return send_via_gmail($subject, $body, $to_email, $full_name);
}

function send_password_changed_notification($to_email, $full_name) {
    if (empty(GMAIL_REFRESH_TOKEN)) return ['success' => false, 'error' => 'Gmail API not configured'];

    $subject = "Password Changed - " . APP_NAME;
    $body = "<h2>Hello, " . htmlspecialchars($full_name) . "!</h2>";
    $body .= "<p>Your password for your " . APP_NAME . " account was successfully changed.</p>";
    $body .= "<p>If you made this change, no further action is needed.</p>";
    $body .= "<p>If you did NOT request this change, please contact the administrator immediately.</p>";

    return send_via_gmail($subject, $body, $to_email, $full_name);
}

function send_via_mail($subject, $body, $to_email = null, $to_name = null) {
    $to_email = $to_email ?? ADMIN_EMAIL;
    $to_name = $to_name ?? ADMIN_NAME;
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . ADMIN_NAME . " <" . GMAIL_USER . ">\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    return mail($to_email, $subject, $body, $headers);
}

function send_payment_receipt_email($orderId, $customer, $total, $itemsOrdered, $staffName) {
    if (empty(GMAIL_REFRESH_TOKEN)) return ['success' => false, 'error' => 'Gmail API not configured'];

    $itemsHtml = '';
    $totalQty = 0;
    foreach ($itemsOrdered as $id => $qty) {
        $totalQty += $qty;
        $name = 'Item #' . $id;
        $itemsHtml .= "<tr><td>" . htmlspecialchars($name) . "</td><td style='text-align:center'>$qty</td></tr>";
    }

    $subject = "Payment Received - Order #$orderId - " . APP_NAME;
    $body = "<h2 style='color:#f10000'>Payment Received!</h2>";
    $body .= "<p><strong>Order #$orderId</strong> has been marked as paid.</p>";
    $body .= "<table style='width:100%;border-collapse:collapse;margin:10px 0;'>";
    $body .= "<tr><td style='padding:4px 8px;font-weight:bold'>Customer:</td><td style='padding:4px 8px'>" . htmlspecialchars($customer ?: 'Walk-in') . "</td></tr>";
    $body .= "<tr><td style='padding:4px 8px;font-weight:bold'>Total:</td><td style='padding:4px 8px'>₱" . number_format($total, 2) . "</td></tr>";
    $body .= "<tr><td style='padding:4px 8px;font-weight:bold'>Items:</td><td style='padding:4px 8px'>$totalQty</td></tr>";
    $body .= "<tr><td style='padding:4px 8px;font-weight:bold'>Processed by:</td><td style='padding:4px 8px'>" . htmlspecialchars($staffName) . "</td></tr>";
    $body .= "</table>";
    $body .= "<p style='margin-top:15px'><a href='" . APP_PUBLIC_URL . "/admin/receipt.php?id=$orderId' style='display:inline-block;padding:10px 20px;background:#0057a3;color:#fff;text-decoration:none;border-radius:6px;'>View Receipt</a></p>";

    return send_via_gmail($subject, $body, ADMIN_EMAIL, ADMIN_NAME);
}

/**
 * Send an HTML email via the Gmail API (OAuth2), no SMTP required.
 * Returns ['success' => bool, 'error' => string|null].
 */
function send_via_gmail($subject, $body, $to_email = null, $to_name = null) {
    if (empty(GMAIL_REFRESH_TOKEN)) {
        return ['success' => false, 'error' => 'Gmail API is not configured (missing refresh token)'];
    }

    $to_email = $to_email ?? ADMIN_EMAIL;
    $to_name = $to_name ?? ADMIN_NAME;

    $accessToken = gmail_get_access_token();
    if ($accessToken === false) {
        return ['success' => false, 'error' => 'Could not obtain Gmail access token'];
    }

    $raw = gmail_build_raw_message($to_email, $to_name, $subject, $body);
    $encoded = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

    $ch = curl_init('https://gmail.googleapis.com/gmail/v1/users/me/messages/send');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode(['raw' => $encoded]),
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr !== '') {
        error_log("Gmail API cURL error: " . $curlErr);
        return ['success' => false, 'error' => 'Gmail API connection error: ' . $curlErr];
    }

    $data = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300 && !empty($data['id'])) {
        return ['success' => true, 'error' => null];
    }

    $error = $data['error']['message'] ?? ('HTTP ' . $httpCode);
    error_log("Gmail API send error: " . $error);
    return ['success' => false, 'error' => $error];
}

function gmail_get_access_token() {
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POSTFIELDS => http_build_query([
            'client_id'     => GMAIL_CLIENT_ID,
            'client_secret' => GMAIL_CLIENT_SECRET,
            'refresh_token' => GMAIL_REFRESH_TOKEN,
            'grant_type'    => 'refresh_token',
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr !== '') {
        error_log("Gmail token cURL error: " . $curlErr);
        return false;
    }

    $data = json_decode($response, true);
    if (empty($data['access_token'])) {
        $error = $data['error'] ?? 'unknown';
        error_log("Gmail token error: " . $error);
        return false;
    }

    return $data['access_token'];
}

function gmail_build_raw_message($to_email, $to_name, $subject, $bodyHtml) {
    if (function_exists('mb_encode_mimeheader')) {
        $subjectEnc = mb_encode_mimeheader($subject, 'UTF-8', 'B');
    } else {
        $subjectEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    }

    $namePart = ($to_name && $to_name !== $to_email) ? gmail_encode_rfc822_name($to_name) . ' ' : '';
    $fromNamePart = (ADMIN_NAME && ADMIN_NAME !== GMAIL_USER) ? gmail_encode_rfc822_name(ADMIN_NAME) . ' ' : '';

    $headers = [
        'To: ' . $namePart . '<' . $to_email . '>',
        'From: ' . $fromNamePart . '<' . GMAIL_USER . '>',
        'Subject: ' . $subjectEnc,
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: PHP/' . phpversion(),
    ];

    return implode("\r\n", $headers) . "\r\n\r\n" . $bodyHtml;
}

function gmail_encode_rfc822_name($name) {
    if (preg_match('/[^\x20-\x7E]/', $name)) {
        return '=?UTF-8?B?' . base64_encode($name) . '?=';
    }
    return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $name) . '"';
}

/**
 * Kept as an alias so existing callers (and the admin test page) still work.
 */
function send_via_phpmailer($subject, $body, $to_email = null, $to_name = null) {
    return send_via_gmail($subject, $body, $to_email, $to_name);
}
