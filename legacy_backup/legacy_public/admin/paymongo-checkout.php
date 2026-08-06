<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/paymongo.php';
require_once __DIR__ . '/../../includes/notifications.php';
require_once __DIR__ . '/../../includes/mail.php';

if (!is_logged_in()) header('Location: ' . APP_BASE_URL . '/login.php');
refresh_user_session($pdo);
$user = current_user();

$fromGet = isset($_GET['type']) && isset($_GET['items']);
if ($fromGet) {
    $itemsOrdered = json_decode($_GET['items'] ?? '[]', true);
    if (!empty($itemsOrdered)) {
        $orderType = $_GET['type'] ?? 'Dine-in';
        $customer = $_GET['customer'] ?? '';
        $grandTotal = (float)($_GET['total'] ?? 0);

        $takeoutFee = 0;
        if ($orderType === 'Take-out') {
            $totalQty = array_sum($itemsOrdered);
            $takeoutFee = (int)ceil($totalQty / 2) * 5;
        }

        $_SESSION['gcash_order'] = [
            'items' => $itemsOrdered,
            'customer' => $customer,
            'type' => $orderType,
            'takeout_fee' => $takeoutFee,
            'total' => $grandTotal,
        ];
        header('Location: paymongo-checkout.php');
        exit;
    }
}

$orderData = $_SESSION['gcash_order'] ?? null;
if (!$orderData) {
    echo "No order data found. Please start again.";
    exit;
}

$itemsOrdered = $orderData['items'];
$customer = $orderData['customer'];
$orderType = $orderData['type'];
$takeoutFee = $orderData['takeout_fee'];
$grandTotal = $orderData['total'];

if (empty($itemsOrdered) || $grandTotal <= 0) {
    echo "Invalid order data.";
    exit;
}

$paymongoSourceId = $_SESSION['paymongo_source_id'] ?? null;
$paymongoCheckoutUrl = $_SESSION['paymongo_checkout_url'] ?? null;

$paymongoKey = PAYMONGO_USE_TEST ? PAYMONGO_SECRET_KEY_TEST : PAYMONGO_SECRET_KEY_LIVE;
$paymongoConfigured = !empty($paymongoKey);

if ($paymongoConfigured && !$paymongoSourceId && !isset($_POST['complete_order'])) {
    try {
        $amountCentavos = (int) round($grandTotal * 100);
        $returnUrl = APP_BASE_URL . '/admin/paymongo-checkout.php';
        $source = paymongoCreateGcashSource($amountCentavos, 'Captain J Order - ' . ($customer ?: 'Walk-in'), $returnUrl);
        $sourceId = $source['data']['id'];
        $checkoutUrl = $source['data']['attributes']['redirect']['checkout_url'] ?? null;

        $_SESSION['paymongo_source_id'] = $sourceId;
        $_SESSION['paymongo_checkout_url'] = $checkoutUrl;
        $paymongoSourceId = $sourceId;
        $paymongoCheckoutUrl = $checkoutUrl;

        // Save pending payment
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS pending_payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                payment_intent_id VARCHAR(255) NOT NULL UNIQUE,
                order_data JSON NOT NULL,
                user_id INT NOT NULL,
                status VARCHAR(50) DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
            )");
            $stmt = $pdo->prepare("INSERT IGNORE INTO pending_payments (payment_intent_id, order_data, user_id, status) VALUES (?,?,?, 'pending')");
            $stmt->execute([$sourceId, json_encode($orderData), $user['id']]);
        } catch (Exception $e) {}
    } catch (Exception $e) {
        // PayMongo source creation failed - still allow manual payment
    }
}

