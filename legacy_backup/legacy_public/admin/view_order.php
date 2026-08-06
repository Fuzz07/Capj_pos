<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!is_logged_in()) header('Location: ' . BASE_PATH . '/login.php');
refresh_user_session($pdo);
$u = current_user();

if (!in_array($u['role'], ['admin', 'staff'])) {
  echo "Access denied.";
  exit;
}

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) { echo "Invalid order ID."; exit; }

header("Location: receipt.php?id=$orderId");
exit;
