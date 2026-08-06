<?php
require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();

    if ($admin) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'full_name' => $admin['full_name'],
            'email' => $admin['email'],
            'email_verified_at' => $admin['email_verified_at'],
            'role' => $admin['role']
        ];
        header('Location: admin/dashboard.php');
        exit;
    } else {
        $error = "No admin user found in database.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Emergency Admin Access</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="col-md-5 mx-auto">
      <div class="card shadow">
        <div class="card-header bg-danger text-white">
          <h5 class="mb-0">Emergency Admin Access</h5>
        </div>
        <div class="card-body text-center">
          <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          <p class="text-muted">This bypasses normal login. Delete this file after use.</p>
          <form method="post">
            <button class="btn btn-danger btn-lg" type="submit">Log in as Admin</button>
          </form>
          <hr>
          <p class="small">Then go to <strong>Users</strong> to reset your password.</p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