// Handle complete order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    $isAjax = !empty($_POST['ajax']);
    $isManual = !empty($_POST['manual']);
    $refId = $_POST['ref_id'] ?? $paymongoSourceId ?? ('MANUAL-' . time());

    try {
        $pdo->beginTransaction();

        $placeholders = implode(',', array_fill(0, count($itemsOrdered), '?'));
        $ids = array_keys($itemsOrdered);
        $stmt = $pdo->prepare("SELECT id, name, price, stock_qty FROM inventory WHERE id IN ($placeholders) FOR UPDATE");
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(PDO::FETCH_UNIQUE);

        foreach ($itemsOrdered as $id => $q) {
            if (!isset($rows[$id])) throw new Exception("Item not found (ID: $id)");
            if ($rows[$id]['stock_qty'] < $q)
                throw new Exception("Not enough stock for " . $rows[$id]['name']);
        }

        $stmtOrder = $pdo->prepare("INSERT INTO orders
            (user_id, customer_name, order_type, takeout_fee, total_amount, amount_paid, change_due, status, payment_method, payment_ref)
            VALUES (?,?,?,?,?,?,?, 'completed', 'GCash', ?)");
        $stmtOrder->execute([$user['id'], $customer, $orderType, $takeoutFee, $grandTotal, $grandTotal, 0, $refId]);
        $orderId = $pdo->lastInsertId();

        $stmtInsert = $pdo->prepare("INSERT INTO order_items (order_id, inventory_id, qty, unit_price, line_total) VALUES (?,?,?,?,?)");
        $stmtUpdate = $pdo->prepare("UPDATE inventory SET stock_qty = stock_qty - ? WHERE id = ?");

        foreach ($itemsOrdered as $id => $q) {
            $unit = $rows[$id]['price'];
            $line = $unit * $q;
            $stmtInsert->execute([$orderId, $id, $q, $unit, $line]);
            $stmtUpdate->execute([$q, $id]);
        }

        $pdo->commit();

        unset($_SESSION['gcash_order']);
        unset($_SESSION['paymongo_source_id']);
        unset($_SESSION['paymongo_checkout_url']);

        check_and_notify_low_stock($pdo);

        $stmtA = $pdo->prepare("INSERT INTO notifications (user_id, type, message, related_entity, related_id) VALUES (?, 'payment', ?, 'order', ?)");
        $notifMsg = "Payment received! Order #$orderId for ₱" . number_format($grandTotal, 2) . " from " . ($customer ?: 'Walk-in') . " processed by " . ($user['full_name'] ?: $user['username']) . ".";
        foreach ($pdo->query("SELECT id FROM users WHERE role IN ('admin','staff')") as $u) {
            try { $stmtA->execute([$u['id'], $notifMsg, $orderId]); } catch (Exception $e) {}
        }
        send_payment_receipt_email($orderId, $customer, $grandTotal, $itemsOrdered, $user['full_name'] ?: $user['username']);

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'order_id' => $orderId]);
            exit;
        }

        unset($_SESSION['gcash_order']);
        unset($_SESSION['paymongo_source_id']);
        unset($_SESSION['paymongo_checkout_url']);

        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Payment Received - Captain J</title>';
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
        echo '<style>body{background:linear-gradient(135deg,#faf9f6,#f8f8ff);font-family:Poppins,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px;}.card{border:none;border-radius:20px;box-shadow:0 12px 30px rgba(0,0,0,0.12);max-width:420px;width:100%;}.card-body{padding:2rem;text-align:center;}.checkmark{font-size:4rem;color:#28a745;}.amount{font-size:2rem;font-weight:800;color:#f10000;}.detail{color:#666;font-size:0.9rem;}</style>';
        echo '</head><body><div class="card"><div class="card-body">';
        echo '<div class="checkmark">✓</div>';
        echo '<h4 class="fw-bold mt-2" style="color:#28a745;">Payment Received</h4>';
        echo '<div class="amount mb-2">₱' . number_format($grandTotal, 2) . '</div>';
        echo '<p class="mb-1"><strong>Customer:</strong> ' . htmlspecialchars($customer ?: 'Walk-in') . '</p>';
        echo '<p class="mb-1"><strong>Order #:</strong> ' . $orderId . '</p>';
        echo '<p class="mb-3"><strong>Payment:</strong> GCash</p>';
        echo '<p class="text-muted small">Transaction recorded and synced to PayMongo.</p>';
        echo '<hr>';
        echo '<div class="d-grid gap-2">';
        echo '<a href="receipt.php?id=' . $orderId . '" class="btn btn-success btn-lg fw-bold">View Receipt</a>';
        echo '<a href="ordering.php?v=2" class="btn btn-outline-secondary">New Order</a>';
        echo '</div></div></div></body></html>';
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
        $error = $e->getMessage();
    }
}

