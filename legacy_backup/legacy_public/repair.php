<?php
require_once __DIR__ . '/../includes/config.php';

$output = [];
$errors = [];

// 1. Fix notifications table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $output[] = "✓ notifications table ready";
} catch (PDOException $e) {
    $errors[] = "notifications table: " . $e->getMessage();
}

foreach ([
    "ALTER TABLE notifications ADD COLUMN type VARCHAR(50) DEFAULT NULL AFTER is_read",
    "ALTER TABLE notifications ADD COLUMN related_entity VARCHAR(50) DEFAULT NULL AFTER type",
    "ALTER TABLE notifications ADD COLUMN related_id INT DEFAULT NULL AFTER related_entity",
] as $sql) {
    try { $pdo->exec($sql); } catch (PDOException $e) {
        if (stripos($e->getMessage(), 'duplicate') === false) $errors[] = $e->getMessage();
    }
}

// 2. Fix password_reset_tokens table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_hash VARCHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $output[] = "✓ password_reset_tokens table ready";
} catch (PDOException $e) {
    $errors[] = "password_reset_tokens table: " . $e->getMessage();
}

// 3. Fix pending_payments table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS pending_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        payment_intent_id VARCHAR(255) NOT NULL UNIQUE,
        order_data JSON NOT NULL,
        user_id INT NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $output[] = "✓ pending_payments table ready";
} catch (PDOException $e) {
    $errors[] = "pending_payments table: " . $e->getMessage();
}

// 4. Add missing columns to users table
$userColumns = [
    "ALTER TABLE users ADD COLUMN email VARCHAR(255) NULL AFTER full_name",
    "ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email",
    "ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(64) NULL AFTER email_verified_at",
];
foreach ($userColumns as $sql) {
    try { $pdo->exec($sql); $output[] = "✓ users: " . substr($sql, strpos($sql, 'ADD COLUMN')); } catch (PDOException $e) {
        if (stripos($e->getMessage(), 'duplicate') === false) $errors[] = $e->getMessage();
    }
}

// 5. Add missing columns to orders table
$orderColumns = [
    "ALTER TABLE orders ADD COLUMN order_type ENUM('Dine-in', 'Take-out') DEFAULT 'Dine-in' AFTER customer_name",
    "ALTER TABLE orders ADD COLUMN takeout_fee DECIMAL(10,2) DEFAULT 0 AFTER order_type",
    "ALTER TABLE orders ADD COLUMN amount_paid DECIMAL(10,2) DEFAULT 0 AFTER total_amount",
    "ALTER TABLE orders ADD COLUMN change_due DECIMAL(10,2) DEFAULT 0 AFTER amount_paid",
    "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(20) DEFAULT 'Cash' AFTER change_due",
    "ALTER TABLE orders ADD COLUMN payment_ref VARCHAR(255) NULL AFTER payment_method",
    "ALTER TABLE orders ADD COLUMN payment_screenshot VARCHAR(255) NULL AFTER payment_ref",
];
foreach ($orderColumns as $sql) {
    try { $pdo->exec($sql); $output[] = "✓ orders: " . substr($sql, strpos($sql, 'ADD COLUMN')); } catch (PDOException $e) {
        if (stripos($e->getMessage(), 'duplicate') === false) $errors[] = $e->getMessage();
    }
}

// 6. Ensure admin user exists
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
$stmt->execute();
$admin = $stmt->fetch();
if ($admin) {
    $pdo->prepare("UPDATE users SET password = ?, email = 'admincapj@gmail.com', email_verified_at = NOW(), role = 'admin' WHERE id = ?")->execute([$hash, $admin['id']]);
    $output[] = "✓ Admin account updated (admin / admin123)";
} else {
    $pdo->prepare("INSERT INTO users (username, password, full_name, email, email_verified_at, role) VALUES ('admin', ?, 'Administrator', 'admincapj@gmail.com', NOW(), 'admin')")->execute([$hash]);
    $output[] = "✓ Admin account created (admin / admin123)";
}

// 7. Create upload directories
$dirs = [
    __DIR__ . '/uploads/payments',
];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) { mkdir($dir, 0777, true); $output[] = "✓ Created directory: " . basename(dirname($dir)) . "/" . basename($dir); }
}

$success = empty($errors);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>System Repair - Captain J</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f5f7f2; font-family: 'Poppins', sans-serif; padding: 2rem; }
    .card { max-width: 700px; margin: auto; border: none; border-radius: 15px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
    .card-header { background: #f10000; color: #fff; border-radius: 15px 15px 0 0; padding: 1rem; text-align: center; font-weight: 600; }
  </style>
</head>
<body>
  <div class="card">
    <div class="card-header"><h5 class="mb-0">🔧 System Repair Complete</h5></div>
    <div class="card-body p-4">
      <?php if ($output): ?>
        <div class="alert alert-success">
          <strong>✓ Repairs applied:</strong>
          <ul class="mb-0 mt-2"><?php foreach ($output as $o): ?><li><?= htmlspecialchars($o) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>
      <?php if ($errors): ?>
        <div class="alert alert-warning">
          <strong>⚠ Non-critical notices:</strong>
          <ul class="mb-0 mt-2"><?php foreach ($errors as $e): ?><li><small><?= htmlspecialchars($e) ?></small></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>
      <div class="alert alert-info">
        <strong>Admin Login:</strong> username <code>admin</code> / password <code>admin123</code>
      </div>
      <hr>
      <div class="d-flex gap-2">
        <a href="login.php" class="btn btn-danger">Go to Login</a>
        <a href="admin/dashboard.php" class="btn btn-success">Go to Dashboard</a>
        <a href="setup.php" class="btn btn-outline-secondary">Alternative: Setup Page</a>
      </div>
    </div>
  </div>
</body>
</html>
