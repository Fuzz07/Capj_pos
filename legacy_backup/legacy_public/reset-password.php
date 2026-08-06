<?php
require_once __DIR__ . '/../includes/config.php';

$errors = [];
$success = '';
$token_valid = false;
$user_id = null;

$token = $_GET['token'] ?? $_POST['token'] ?? '';

if ($token === '') {
    $errors[] = "Missing reset token.";
} else {
    $token_hash = hash('sha256', $token);
    $stmt = $pdo->prepare("SELECT prt.id, prt.user_id, prt.expires_at FROM password_reset_tokens prt WHERE prt.token_hash = ? AND prt.used_at IS NULL LIMIT 1");
    $stmt->execute([$token_hash]);
    $row = $stmt->fetch();

    if ($row) {
        if (strtotime($row['expires_at']) < time()) {
            $errors[] = "This reset link has expired. Please request a new one.";
        } else {
            $token_valid = true;
            $user_id = $row['user_id'];
        }
    } else {
        $errors[] = "Invalid or already used reset token.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character (e.g. !@#$%).';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $user_id]);
        $pdo->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL")->execute([$user_id]);
        $success = 'Password has been reset successfully. You can now login.';
        $token_valid = false;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reset Password - Captain J</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/responsive.css">
  <style>
    body {
      background: linear-gradient(135deg, #faf9f6, #f8f8ff);
      font-family: 'Poppins', sans-serif;
      color: #2f3b2f;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .card-custom {
      background: #f6f7f2;
      border: none;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    .card-header-custom {
      background: #f10000;
      color: #fff;
      border-top-left-radius: 15px;
      border-top-right-radius: 15px;
      padding: 1rem;
      text-align: center;
      font-weight: 600;
    }
    .form-control {
      border-radius: 10px;
      border: 1px solid #ef9a9a;
    }
    .form-control:focus {
      border-color: #ef9a9a;
      box-shadow: 0 0 5px rgba(138,154,91,0.3);
    }
    .btn-custom {
      background-color: #f10000;
      border: none;
      color: #fff;
      font-weight: 500;
      border-radius: 10px;
    }
    .btn-custom:hover {
      background-color: #ef9a9a;
      transform: scale(1.03);
    }
    .footer-text {
      text-align: center;
      margin-top: 15px;
      color: #b69a9a;
      font-size: 0.9rem;
    }
    @media (max-width: 576px) {
      .container { padding: 0 12px; }
      .col-md-5 { max-width: 100%; flex: 0 0 100%; }
      .card-body { padding: 1.5rem !important; }
      .form-control { font-size: 16px !important; padding: 12px 14px !important; }
      .btn-custom { font-size: 1.05rem !important; padding: 12px !important; }
    }
  </style>

</head>
<body>
  <div class="container">
    <div class="col-md-5 mx-auto">
      <div class="card card-custom shadow-lg">
        <div class="card-header-custom">Reset Password</div>
        <div class="card-body p-4">
          <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <div class="text-center mt-3">
              <a href="login.php" class="btn btn-custom">Login</a>
            </div>
          <?php elseif ($token_valid): ?>
            <?php foreach ($errors as $err): ?>
              <div class="alert alert-danger py-2"><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
            <form method="post">
              <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
              <div class="mb-3">
                <label class="form-label fw-semibold">New Password</label>
                <input name="password" type="password" class="form-control" required minlength="8">
                <div class="form-text">At least 8 characters with uppercase, lowercase, number, and symbol.</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Confirm Password</label>
                <input name="confirm_password" type="password" class="form-control" required>
              </div>
              <div class="d-grid">
                <button class="btn btn-custom">Reset Password</button>
              </div>
            </form>
          <?php else: ?>
            <?php foreach ($errors as $err): ?>
              <div class="alert alert-danger py-2"><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
            <div class="text-center mt-3">
              <a href="forgot-password.php" class="btn btn-custom">Request New Link</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="footer-text">© <?= date('Y') ?> Captain J. All rights reserved.</div>
    </div>
  </div>
</body>
</html>
