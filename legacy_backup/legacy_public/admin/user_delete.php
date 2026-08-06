<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!is_logged_in()) header('Location: ' . BASE_PATH . '/login.php');
refresh_user_session($pdo);
$u = current_user();

if ($u['role'] !== 'admin') {
    echo 'Access denied';
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: users.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, username, full_name, email FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    echo "<p class='text-danger'>User not found.</p>";
    exit;
}

if ($user['id'] == $u['id']) {
    echo "<script>alert('You cannot delete your own account.'); window.location='users.php';</script>";
    exit;
}

if (isset($_POST['confirm_delete'])) {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id=?");
    $stmt->execute([$id]);
    $delUser = $stmt->fetchColumn();
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$id]);
    header('Location: users.php?deleted=1&name=' . urlencode($delUser ?: 'User'));
    exit;
}

if (isset($_POST['cancel'])) {
    header('Location: users.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Confirm Delete User - Captain J</title>
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
.btn-success {
  background-color: #8a9a5b;
  border: none;
}
.btn-success:hover {
  background-color: #7c8b52;
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
      <a href="users.php" class="active">Users</a>
      <a href="ordering.php?v=2">Create Order</a>
      <a href="order.php">Orders</a>
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
  <div class="card shadow-sm mx-auto" style="max-width: 500px;">
    <div class="card-header bg-danger text-white fw-bold">
      Confirm Deletion
    </div>
    <div class="card-body">
      <p>Are you sure you want to delete the user:</p>
      <h5 class="text-danger"><?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['full_name'] ?: 'N/A') ?>)</h5>
      <?php if ($user['email']): ?>
        <p class="text-muted small">Email: <?= htmlspecialchars($user['email']) ?></p>
      <?php endif; ?>
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
