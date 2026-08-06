<?php
require_once __DIR__ . '/../includes/config.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Run migration - add columns if they don't exist
        try { $pdo->exec("ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(64) NULL AFTER email_verified_at"); } catch (PDOException $e) {}

        // 2. Ensure admin user exists with correct password
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch();

        if ($admin) {
            $pdo->prepare("UPDATE users SET password = ?, email = 'admincapj@gmail.com', email_verified_at = NOW(), role = 'admin' WHERE id = ?")->execute([$hash, $admin['id']]);
            $message = "Admin account UPDATED.<br>";
        } else {
            $pdo->prepare("INSERT INTO users (username, password, full_name, email, email_verified_at, role) VALUES ('admin', ?, 'Administrator', 'admincapj@gmail.com', NOW(), 'admin')")->execute([$hash]);
            $message = "Admin account CREATED.<br>";
        }

        // 3. Auto-login as admin
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin' LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch();

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'full_name' => $admin['full_name'],
            'email' => $admin['email'],
            'email_verified_at' => $admin['email_verified_at'],
            'role' => $admin['role']
        ];

        $message .= "You are now logged in! Redirecting to dashboard...";
        $success = true;
        header('Refresh: 2; URL=admin/dashboard.php');
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Setup - Captain J</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="col-md-6 mx-auto">
      <div class="card shadow">
        <div class="card-header bg-danger text-white">
          <h5 class="mb-0">System Setup</h5>
        </div>
        <div class="card-body">
          <?php if ($message): ?>
            <div class="alert alert-<?= $success ? 'success' : 'danger' ?>"><?= $message ?></div>
            <?php if ($success): ?>
              <p><a href="admin/dashboard.php" class="btn btn-danger">Go to Dashboard</a></p>
            <?php endif; ?>
          <?php else: ?>
            <p>This will:</p>
            <ul>
              <li>Run database migration (add email columns)</li>
              <li>Create or reset the admin account</li>
              <li>Log you in automatically</li>
            </ul>
            <form method="post">
              <button type="submit" class="btn btn-danger btn-lg w-100">Setup &amp; Login as Admin</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
