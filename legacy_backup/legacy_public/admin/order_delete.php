<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!is_logged_in()) {
    header('Location: ' . APP_BASE_URL . '/login.php');
    exit;
}

refresh_user_session($pdo);
$u = current_user();

if ($u['role'] !== 'admin') {
    echo 'Access denied';
    exit;
}

$orderId = (int)($_GET['id'] ?? 0);

if (!$orderId) {
    header('Location: order.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.*, u.full_name AS staff_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<p class='text-danger'>Order not found.</p>";
    exit;
}

if (isset($_POST['confirm_delete'])) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);

        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);

        $pdo->commit();

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Order deleted successfully.'
        ];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Unable to delete this order. Please try again.'
        ];
    }

    header('Location: order.php');
    exit;
}

if (isset($_POST['cancel'])) {
    header('Location: order.php');
    exit;
}

$orderLabel = 'Order #' . $order['id'] . ' - ' . ($order['customer_name'] ?: 'Walk-in') . ' (₱' . number_format($order['total_amount'], 2) . ')';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Confirm Delete - Captain J</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/responsive.css">
<style>
body {
  font-family: 'Poppins', sans-serif;
  background-color: #f5f7f2;
  color: #2f3b2f;
  display: flex;
  height: 100vh;
  margin: 0;
}
.sidebar {
  width: 230px;
  background-color: #f10000;
  color: #fff;
  padding-top: 1.5rem;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
}
.sidebar a {
  color: #fff;
  text-decoration: none;
  display: block;
  padding: 12px 20px;
  transition: 0.3s;
  border-left: 4px solid transparent;
}
.sidebar a:hover, .sidebar a.active {
  background-color: #ef9a9a;
  border-left: 4px solid #fff;
}
.sidebar-footer {
  padding: 1rem;
  text-align: center;
  border-top: 1px solid rgba(255,255,255,0.2);
}
.main-content {
  margin-left: 230px;
  padding: 2rem;
  width: 100%;
}
</style>

</head>
<body>

<button class="hamburger">&#9776;</button>
<div class="sidebar-overlay"></div>

<div class="sidebar">
  <div>
    <h5 class="text-center mb-4"><img src="<?= BASE_PATH ?>/images/capj.jpg" alt="Captain J" style="float: center; width: 30%; border-radius: 200px 200px 200px 200px; border: 3px solid #fff; margin-bottom: 15px;"> Captain J  <?= ucfirst($u['role']) ?></h5>

    <?php if ($u['role'] === 'admin'): ?>
      <a href="profile.php">Profile</a>
      <a href="dashboard.php">Dashboard</a>
      <a href="inventory.php">Inventory</a>
      <a href="users.php">Users</a>
      <a href="ordering.php?v=2">Create Order</a>
      <a href="order.php" class="active">Orders</a>
    <?php elseif ($u['role'] === 'staff'): ?>
      <a href="profile.php">Profile</a>
      <a href="inventory.php">Inventory</a>
      <a href="ordering.php?v=2">Create Order</a>
      <a href="order.php" class="active">Orders</a>
    <?php endif; ?>
  </div>

  <div class="sidebar-footer">
    <p class="mb-1 small">Logged in as:</p>
    <strong><?= htmlspecialchars($u['full_name'] ?? $u['username']) ?></strong><br>
    <a href="<?= BASE_PATH ?>/logout.php" class="btn btn-secondary btn-sm mt-2">Logout</a>
  </div>
</div>

<div class="main-content">
  <div class="card shadow-sm mx-auto" style="max-width: 500px;">
    <div class="card-header bg-danger text-white fw-bold">
      Confirm Deletion
    </div>
    <div class="card-body">
      <p>Are you sure you want to delete the order:</p>
      <h5 class="text-danger"><?= htmlspecialchars($orderLabel) ?></h5>
      <form method="post" class="mt-3">
        <button type="submit" name="confirm_delete" class="btn btn-danger">Yes, Delete</button>
        <button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
      </form>
    </div>
  </div>
</div>

<script src="../js/mobile-sidebar.js"></script>
</body>
</html>
