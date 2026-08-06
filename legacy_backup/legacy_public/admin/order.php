<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!is_logged_in()) header('Location: ' . BASE_PATH . '/login.php');
refresh_user_session($pdo);
$u = current_user();

// Fetch all orders with user info
$sql = "SELECT o.*, u.full_name AS staff_name, u.username 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Orders - Captain J</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/responsive.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
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
      overflow-y: auto;
    }
    .card {
      background: #fff;
      border: none;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      margin-bottom: 1.5rem;
    }
    .btn-sage {
      background-color: #18b318;
      color: #fff;
      border: none;
    }
    .btn-sage:hover {
      background-color: #18b318;
    }
    .btn-danger {
      font-size: 13px;
    }
    .search-bar {
      margin-bottom: 1rem;
    }
    .badge-status {
      text-transform: capitalize;
    }
  </style>
</head>
<body>
  <button class="hamburger">&#9776;</button>
  <div class="sidebar-overlay"></div>
  <!-- Sidebar -->
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

  <!-- Main Content -->
  <div class="main-content">
    <h4 class="mb-4">Orders List</h4>

    <div class="d-flex justify-content-end mb-3">
      <input type="text" id="search" class="form-control" placeholder="Search by Customer, Staff, or Status..." style="width:400px; border-radius:8px; padding:8px 12px; border:1px solid #ccc;">
    </div>

    <div class="card p-3">
      <div class="table-responsive-wrap">
      <table class="table table-bordered table-hover align-middle text-center">
        <thead class="table-success">
          <tr>
            <th>Customer</th>
            <th>Total (₱)</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Created</th>
            <th>Admin/Staff</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="orderTable">
          <?php foreach ($orders as $o): ?>
          <tr>
            <td><?= htmlspecialchars($o['customer_name'] ?: 'Walk-in') ?></td>
            <td><?= number_format($o['total_amount'], 2) ?></td>
            <td>
              <?php $pm = $o['payment_method'] ?? 'Cash'; ?>
              <?php if (strtolower($pm) === 'gcash'): ?>
                <span class="badge" style="background:#0057a3;">📱 GCash</span>
              <?php else: ?>
                <span class="badge bg-success">💵 Cash</span>
              <?php endif; ?>
              <?php if (!empty($o['payment_screenshot'])): ?>
                <br><a href="<?= BASE_PATH ?>/admin/<?= htmlspecialchars($o['payment_screenshot']) ?>" target="_blank" class="small">View Screenshot</a>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge 
                <?= $o['status'] === 'completed' ? 'bg-success' : ($o['status'] === 'pending' ? 'bg-warning' : 'bg-danger') ?> badge-status">
                <?= htmlspecialchars($o['status']) ?>
              </span>
            </td>
            <td><?= htmlspecialchars(date('Y-m-d h:i A', strtotime($o['created_at']))) ?></td>
            <td><?= htmlspecialchars($o['staff_name'] ?: $o['username']) ?></td>
            <td>
              <a href="view_order.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-sage">View</a>

              <?php if ($u['role'] === 'admin'): ?>
                <a href="order_delete.php?id=<?= $o['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>

  <script>
  // 🔍 Search Filter
  $('#search').on('keyup', function() {
    let value = $(this).val().toLowerCase();
    $('#orderTable tr').filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
  });
  </script>
  <script src="../js/mobile-sidebar.js"></script>
</body>
</html>
