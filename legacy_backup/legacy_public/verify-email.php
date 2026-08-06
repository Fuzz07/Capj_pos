<?php
require_once __DIR__ . '/../includes/config.php';

try { $pdo->exec("ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(64) NULL AFTER email_verified_at"); } catch (PDOException $e) {}

$message = '';
$success = false;

$token = $_GET['token'] ?? '';

if ($token === '') {
    $message = "Missing verification token.";
} else {
    $token_hash = hash('sha256', $token);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email_verification_token = ? AND email_verified_at IS NULL LIMIT 1");
    $stmt->execute([$token_hash]);
    $user = $stmt->fetch();

    if ($user) {
        $pdo->prepare("UPDATE users SET email_verified_at = NOW(), email_verification_token = NULL WHERE id = ?")->execute([$user['id']]);
        if (isset($_SESSION['user']) && $_SESSION['user']['id'] == $user['id']) {
            $_SESSION['user']['email_verified_at'] = date('Y-m-d H:i:s');
        }
        $message = "Your email has been verified successfully! You can now login.";
        $success = true;
    } else {
        $message = "Invalid or expired verification link. If you already verified your email, try logging in.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Email Verification - Captain J</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
    .btn-custom {
      background-color: #f10000;
      border: none;
      color: #fff;
      font-weight: 500;
      border-radius: 10px;
    }
    .btn-custom:hover {
      background-color: #ef9a9a;
    }
    .footer-text {
      text-align: center;
      margin-top: 15px;
      color: #b69a9a;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="col-md-5 mx-auto">
      <div class="card card-custom shadow-lg">
        <div class="card-header-custom">Email Verification</div>
        <div class="card-body p-4 text-center">
          <div class="alert alert-<?= $success ? 'success' : 'danger' ?>"><?= htmlspecialchars($message) ?></div>
          <a href="login.php" class="btn btn-custom">Go to Login</a>
        </div>
      </div>
      <div class="footer-text">© <?= date('Y') ?> Captain J. All rights reserved.</div>
    </div>
  </div>
</body>
</html>
