<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Notification;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public static function checkAndNotifyLowStock(?Inventory $item = null): void
    {
        $threshold = config('pos.low_stock_threshold', 5);

        $query = Inventory::where('is_active', true)->where('stock_qty', '<=', $threshold);
        if ($item) {
            $query->where('id', $item->id);
        }

        $itemsToNotify = $query->get();

        foreach ($itemsToNotify as $lowItem) {
            $isOutOfStock = ($lowItem->stock_qty <= 0);
            $type = $isOutOfStock ? 'out_of_stock' : 'low_stock';
            
            $msg = $isOutOfStock
                ? "⚠️ OUT OF STOCK ALERT: '{$lowItem->name}' is completely OUT OF STOCK (0 remaining)!"
                : "⚡ LOW STOCK ALERT: '{$lowItem->name}' has only {$lowItem->stock_qty} item(s) remaining (Low stock: 1-5 threshold).";

            $exists = Notification::where('type', $type)
                ->where('related_id', $lowItem->id)
                ->where('is_read', false)
                ->exists();

            if (!$exists) {
                Notification::create([
                    'type' => $type,
                    'message' => $msg,
                    'related_entity' => 'inventory',
                    'related_id' => $lowItem->id,
                    'is_read' => false,
                ]);

                // Send email notification to Admin and Staff
                try {
                    self::sendStockAlertEmail($lowItem, $type);
                } catch (\Exception $e) {
                    Log::error("Failed to send stock alert email for {$lowItem->name}: " . $e->getMessage());
                }
            }
        }
    }

    public static function sendStockAlertEmail(Inventory $item, string $type): void
    {
        $isOutOfStock = ($type === 'out_of_stock' || $item->stock_qty <= 0);
        $subject = $isOutOfStock
            ? "🚨 OUT OF STOCK ALERT: {$item->name} - CAPTAiN J POS"
            : "⚠️ LOW STOCK WARNING: {$item->name} ({$item->stock_qty} Left) - CAPTAiN J POS";

        $statusColor = $isOutOfStock ? '#dc3545' : '#f59e0b';
        $statusText = $isOutOfStock ? 'OUT OF STOCK (0 remaining)' : "LOW STOCK ({$item->stock_qty} remaining)";

        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px; background-color: #ffffff;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <h2 style='color: #dc3545; margin: 0; font-size: 24px;'>CAPTAiN J</h2>
                    <p style='color: #666; margin: 5px 0 0; font-size: 14px;'>Inventory Stock Notification</p>
                </div>
                
                <div style='background-color: {$statusColor}; color: white; padding: 15px; border-radius: 6px; text-align: center; margin-bottom: 20px;'>
                    <h3 style='margin: 0; font-size: 18px;'>" . ($isOutOfStock ? "🔴 OUT OF STOCK ALERT" : "🟡 LOW STOCK WARNING") . "</h3>
                    <p style='margin: 5px 0 0; font-size: 14px;'>Product stock requires immediate replenishment!</p>
                </div>

                <table style='width: 100%; font-size: 14px; margin-bottom: 20px; color: #333; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px; font-weight: bold; border-bottom: 1px solid #eee;'>Product Name:</td>
                        <td style='padding: 8px; text-align: right; border-bottom: 1px solid #eee; font-weight: bold;'>{$item->name}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; font-weight: bold; border-bottom: 1px solid #eee;'>Current Stock:</td>
                        <td style='padding: 8px; text-align: right; border-bottom: 1px solid #eee; color: {$statusColor}; font-weight: bold;'>{$statusText}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; font-weight: bold; border-bottom: 1px solid #eee;'>Price:</td>
                        <td style='padding: 8px; text-align: right; border-bottom: 1px solid #eee;'>₱" . number_format($item->price, 2) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; font-weight: bold; border-bottom: 1px solid #eee;'>Category / Desc:</td>
                        <td style='padding: 8px; text-align: right; border-bottom: 1px solid #eee;'>" . htmlspecialchars($item->description ?? 'N/A') . "</td>
                    </tr>
                </table>

                <div style='text-align: center; margin-top: 25px;'>
                    <p style='color: #555; font-size: 13px; margin-bottom: 15px;'>Please restock this product in the Inventory Management system to ensure seamless POS ordering.</p>
                </div>

                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #888; font-size: 12px;'>
                    <p style='margin: 0;'>Automated Inventory Notification System</p>
                    <p style='margin: 5px 0 0;'>© " . date('Y') . " CAPTAiN J POS System. All rights reserved.</p>
                </div>
            </div>
        ";

        $config = config('pos.gmail');
        $adminEmail = $config['admin_email'] ?? 'admincapj@gmail.com';

        // Gather all Admin and Staff emails
        $recipientEmails = collect([$adminEmail]);
        $userEmails = \App\Models\User::whereIn('role', ['admin', 'staff'])
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->pluck('email');

        $allEmails = $recipientEmails->merge($userEmails)->filter()->unique(fn($e) => strtolower($e));

        foreach ($allEmails as $email) {
            self::sendViaGmail($subject, $body, $email, 'CAPTAiN J Recipient');
        }
    }

    public static function notifyGcashPayment(Order $order): void
    {
        Notification::create([
            'type' => 'gcash_payment',
            'message' => "GCash payment received for Order #{$order->id} (" . ($order->customer_name ?: 'Walk-in') . ") - ₱" . number_format($order->total_amount, 2),
            'related_entity' => 'order',
            'related_id' => $order->id,
            'is_read' => false,
        ]);

        try {
            self::sendGcashOrderEmail($order);
        } catch (\Exception $e) {
            Log::error("Failed to send GCash payment email: " . $e->getMessage());
        }
    }

    public static function sendGcashOrderEmail(Order $order): void
    {
        $order->load('items.inventory', 'user');

        $itemsHtml = '';
        foreach ($order->items as $item) {
            $name = $item->inventory ? $item->inventory->name : 'Unknown Item';
            $qty = $item->qty;
            $price = number_format($item->unit_price, 2);
            $total = number_format($item->line_total, 2);
            $itemsHtml .= "
                <tr>
                    <td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$name}</td>
                    <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: center;'>{$qty}</td>
                    <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: right;'>₱{$price}</td>
                    <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: right; font-weight: bold;'>₱{$total}</td>
                </tr>
            ";
        }

        $subject = "Payment Received - Order #{$order->id} - CAPTAiN J POS";
        
        $customerName = htmlspecialchars($order->customer_name ?: 'Walk-in Customer');
        $orderType = htmlspecialchars($order->order_type);
        $takeoutFee = number_format($order->takeout_fee, 2);
        $totalAmount = number_format($order->total_amount, 2);
        $amountPaid = number_format($order->amount_paid, 2);
        $changeDue = number_format($order->change_due, 2);
        $staffName = htmlspecialchars($order->user ? $order->user->full_name : 'Staff');
        $date = $order->created_at ? $order->created_at->format('M d, Y h:i A') : date('M d, Y h:i A');

        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px; background-color: #fcfcfc;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <h2 style='color: #dc3545; margin: 0; font-size: 24px;'>CAPTAiN J</h2>
                    <p style='color: #666; margin: 5px 0 0; font-size: 14px;'>Takoyaki & Milktea Shop</p>
                </div>
                
                <div style='background-color: #0057a3; color: white; padding: 15px; border-radius: 6px; text-align: center; margin-bottom: 20px;'>
                    <h3 style='margin: 0; font-size: 18px;'>📱 GCash Payment Received</h3>
                    <p style='margin: 5px 0 0; font-size: 14px;'>Order #{$order->id} is successfully processed!</p>
                </div>

                <table style='width: 100%; font-size: 14px; margin-bottom: 20px; color: #333;'>
                    <tr>
                        <td style='padding: 4px 0; font-weight: bold;'>Customer:</td>
                        <td style='padding: 4px 0; text-align: right;'>{$customerName}</td>
                    </tr>
                    <tr>
                        <td style='padding: 4px 0; font-weight: bold;'>Order Type:</td>
                        <td style='padding: 4px 0; text-align: right;'>{$orderType}</td>
                    </tr>
                    <tr>
                        <td style='padding: 4px 0; font-weight: bold;'>Date & Time:</td>
                        <td style='padding: 4px 0; text-align: right;'>{$date}</td>
                    </tr>
                    <tr>
                        <td style='padding: 4px 0; font-weight: bold;'>Processed By:</td>
                        <td style='padding: 4px 0; text-align: right;'>{$staffName}</td>
                    </tr>
                </table>

                <h4 style='border-bottom: 2px solid #0057a3; padding-bottom: 5px; color: #0057a3; margin-top: 25px;'>Order Summary</h4>
                <table style='width: 100%; border-collapse: collapse; font-size: 13px; color: #333; margin-bottom: 20px;'>
                    <thead>
                        <tr style='background-color: #f5f5f5;'>
                            <th style='padding: 8px; text-align: left;'>Item Name</th>
                            <th style='padding: 8px; text-align: center; width: 50px;'>Qty</th>
                            <th style='padding: 8px; text-align: right; width: 80px;'>Unit Price</th>
                            <th style='padding: 8px; text-align: right; width: 90px;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsHtml}
                    </tbody>
                </table>

                <table style='width: 100%; font-size: 14px; color: #333;'>
                    <tr>
                        <td style='padding: 4px 0;'>Subtotal:</td>
                        <td style='padding: 4px 0; text-align: right;'>₱" . number_format($order->total_amount - $order->takeout_fee, 2) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 4px 0;'>Takeout Fee:</td>
                        <td style='padding: 4px 0; text-align: right;'>₱{$takeoutFee}</td>
                    </tr>
                    <tr style='font-size: 16px; font-weight: bold; border-top: 2px solid #ddd;'>
                        <td style='padding: 8px 0; color: #0057a3;'>Grand Total:</td>
                        <td style='padding: 8px 0; text-align: right; color: #0057a3;'>₱{$totalAmount}</td>
                    </tr>
                    <tr>
                        <td style='padding: 4px 0; font-weight: bold;'>Amount Paid:</td>
                        <td style='padding: 4px 0; text-align: right; font-weight: bold;'>₱{$amountPaid}</td>
                    </tr>
                    <tr>
                        <td style='padding: 4px 0; color: #666;'>Change Due:</td>
                        <td style='padding: 4px 0; text-align: right; color: #666;'>₱{$changeDue}</td>
                    </tr>
                </table>

                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #888; font-size: 12px;'>
                    <p style='margin: 0;'>Thank you for choosing CAPTAiN J!</p>
                    <p style='margin: 5px 0 0;'>© " . date('Y') . " CAPTAiN J. All rights reserved.</p>
                </div>
            </div>
        ";

        $config = config('pos.gmail');
        $adminEmail = $config['admin_email'] ?? 'admincapj@gmail.com';
        $adminName = $config['admin_name'] ?? 'Captain J Admin';

        // Send to admin email
        self::sendViaGmail($subject, $body, $adminEmail, $adminName);

        // Also send to the logged-in user if they have a valid email configured and it is different from admin email
        if ($order->user && !empty($order->user->email) && strtolower($order->user->email) !== strtolower($adminEmail)) {
            self::sendViaGmail($subject, $body, $order->user->email, $order->user->full_name ?: $order->user->username);
        }
    }

    /**
     * Human-readable reason the most recent send failed, for surfacing in the UI.
     */
    public static ?string $lastError = null;

    public static function sendViaGmail(string $subject, string $body, string $toEmail, string $toName): bool
    {
        self::$lastError = null;

        $config = config('pos.gmail');
        $clientId = $config['client_id'] ?? '';
        $clientSecret = $config['client_secret'] ?? '';
        $refreshToken = $config['refresh_token'] ?? '';
        $gmailUser = $config['user'] ?? '';

        if (!empty($refreshToken)) {
            // 1. Get Access Token via Gmail OAuth API
            $ch = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POSTFIELDS => http_build_query([
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refreshToken,
                    'grant_type'    => 'refresh_token',
                ]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            ]);
            $response = curl_exec($ch);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($curlErr !== '') {
                self::$lastError = 'The server could not reach Google (' . $curlErr . ').';
                Log::error("Gmail OAuth token request failed: {$curlErr}");
            } else {
                $data = json_decode($response, true);
                if (empty($data['access_token'])) {
                    if (($data['error'] ?? '') === 'invalid_grant') {
                        self::$lastError = 'The Gmail authorisation has expired (invalid_grant). Regenerate GMAIL_REFRESH_TOKEN, or switch to Gmail SMTP with an App Password.';
                    } else {
                        self::$lastError = 'Google rejected the mail credentials (' . ($data['error'] ?? 'unknown error') . ').';
                    }
                    Log::error("Gmail OAuth token response missing access_token: " . substr((string) $response, 0, 300));
                } else {
                    $accessToken = $data['access_token'];

                    if (function_exists('mb_encode_mimeheader')) {
                        $subjectEnc = mb_encode_mimeheader($subject, 'UTF-8', 'B');
                    } else {
                        $subjectEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';
                    }

                    $toNameEnc = preg_match('/[^\x20-\x7E]/', $toName) ? '=?UTF-8?B?' . base64_encode($toName) . '?=' : '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $toName) . '"';
                    $fromName = $config['admin_name'] ?? 'CAPTAiN J';
                    $fromNameEnc = preg_match('/[^\x20-\x7E]/', $fromName) ? '=?UTF-8?B?' . base64_encode($fromName) . '?=' : '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $fromName) . '"';

                    $headers = [
                        "To: {$toNameEnc} <{$toEmail}>",
                        "From: {$fromNameEnc} <{$gmailUser}>",
                        "Subject: {$subjectEnc}",
                        'MIME-Version: 1.0',
                        'Content-Type: text/html; charset=UTF-8',
                        'Content-Transfer-Encoding: 8bit',
                        'X-Mailer: PHP/' . phpversion(),
                    ];

                    $raw = implode("\r\n", $headers) . "\r\n\r\n" . $body;
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
                    $sendResponse = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    $sendData = json_decode($sendResponse, true);
                    if ($httpCode >= 200 && $httpCode < 300 && !empty($sendData['id'])) {
                        return true;
                    }
                    self::$lastError = "Gmail rejected the message (HTTP {$httpCode}).";
                    Log::error("Gmail API send failed (HTTP {$httpCode}): " . substr((string) $sendResponse, 0, 300));
                }
            }
        }

        // Fallback to Laravel Mailer. The 'log' and 'array' mailers only swallow
        // the message for debugging, so they must not be reported as delivered.
        $mailer = config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            self::$lastError = ($mailer === 'log' || $mailer === 'array')
                ? trim((self::$lastError ? self::$lastError . ' ' : '') . "No delivering mailer is configured (MAIL_MAILER={$mailer} only writes to the log). Set MAIL_MAILER=smtp with valid credentials.")
                : self::$lastError;
            Log::error("Mail not delivered: MAIL_MAILER={$mailer} does not send email.");
            return false;
        }

        try {
            \Illuminate\Support\Facades\Mail::html($body, function ($message) use ($toEmail, $toName, $subject) {
                $message->to($toEmail, $toName)->subject($subject);
            });
            self::$lastError = null;
            return true;
        } catch (\Exception $e) {
            self::$lastError = 'SMTP send failed: ' . $e->getMessage();
            Log::error("Mail fallback send error: " . $e->getMessage());
        }

        return false;
    }
}
