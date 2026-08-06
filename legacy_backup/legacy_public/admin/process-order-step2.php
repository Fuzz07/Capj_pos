<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
if (!is_logged_in()) header('Location: ' . BASE_PATH . '/login.php');
refresh_user_session($pdo);
$user = current_user();

$orderType = $_GET['type'] ?? 'Dine-in';
$customer = $_GET['customer'] ?? '';
$itemsOrdered = json_decode($_GET['items'] ?? '[]', true);

if (empty($itemsOrdered)) {
    echo "Invalid order data.";
    exit;
}

try {
    $placeholders = implode(',', array_fill(0, count($itemsOrdered), '?'));
    $ids = array_keys($itemsOrdered);

    $stmt = $pdo->prepare("SELECT id, name, price, stock_qty FROM inventory WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_UNIQUE);

    $total = 0;
    $takeoutFee = 0;

    foreach ($itemsOrdered as $id => $q) {
        if (!isset($rows[$id])) throw new Exception("Item not found (ID: $id)");
        if ($rows[$id]['stock_qty'] < $q)
            throw new Exception("Not enough stock for " . $rows[$id]['name']);

        $line = $rows[$id]['price'] * $q;
        $total += $line;
    }

    if ($orderType === 'Take-out') {
        $totalQty = array_sum($itemsOrdered);
        $takeoutFee = (int)ceil($totalQty / 2) * 5;
    }

    $grandTotal = $total + $takeoutFee;
    $itemsJson = urlencode(json_encode($itemsOrdered));
    $typeParam = urlencode($orderType);
    $customerParam = urlencode($customer);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Select Payment - Captain J</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    * { box-sizing: border-box; }
    body {
      background: linear-gradient(135deg, #faf9f6, #f8f8ff);
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0;
      padding: 20px;
    }
    .payment-card {
      background: #fff;
      border: none;
      border-radius: 20px;
      box-shadow: 0 12px 30px rgba(0,0,0,0.12);
      max-width: 500px;
      width: 100%;
      overflow: hidden;
    }
    .payment-header {
      background: #f10000;
      color: #fff;
      text-align: center;
      padding: 1.5rem 1rem;
    }
    .payment-header h4 { margin: 0; font-weight: 700; }
    .payment-header .total-amount {
      font-size: 2rem;
      font-weight: 800;
      margin-top: 0.5rem;
    }
    .payment-body { padding: 2rem; }
    .payment-option {
      border: 2px solid #e0e0e0;
      border-radius: 16px;
      padding: 1.5rem;
      text-align: center;
      cursor: pointer;
      transition: all 0.25s ease;
      text-decoration: none;
      display: block;
      color: inherit;
    }
    .payment-option:hover {
      border-color: #f10000;
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(241,0,0,0.12);
      color: inherit;
    }
    .payment-option .icon {
      font-size: 3rem;
      margin-bottom: 0.5rem;
    }
    .payment-option .label {
      font-size: 1.2rem;
      font-weight: 700;
    }
    .payment-option .desc {
      font-size: 0.85rem;
      color: #666;
      margin-top: 0.3rem;
    }
    .divider {
      display: flex;
      align-items: center;
      text-align: center;
      margin: 1.5rem 0;
      color: #999;
    }
    .divider::before, .divider::after {
      content: '';
      flex: 1;
      border-bottom: 1px solid #e0e0e0;
    }
    .divider span { padding: 0 1rem; font-size: 0.85rem; }
    .order-summary {
      background: #f8f9fa;
      border-radius: 12px;
      padding: 1rem;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
    }
    .order-summary .row-item {
      display: flex;
      justify-content: space-between;
      padding: 0.25rem 0;
    }
    .back-link {
      text-align: center;
      margin-top: 1rem;
    }
    .back-link a { color: #999; font-size: 0.85rem; }
  </style>
</head>
<body>

  <div class="payment-card">
    <div class="payment-header">
      <h4>Captain J</h4>
      <div class="total-amount">₱<?= number_format($grandTotal, 2) ?></div>
      <div style="font-size:0.9rem;opacity:0.9;">Total Amount</div>
    </div>

    <div class="payment-body">
      <div class="order-summary">
        <div class="row-item">
          <span>Customer</span>
          <strong><?= htmlspecialchars($customer ?: 'Walk-in') ?></strong>
        </div>
        <div class="row-item">
          <span>Order Type</span>
          <strong><?= htmlspecialchars($orderType) ?></strong>
        </div>
        <div class="row-item">
          <span>Items</span>
          <strong><?= array_sum($itemsOrdered) ?> pcs</strong>
        </div>
        <?php if ($takeoutFee > 0): ?>
        <div class="row-item">
          <span>Take-out Fee</span>
          <strong>₱<?= number_format($takeoutFee, 2) ?></strong>
        </div>
        <?php endif; ?>
      </div>

      <label style="font-weight:600;margin-bottom:1rem;display:block;">Select Payment Method</label>

      <div class="row g-3">
        <div class="col-6">
          <a href="#" class="payment-option" id="cashOption">
            <div class="icon">💵</div>
            <div class="label">Cash</div>
            <div class="desc">Pay with cash</div>
          </a>
        </div>
        <div class="col-6">
          <a href="paymongo-checkout.php?type=<?= $typeParam ?>&customer=<?= $customerParam ?>&items=<?= $itemsJson ?>&total=<?= $grandTotal ?>" class="payment-option">
            <div class="icon">📱</div>
            <div class="label">GCash</div>
            <div class="desc">Pay with GCash</div>
          </a>
        </div>
      </div>

      <div class="back-link">
        <a href="ordering.php?v=2">← Back to Order</a>
      </div>
    </div>
  </div>

  <script>
  document.getElementById('cashOption').addEventListener('click', function(e) {
    e.preventDefault();
    Swal.fire({
      title: 'Enter Cash Amount',
      input: 'number',
      inputAttributes: {
        min: <?= ceil($grandTotal) ?>,
        step: 1
      },
      confirmButtonText: 'Pay',
      showCancelButton: true,
      cancelButtonText: 'Cancel',
      inputValidator: (value) => {
        if (!value || parseFloat(value) < <?= $grandTotal ?>) {
          return 'Amount must be at least ₱<?= number_format($grandTotal, 2) ?>';
        }
      }
    }).then((r) => {
      if (r.value) {
        window.location.href = 'process-order-final.php?type=<?= $typeParam ?>&customer=<?= $customerParam ?>&paid=' + r.value + '&items=<?= $itemsJson ?>';
      }
    });
  });
  </script>

</body>
</html>
<?php
    exit;
} catch (Exception $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>
