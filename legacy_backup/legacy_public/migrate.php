<?php
require_once __DIR__ . '/../includes/config.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email");
        $message = "Column 'email_verified_at' added.";
        $success = true;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false || strpos($e->getMessage(), 'Duplicate') !== false) {
            $success = true;
            $message = "Column 'email_verified_at' already exists.";
        } else {
            $message = "Error: " . $e->getMessage();
        }
    }

    if ($success) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(64) NULL AFTER email_verified_at");
            $message .= " Column 'email_verification_token' added.";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false || strpos($e->getMessage(), 'Duplicate') !== false) {
                $message .= " Column 'email_verification_token' already exists.";
            } else {
                $message .= " Error: " . $e->getMessage();
                $success = false;
            }
        }
    }

    if ($success) {
        $pdo->exec("UPDATE users SET email = 'admincapj@gmail.com', email_verified_at = NOW() WHERE username = 'admin' AND (email IS NULL OR email = '')");
        $count = $pdo->affectedRows();
        if ($count > 0) {
            $message .= " Admin email set to admincapj@gmail.com and marked as verified.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Database Migration - Captain J</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="col-md-6 mx-auto">
      <div class="card shadow">
        <div class="card-header bg-danger text-white">
          <h5 class="mb-0">Database Migration</h5>
        </div>
        <div class="card-body">
          <p>This will add email verification columns to the <code>users</code> table.</p>
          <?php if ($message): ?>
            <div class="alert alert-<?= $success ? 'success' : 'danger' ?>"><?= htmlspecialchars($message) ?></div>
          <?php endif; ?>
          <form method="post">
            <button type="submit" class="btn btn-danger">Run Migration</button>
          </form>
          <hr>
          <p class="text-muted small">If the button above fails, run this SQL directly in phpMyAdmin or MySQL CLI:</p>
          <pre class="bg-dark text-light p-3 rounded"><code>ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email;
ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(64) NULL AFTER email_verified_at;</code></pre>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
