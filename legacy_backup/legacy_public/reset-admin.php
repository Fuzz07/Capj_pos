<?php
require_once __DIR__ . '/../includes/config.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, email = 'admincapj@gmail.com', email_verified_at = NOW() WHERE username = 'admin'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $message = "Admin account reset successfully!<br>Username: <strong>admin</strong><br>Password: <strong>admin123</strong><br>Email: <strong>admincapj@gmail.com</strong>";
            $success = true;
        } else {
            $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, full_name, email, email_verified_at, role) VALUES ('admin', ?, 'Administrator', 'admincapj@gmail.com', NOW(), 'admin')");
            $stmt->execute([$hash]);
            $message = "Admin account created!<br>Username: <strong>admin</strong><br>Password: <strong>admin123</strong>";
            $success = true;
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reset Admin - Captain J</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="col-md-6 mx-auto">
      <div class="card shadow">
        <div class="card-header bg-danger text-white">
          <h5 class="mb-0">Admin Account Reset</h5>
        </div>
        <div class="card-body">
          <?php if ($message): ?>
            <div class="alert alert-<?= $success ? 'success' : 'danger' ?>"><?= $message ?></div>
            <?php if ($success): ?>
              <a href="login.php" class="btn btn-danger">Go to Login</a>
            <?php endif; ?>
          <?php else: ?>
            <p>This will reset the admin account to default credentials:</p>
            <ul>
              <li><strong>Username:</strong> admin</li>
              <li><strong>Password:</strong> admin123</li>
              <li><strong>Email:</strong> admincapj@gmail.com</li>
            </ul>
            <form method="post">
              <button type="submit" class="btn btn-danger" onclick="return confirm('Reset admin account?')">Reset Admin Account</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