$totalFormatted = number_format($grandTotal, 2);
$gcashQrPath = __DIR__ . '/../' . GCASH_QR_IMAGE;
$hasQr = file_exists($gcashQrPath);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>GCash Payment - Captain J</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #faf9f6, #f8f8ff);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-custom {
            background: #fff;
            border: none;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            max-width: 480px;
            width: 100%;
            margin: 0 auto;
        }
        .card-header-custom {
            background: #f10000;
            color: #fff;
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
            padding: 1rem;
            text-align: center;
            font-weight: 700;
            font-size: 1.15rem;
        }
        .total-display {
            font-size: 1.8rem;
            font-weight: 800;
            color: #f10000;
            text-align: center;
            padding: 0.5rem 0;
        }
        .qr-container {
            text-align: center;
            padding: 0.5rem;
        }
        .qr-container img {
            max-width: 260px;
            width: 100%;
            height: auto;
            border: 3px solid #0057a3;
            border-radius: 14px;
            padding: 12px;
            background: #fff;
        }
        .btn-gcash {
            background-color: #0057a3;
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 12px;
            padding: 14px;
            font-size: 1rem;
        }
        .btn-gcash:hover { background-color: #003d73; }
        .order-summary {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 0.8rem 1rem;
            font-size: 0.9rem;
        }
        .order-summary .row-item {
            display: flex;
            justify-content: space-between;
            padding: 0.2rem 0;
        }
        .steps-list {
            font-size: 0.85rem;
            color: #555;
            text-align: left;
            margin: 0.5rem 0;
        }
        .steps-list li { margin-bottom: 0.3rem; }
        .paymongo-note {
            font-size: 0.75rem;
            color: #999;
            text-align: center;
            margin-top: 0.5rem;
        }
        .paymongo-note a { color: #0057a3; }
        @media (max-width: 400px) {
            .total-display { font-size: 1.4rem; }
            .qr-container img { max-width: 200px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card card-custom shadow-lg mx-auto">
            <div class="card-header-custom">GCash Payment</div>
            <div class="card-body p-4">

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><small><?= htmlspecialchars($error) ?></small></div>
                <?php endif; ?>

                <div class="total-display">₱<?= $totalFormatted ?></div>

                <div class="order-summary">
                    <div class="row-item">
                        <span>Customer</span>
                        <strong><?= htmlspecialchars($customer ?: 'Walk-in') ?></strong>
                    </div>
                    <div class="row-item">
                        <span>Order Type</span>
                        <strong><?= htmlspecialchars(ucfirst($orderType)) ?></strong>
                    </div>
                    <div class="row-item">
                        <span>Items</span>
                        <strong><?= array_sum($itemsOrdered) ?> pcs</strong>
                    </div>
                </div>

                <div class="text-center mb-1">
                    <strong style="color:#0057a3;">Pay via GCash</strong>
                </div>

                <?php if ($hasQr): ?>
                <div class="qr-container">
                    <img src="<?= BASE_PATH ?>/<?= GCASH_QR_IMAGE ?>?t=<?= filemtime($gcashQrPath) ?>" alt="GCash QR Code">
                </div>
                <?php endif; ?>

                <ol class="steps-list">
                    <li>Open <strong>GCash</strong> app and tap <strong>Scan QR</strong></li>
                    <li>Scan the QR code or send to <strong><?= htmlspecialchars(GCASH_NUMBER) ?></strong></li>
                    <li>Enter amount: <strong class="text-danger">₱<?= $totalFormatted ?></strong></li>
                    <li>Send payment and wait for confirmation</li>
                    <li><strong>Staff:</strong> verify payment in GCash app, then click confirm</li>
                </ol>

                <?php if ($paymongoCheckoutUrl): ?>
                <div class="paymongo-note">
                    💳 Also available via <a href="<?= htmlspecialchars($paymongoCheckoutUrl) ?>" target="_blank">PayMongo online payment</a>
                </div>
                <?php endif; ?>

                <form method="post" class="mt-3">
                    <input type="hidden" name="complete_order" value="1">
                    <input type="hidden" name="manual" value="1">
                    <?php if ($paymongoSourceId): ?>
                    <input type="hidden" name="ref_id" value="<?= htmlspecialchars($paymongoSourceId) ?>">
                    <?php endif; ?>
                    <div class="d-grid">
                        <button type="button" class="btn btn-gcash btn-lg" id="confirmPaymentBtn">
                            ✓ Payment Received — Create Order
                        </button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <a href="ordering.php?v=2" class="btn btn-outline-secondary btn-sm">⬅ Cancel</a>
                </div>

            </div>
        </div>
    </div>

<script>
document.getElementById('confirmPaymentBtn').addEventListener('click', function() {
    Swal.fire({
        title: 'Confirm Payment',
        html: 'Have you verified the payment of <strong>₱<?= $totalFormatted ?></strong> from <strong><?= htmlspecialchars($customer ?: "Walk-in") ?></strong> in your GCash app?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Payment Received',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0057a3',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            this.closest('form').submit();
        }
    });
});
</script>
</body>
</html>
