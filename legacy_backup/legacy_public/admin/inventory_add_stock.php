<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/notifications.php';

if (!is_logged_in()) {
  http_response_code(403);
  exit('Not logged in');
}

$u = current_user();

if (!in_array($u['role'], ['admin', 'staff'])) {
  http_response_code(403);
  exit('Access denied');
}

if (isset($_POST['id'])) {
  $id = (int)$_POST['id'];
  
  // Increase stock by 1
  $stmt = $pdo->prepare("UPDATE inventory SET stock_qty = stock_qty + 1, updated_at = NOW() WHERE id = ?");
  $stmt->execute([$id]);

  // Get new stock qty
  $stmt = $pdo->prepare("SELECT stock_qty FROM inventory WHERE id = ?");
  $stmt->execute([$id]);
  $newQty = $stmt->fetchColumn();

  // If stock is now above threshold, remove low stock notification for this item
  if ($newQty > LOW_STOCK_THRESHOLD) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE type = 'low_stock' AND related_id = ? AND is_read = 0")
        ->execute([$id]);
  }

  echo $newQty;
}
