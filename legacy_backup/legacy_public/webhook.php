<?php
/**
 * PayMongo Webhook Handler
 *
 * NOTE: Your server needs a public HTTPS URL for PayMongo to reach this endpoint.
 * Currently your server is on a local network (192.168.1.41) so PayMongo cannot
 * send webhooks. Use a tunneling service (ngrok, expose, etc.) or deploy to
 * a public server for webhooks to work.
 *
 * For now, payment verification works via polling in paymongo-checkout.php.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/paymongo.php';

$payload = file_get_contents('php://input');
$headers = getallheaders();
$signatureHeader = $headers['Paymongo-Signature'] ?? '';

if (!$payload) {
    http_response_code(400);
    echo json_encode(['error' => 'No payload received']);
    exit;
}

$data = json_decode($payload, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Verify webhook signature if secret is configured
if (PAYMONGO_WEBHOOK_SECRET && PAYMONGO_WEBHOOK_SECRET !== 'whsec_...') {
    $computedSig = hash_hmac('sha256', $payload, PAYMONGO_WEBHOOK_SECRET);
    if ($signatureHeader !== $computedSig) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
}

$eventType = $data['data']['attributes']['type'] ?? '';
$eventData = $data['data']['attributes']['data'] ?? [];

if ($eventType === 'payment.paid' || $eventType === 'payment_intent.payment.succeeded') {
    $piId = $eventData['id'] ?? '';

    if ($piId) {
        // Payment succeeded — look up pending order data
        $stmt = $pdo->prepare("SELECT * FROM pending_payments WHERE payment_intent_id = ? AND status = 'pending'");
        $stmt->execute([$piId]);
        $pending = $stmt->fetch();

        if ($pending) {
            $orderData = json_decode($pending['order_data'], true);
            $userId = $pending['user_id'];

            try {
                $pdo->beginTransaction();

                $stmtOrder = $pdo->prepare("INSERT INTO orders
                    (user_id, customer_name, order_type, takeout_fee, total_amount, amount_paid, change_due, status, payment_method, payment_ref)
                    VALUES (?,?,?,?,?,?,?, 'completed', 'GCash', ?)");
                $stmtOrder->execute([
                    $userId,
                    $orderData['customer'],
                    $orderData['type'],
                    $orderData['takeout_fee'],
                    $orderData['total'],
                    $orderData['total'],
                    0,
                    $piId
                ]);
                $orderId = $pdo->lastInsertId();

                $stmtInsert = $pdo->prepare("INSERT INTO order_items (order_id, inventory_id, qty, unit_price, line_total) VALUES (?,?,?,?,?)");
                $stmtUpdate = $pdo->prepare("UPDATE inventory SET stock_qty = stock_qty - ? WHERE id = ?");

                foreach ($orderData['items'] as $id => $q) {
                    $itemStmt = $pdo->prepare("SELECT id, price FROM inventory WHERE id = ? FOR UPDATE");
                    $itemStmt->execute([$id]);
                    $item = $itemStmt->fetch();
                    if (!$item) continue;

                    $unit = $item['price'];
                    $line = $unit * $q;
                    $stmtInsert->execute([$orderId, $id, $q, $unit, $line]);
                    $stmtUpdate->execute([$q, $id]);
                }

                $stmtUpd = $pdo->prepare("UPDATE pending_payments SET status = 'completed', updated_at = NOW() WHERE id = ?");
                $stmtUpd->execute([$pending['id']]);

                $pdo->commit();

                require_once __DIR__ . '/../includes/notifications.php';
                require_once __DIR__ . '/../includes/mail.php';
                $totalFormatted = number_format($orderData['total'], 2);
                $staffUser = $pdo->prepare("SELECT id, full_name, username FROM users WHERE id = ?");
                $staffUser->execute([$userId]);
                $sUser = $staffUser->fetch();
                $staffName = ($sUser['full_name'] ?: $sUser['username']) ?? 'System';
                $stmtN = $pdo->prepare("INSERT INTO notifications (user_id, type, message, related_entity, related_id) VALUES (?, 'payment', ?, 'order', ?)");
                $notifMsg = "Payment received! Order #$orderId for ₱$totalFormatted from " . ($orderData['customer'] ?: 'Walk-in') . " (webhook).";
                foreach ($pdo->query("SELECT id FROM users WHERE role IN ('admin','staff')") as $u) {
                    try { $stmtN->execute([$u['id'], $notifMsg, $orderId]); } catch (Exception $e) {}
                }
                send_payment_receipt_email($orderId, $orderData['customer'], $orderData['total'], $orderData['items'], $staffName);

                http_response_code(200);
                echo json_encode(['success' => true, 'order_id' => $orderId]);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
                exit;
            }
        }
    }
}

http_response_code(200);
echo json_encode(['received' => true]);
