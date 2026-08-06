<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/mail.php';


try { $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, message TEXT NOT NULL, is_read BOOLEAN DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE notifications ADD COLUMN type VARCHAR(50) DEFAULT NULL AFTER is_read"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE notifications ADD COLUMN related_entity VARCHAR(50) DEFAULT NULL AFTER type"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE notifications ADD COLUMN related_id INT DEFAULT NULL AFTER related_entity"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(64) NULL AFTER email_verified_at"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(20) DEFAULT 'Cash' AFTER change_due"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN payment_ref VARCHAR(255) NULL AFTER payment_method"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN payment_screenshot VARCHAR(255) NULL AFTER payment_ref"); } catch (PDOException $e) {}

$errors = [];
$success = '';
$step = 1;

$email = trim($_POST['email'] ?? '');
$otp_input = trim($_POST['otp'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

// Step 3: Reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    $reset_user_id = $_SESSION['otp_user_id'] ?? null;
    $reset_email = $_SESSION['otp_email'] ?? null;

    if (!$reset_user_id || !$reset_email) {
        $errors[] = "Session expired. Please start over.";
        session_destroy();
    } else {
        $step = 3;
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password must contain an uppercase letter.';
        if (!preg_match('/[a-z]/', $password)) $errors[] = 'Password must contain a lowercase letter.';
        if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password must contain a number.';
        if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors[] = 'Password must contain a special character.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';

            if (!$errors) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND email = ?");
            $stmt->execute([$hash, $reset_user_id, $reset_email]);
            if ($stmt->rowCount() === 0) {
                $errors[] = "Could not update password (user ID $reset_user_id, email $reset_email not found). Contact admin.";
            } else {
                $stmt2 = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                $stmt2->execute([$reset_user_id]);
                $reset_user = $stmt2->fetch();
                if (!empty(GMAIL_REFRESH_TOKEN)) {
                    send_password_changed_notification($reset_email, $reset_user['full_name'] ?: 'User');
                }
                unset($_SESSION['otp_user_id'], $_SESSION['otp_email'], $_SESSION['otp_verified']);
                $success = 'Password has been reset successfully!';
            }
        }
    }
}

// Step 2: Verify OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $email = $_POST['email'] ?? '';
    $otp_input = trim($_POST['otp'] ?? '');

    $step = 2;
    if ($otp_input === '') {
        $errors[] = "Please enter the OTP code.";
    } elseif (!isset($_SESSION['otp_reset'])) {
        $errors[] = "Session expired. Please request a new OTP.";
        $step = 1;
    } elseif ($_SESSION['otp_reset']['email'] !== $email) {
        $errors[] = "Email mismatch. Please start over.";
        $step = 1;
    } elseif ($_SESSION['otp_reset']['expires_at'] < time()) {
        unset($_SESSION['otp_reset']);
        $errors[] = "OTP code has expired. Please request a new one.";
        $step = 1;
    } elseif ($_SESSION['otp_reset']['otp'] !== $otp_input) {
        $errors[] = "Invalid OTP code. Please request a new one.";
        $step = 2;
    } else {
        $_SESSION['otp_user_id'] = $_SESSION['otp_reset']['user_id'];
        $_SESSION['otp_email'] = $_SESSION['otp_reset']['email'];
        unset($_SESSION['otp_reset']);
        $step = 3;
    }
}

