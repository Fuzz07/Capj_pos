<?php
require_once __DIR__ . '/../../includes/config.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        otp_code VARCHAR(6) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_otp_code (otp_code),
        INDEX idx_user_used (user_id, used_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✓ password_reset_tokens table ready.<br>\n";
} catch (PDOException $e) {
    echo "✗ password_reset_tokens: " . $e->getMessage() . "<br>\n";
}
try { $pdo->exec("ALTER TABLE password_reset_tokens CHANGE COLUMN token_hash otp_code VARCHAR(6) NOT NULL"); echo "✓ Renamed token_hash → otp_code<br>\n"; } catch (PDOException $e) { echo "  token_hash rename: " . $e->getMessage() . "<br>\n"; }
try { $pdo->exec("ALTER TABLE password_reset_tokens ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE"); } catch (PDOException $e) {}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✓ notifications table ready.<br>\n";
} catch (PDOException $e) {
    echo "✗ notifications: " . $e->getMessage() . "<br>\n";
}

try {
    $pdo->exec("ALTER TABLE notifications ADD COLUMN type VARCHAR(50) DEFAULT NULL AFTER is_read");
    echo "✓ notifications.type added.<br>\n";
} catch (PDOException $e) {
    echo "  notifications.type: " . $e->getMessage() . "<br>\n";
}

try {
    $pdo->exec("ALTER TABLE notifications ADD COLUMN related_entity VARCHAR(50) DEFAULT NULL AFTER type");
    echo "✓ notifications.related_entity added.<br>\n";
} catch (PDOException $e) {
    echo "  notifications.related_entity: " . $e->getMessage() . "<br>\n";
}

try {
    $pdo->exec("ALTER TABLE notifications ADD COLUMN related_id INT DEFAULT NULL AFTER related_entity");
    echo "✓ notifications.related_id added.<br>\n";
} catch (PDOException $e) {
    echo "  notifications.related_id: " . $e->getMessage() . "<br>\n";
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email");
    echo "✓ users.email_verified_at added.<br>\n";
} catch (PDOException $e) {
    echo "  users.email_verified_at: " . $e->getMessage() . "<br>\n";
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(64) NULL AFTER email_verified_at");
    echo "✓ users.email_verification_token added.<br>\n";
} catch (PDOException $e) {
    echo "  users.email_verification_token: " . $e->getMessage() . "<br>\n";
}

try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(20) DEFAULT 'Cash' AFTER change_due");
    echo "✓ orders.payment_method added.<br>\n";
} catch (PDOException $e) {
    echo "  orders.payment_method: " . $e->getMessage() . "<br>\n";
}

try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN payment_ref VARCHAR(255) NULL AFTER payment_method");
    echo "✓ orders.payment_ref added.<br>\n";
} catch (PDOException $e) {
    echo "  orders.payment_ref: " . $e->getMessage() . "<br>\n";
}

try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN payment_screenshot VARCHAR(255) NULL AFTER payment_ref");
    echo "✓ orders.payment_screenshot added.<br>\n";
} catch (PDOException $e) {
    echo "  orders.payment_screenshot: " . $e->getMessage() . "<br>\n";
}

echo "<br>Done.";
