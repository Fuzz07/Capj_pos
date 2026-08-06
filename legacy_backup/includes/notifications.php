<?php
require_once __DIR__ . '/config.php';

function get_low_stock_items($pdo) {
    $stmt = $pdo->prepare(
        "SELECT id, name, stock_qty FROM inventory WHERE is_active = 1 AND stock_qty <= ? ORDER BY stock_qty ASC"
    );
    $stmt->execute([LOW_STOCK_THRESHOLD]);
    return $stmt->fetchAll();
}

function create_low_stock_notification($pdo, $item_id, $item_name, $stock_qty) {
    $stmt = $pdo->prepare(
        "SELECT id FROM notifications WHERE type = 'low_stock' AND related_id = ? AND is_read = 0"
    );
    $stmt->execute([$item_id]);
    if ($stmt->fetch()) {
        return false;
    }
    $message = "Low stock alert: '{$item_name}' only has {$stock_qty} item(s) left (threshold: " . LOW_STOCK_THRESHOLD . ").";
    $stmt = $pdo->prepare(
        "INSERT INTO notifications (type, message, related_entity, related_id) VALUES ('low_stock', ?, 'inventory', ?)"
    );
    $stmt->execute([$message, $item_id]);
    return true;
}

function get_unread_notifications($pdo, $limit = 20) {
    $limit = (int)$limit;
    $stmt = $pdo->prepare(
        "SELECT id, type, message, created_at FROM notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT ?"
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_unread_notification_count($pdo) {
    return (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();
}

function mark_notification_read($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$id]);
}

function mark_all_notifications_read($pdo) {
    $pdo->exec("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
}

function check_and_notify_low_stock($pdo) {
    $items = get_low_stock_items($pdo);
    $new_notifications = [];
    $email_result = ['success' => false, 'error' => 'No low stock items'];

    foreach ($items as $item) {
        $created = create_low_stock_notification($pdo, $item['id'], $item['name'], $item['stock_qty']);
        if ($created) {
            $new_notifications[] = $item;
        }
    }

    if (!empty($items)) {
        require_once __DIR__ . '/mail.php';
        $email_result = send_low_stock_email($items, $pdo);
    }

    return [
        'new_notifications' => $new_notifications,
        'email' => $email_result
    ];
}