// Step 1: Send OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $errors[] = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT id, full_name, email_verified_at FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['email_verified_at'] !== null) {
                $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $_SESSION['otp_reset'] = [
                    'email' => $email,
                    'user_id' => $user['id'],
                    'otp' => $otp,
                    'expires_at' => time() + 300
                ];

                if (empty(GMAIL_REFRESH_TOKEN)) {
                    $_SESSION['otp_display'] = $otp;
                    $step = 2;
                } else {
                    $result = send_otp_email($email, $otp, $user['full_name'] ?: 'User');
                    if ($result['success']) {
                        $step = 2;
                    } else {
                        $errors[] = "Failed to send OTP email: " . $result['error'];
                    }
                }
            } else {
                $errors[] = "This email is not yet verified. Please check your inbox for the verification link or contact the administrator.";
            }
        } else {
            $errors[] = "No account found with that email address.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Account Recovery - Captain J</title>
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
    .otp-input {
      font-size: 28px;
      letter-spacing: 12px;
      text-align: center;
      font-weight: bold;
    }
    .step-indicator {
      display: flex;
      justify-content: center;
      gap: 8px;
      margin-bottom: 15px;
    }
    .step-dot {
      width: 30px; height: 30px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; font-weight: bold;
      background: #ddd; color: #999;
    }
    .step-dot.active { background: #f10000; color: #fff; }
    .step-dot.done { background: #28a745; color: #fff; }
    .footer-text {
      text-align: center;
      margin-top: 15px;
      color: #b69a9a;
      font-size: 0.9rem;
    }
    .letter-spacing-3 { letter-spacing: 8px; }
    @media (max-width: 576px) {
      .container { padding: 0 12px; }
      .col-md-5 { max-width: 100%; flex: 0 0 100%; }
      .card-body { padding: 1.5rem !important; }
      .form-control { font-size: 16px !important; padding: 12px 14px !important; }
      .btn-custom { font-size: 1.05rem !important; padding: 12px !important; }
      .otp-input { font-size: 22px !important; letter-spacing: 8px !important; }
    }
  </style>

</head>
<body>
  <div class="container">
    <div class="col-md-5 mx-auto">
      <div class="card card-custom shadow-lg">
        <div class="card-header-custom">Account Recovery</div>
        <div class="card-body p-4">

          <!-- Step Indicator -->
          <div class="step-indicator">
            <div class="step-dot <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>">1</div>
            <div class="step-dot <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>">2</div>
            <div class="step-dot <?= $step >= 3 ? 'active' : '' ?>">3</div>
          </div>

          <?php if ($success): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
            <div class="text-center mt-3"><a href="login.php" class="btn btn-custom">Go to Login</a></div>

          <?php elseif ($step === 3): ?>
            <!-- Step 3: New Password -->
            <?php foreach ($errors as $err): ?>
              <div class="alert alert-danger py-2"><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
            <p class="text-muted">Enter your new password.</p>
            <form method="post">
              <input type="hidden" name="reset" value="1">
              <div class="mb-3">
                <label class="form-label fw-semibold">New Password</label>
                <input name="password" type="password" class="form-control" required minlength="8">
                <div class="form-text">At least 8 characters — uppercase, lowercase, number, and symbol.</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Confirm Password</label>
                <input name="confirm_password" type="password" class="form-control" required>
              </div>
              <div class="d-grid"><button class="btn btn-custom">Reset Password</button></div>
            </form>

          <?php elseif ($step === 2): ?>
            <!-- Step 2: Verify OTP -->
            <?php foreach ($errors as $err): ?>
              <div class="alert alert-danger py-2"><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
            <?php if (isset($_SESSION['otp_display'])): ?>
              <div class="alert alert-info text-center py-2">
                <small>SMTP not configured. Use this OTP:</small>
                <div class="fs-1 fw-bold letter-spacing-3"><?= htmlspecialchars($_SESSION['otp_display']) ?></div>
                <small class="text-muted">Configure SMTP credentials in <code>includes/config.php</code> to send via email.</small>
              </div>
              <?php unset($_SESSION['otp_display']); ?>
            <?php endif; ?>
            <p class="text-muted">A 6-digit code was sent to <strong><?= htmlspecialchars($email) ?></strong>. Enter it below.</p>
            <form method="post">
              <input type="hidden" name="verify_otp" value="1">
              <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
              <div class="mb-3">
                <input name="otp" type="text" class="form-control otp-input" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" required autofocus>
              </div>
              <div class="d-grid"><button class="btn btn-custom">Verify Code</button></div>
            </form>
            <div class="text-center mt-3">
              <a href="forgot-password.php" class="text-danger">Request a new code</a>
            </div>

          <?php else: ?>
            <!-- Step 1: Enter Email -->
            <?php foreach ($errors as $err): ?>
              <div class="alert alert-danger py-2"><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
            <p class="text-muted">Enter your verified email address to receive an OTP code.</p>
            <form method="post">
              <input type="hidden" name="send_otp" value="1">
              <div class="mb-3">
                <label class="form-label fw-semibold">Email Address</label>
                <input name="email" type="email" class="form-control" required placeholder="you@example.com" value="<?= htmlspecialchars($email) ?>">
              </div>
              <div class="d-grid"><button class="btn btn-custom">Send OTP Code</button></div>
            </form>

          <?php endif; ?>

          <div class="text-center mt-3">
            <a href="login.php">Back to Login</a>
          </div>
        </div>
      </div>
      <div class="footer-text">© <?= date('Y') ?> Captain J. All rights reserved.</div>
    </div>
  </div>
</body>
</html>
