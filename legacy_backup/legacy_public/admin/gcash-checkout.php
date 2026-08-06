<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/notifications.php';
require_once __DIR__ . '/../../includes/mail.php';
try { $pdo->exec("ALTER TABLE orders ADD COLUMN payment_ref VARCHAR(255) NULL AFTER payment_method"); } catch (PDOException $e) {}
if (!is_logged_in()) header('Location: ' . APP_BASE_URL . '/login.php');
refresh_user_session($pdo);
$user = current_user();

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

$gcashNumber = GCASH_NUMBER;
$totalFormatted = number_format($grandTotal, 2);

// Handle manual confirm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
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
        $stmtOrder->execute([$user['id'], $customer, $orderType, $takeoutFee, $grandTotal, $grandTotal, 0, 'Manual GCash']);
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

        check_and_notify_low_stock($pdo);

        send_payment_receipt_email($orderId, $customer, $grandTotal, $itemsOrdered, $user['full_name'] ?: $user['username']);

        header('Location: receipt.php?id=' . $orderId);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>GCash Payment - Captain J</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #faf9f6, #f8f8ff);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-custom {
            background: #fff;
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            max-width: 480px;
            width: 100%;
        }
        .card-header-custom {
            background: #f10000;
            color: #fff;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            font-size: 1.2rem;
        }
        .total-display {
            font-size: 1.5rem;
            font-weight: bold;
            color: #f10000;
            text-align: center;
        }
        .gcash-number {
            font-size: 1.6rem;
            font-weight: bold;
            color: #0057a3;
            text-align: center;
            letter-spacing: 2px;
            background: #e8f4fd;
            padding: 10px;
            border-radius: 10px;
        }
        .qr-container {
            text-align: center;
            padding: 1rem;
        }
        .qr-container img {
            max-width: 220px;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 10px;
        }
        .qr-container {
            text-align: center;
            padding: 1rem;
        }
        .qr-container img {
            max-width: 300px;
            width: 100%;
            height: auto;
            border: 3px solid #0057a3;
            border-radius: 12px;
            padding: 12px;
            background: #fff;
        }
        .btn-gcash {
            background-color: #0057a3;
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 10px;
            padding: 12px;
        }
        .btn-gcash:hover {
            background-color: #003d73;
        }
    </style>

</head>
<body>
    <div class="container">
        <div class="card card-custom shadow-lg mx-auto">
            <div class="card-header-custom">GCash Payment</div>
            <div class="card-body p-4">
                <div class="total-display">Total: ₱<?= $totalFormatted ?></div>
                <hr>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <p class="text-center mb-1"><strong>Scan to pay via GCash:</strong></p>

                <?php
                $qrUrl = APP_BASE_URL . '/' . GCASH_QR_IMAGE;
                $qrFullPath = __DIR__ . '/../' . GCASH_QR_IMAGE;
                if (file_exists($qrFullPath)):
                ?>
                <div class="qr-container">
                    <img src="<?= htmlspecialchars($qrUrl) ?>" alt="GCash QR Code">
                </div>
                <?php else: ?>
                <div class="alert alert-warning text-center small">
                    Store QR code not set up yet. Customer can use the number below.
                </div>
                <?php endif; ?>

                <p class="text-center mb-1"><strong>Or send to this GCash number:</strong></p>
                <div class="gcash-number"><?= htmlspecialchars($gcashNumber) ?></div>

                <div class="alert alert-info text-center small">
                    <strong>How it works:</strong>
                    <ol class="text-start mb-0 mt-1">
                        <li>Customer opens <strong>GCash</strong> app</li>
                        <li>Taps <strong>Scan QR</strong> and scans the QR above,<br>or taps <strong>Send Money</strong> and enters the number</li>
                        <li>Enters amount: <strong>₱<?= $totalFormatted ?></strong></li>
                        <li>Completes payment</li>
                        <li><strong>Staff verifies</strong> payment, then clicks confirm below</li>
                    </ol>
                </div>

                <form method="post" onsubmit="return confirm('Have you verified the payment in your GCash app? Click OK only if payment is received.')">
                    <div class="d-grid gap-2">
                        <button type="submit" name="confirm" value="1" class="btn btn-gcash btn-lg">
                            ✓ Payment Received — Create Order
                        </button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <a href="ordering.php?v=2" class="btn btn-outline-secondary btn-sm">Cancel Order</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
