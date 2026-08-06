<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/notifications.php';
if (!is_logged_in()) header('Location: ' . BASE_PATH . '/login.php');
refresh_user_session($pdo);
$user = current_user();

try { $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(20) DEFAULT 'Cash' AFTER change_due"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN payment_ref VARCHAR(255) NULL AFTER payment_method"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN payment_screenshot VARCHAR(255) NULL AFTER payment_ref"); } catch (PDOException $e) {}

$orderType = $_POST['type'] ?? $_GET['type'] ?? 'Dine-in';
$customer = $_POST['customer'] ?? $_GET['customer'] ?? '';
$itemsOrdered = json_decode($_POST['items'] ?? $_GET['items'] ?? '[]', true);
$paid = (float)($_POST['paid'] ?? $_GET['paid'] ?? 0);
$paymentMethod = $_POST['payment_method'] ?? $_GET['payment_method'] ?? 'Cash';
$totalAmount = (float)($_POST['total_amount'] ?? $_GET['total_amount'] ?? 0);

if (empty($itemsOrdered)) {
    echo "Invalid order data.";
    exit;
}

// Handle screenshot upload
$screenshotPath = null;
if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../uploads/payments/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $ext = strtolower(pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION));
    $allowed = ['png', 'jpg', 'jpeg', 'gif'];
    if (!in_array($ext, $allowed)) {
        die("Invalid file type.");
    }
    $filename = 'payment_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $uploadDir . $filename;
    if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $dest)) {
        $screenshotPath = 'uploads/payments/' . $filename;
    }
}

try {
    $pdo->beginTransaction();

    $placeholders = implode(',', array_fill(0, count($itemsOrdered), '?'));
    $ids = array_keys($itemsOrdered);
    $stmt = $pdo->prepare("SELECT id, name, price, stock_qty FROM inventory WHERE id IN ($placeholders) FOR UPDATE");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_UNIQUE);

    $takeoutFee = 0;
    foreach ($itemsOrdered as $id => $q) {
        if (!isset($rows[$id])) throw new Exception("Item not found (ID: $id)");
        if ($rows[$id]['stock_qty'] < $q)
            throw new Exception("Not enough stock for " . $rows[$id]['name']);
    }

    if ($orderType === 'Take-out') {
        $totalQty = array_sum($itemsOrdered);
        $takeoutFee = (int)ceil($totalQty / 2) * 5;
    }

    if ($totalAmount > 0) {
        $grandTotal = $totalAmount;
    } else {
        $total = 0;
        foreach ($itemsOrdered as $id => $q) {
            $line = $rows[$id]['price'] * $q;
            $total += $line;
        }
        $grandTotal = $total + $takeoutFee;
    }

    $change = $paid - $grandTotal;
    if ($change < 0) throw new Exception("Insufficient payment.");

    // Save order
    if ($screenshotPath) {
        $stmt = $pdo->prepare("INSERT INTO orders 
            (user_id, customer_name, order_type, takeout_fee, total_amount, amount_paid, change_due, status, payment_method, payment_screenshot)
            VALUES (?,?,?,?,?,?,?, 'completed',?,?)");
        $stmt->execute([$user['id'], $customer, $orderType, $takeoutFee, $grandTotal, $paid, $change, $paymentMethod, $screenshotPath]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO orders 
            (user_id, customer_name, order_type, takeout_fee, total_amount, amount_paid, change_due, status, payment_method)
            VALUES (?,?,?,?,?,?,?, 'completed',?)");
        $stmt->execute([$user['id'], $customer, $orderType, $takeoutFee, $grandTotal, $paid, $change, $paymentMethod]);
    }
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

    check_and_notify_low_stock($pdo);

    echo "<!DOCTYPE html>
    <html>
    <head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head>
    <body>
    <script>
    Swal.fire({
      title: 'Payment Successful!',
      html: 'Total: ₱" . number_format($grandTotal,2) . "<br>Change: ₱" . number_format($change,2) . "',
      icon: 'success',
      confirmButtonText: 'View Receipt'
    }).then(() => {
      window.location.href = 'receipt.php?id=$orderId';
    });
    </script>
    </body>
    </html>";
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>
