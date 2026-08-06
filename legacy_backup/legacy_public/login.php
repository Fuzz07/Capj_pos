<?php
require_once __DIR__ . '/../includes/config.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = "Enter username and password.";
    }

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ];

            // ✅ Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: admin/profile.php');
            }
            exit;
        } else {
            $errors[] = "Invalid credentials.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login - Captain J</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/responsive.css">
  <style>
    /* ======== Sage Green Theme ======== */
    body {
      background: linear-gradient(135deg, #faf9f6, #f8f8ff);
      font-family: 'Poppins', sans-serif;
      color: #2f3b2f;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .login-card {
      background: #f6f7f2;
      border: none;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    .login-header {
      background: #f10000;
      color: #fff;
      border-top-left-radius: 15px;
      border-top-right-radius: 15px;
      padding: 1rem;
      text-align: center;
      font-weight: 600;
      letter-spacing: 0.5px;
    }
    .form-control {
      border-radius: 10px;
      border: 1px solid #ef9a9a;
    }
    .form-control:focus {
      border-color: #ef9a9a;
      box-shadow: 0 0 5px rgba(138,154,91,0.3);
    }
    .btn-sage {
      background-color: #f10000;
      border: none;
      color: #fff;
      font-weight: 500;
      border-radius: 10px;
      transition: all 0.2s ease;
    }
    .btn-sage:hover {
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
      .login-card { margin: 0 auto; }
      .card-body { padding: 1.5rem !important; }
      .form-control { font-size: 16px !important; padding: 12px 14px !important; }
      .btn-sage { font-size: 1.05rem !important; padding: 12px !important; }
      .login-header { font-size: 1rem; padding: 1.2rem; }
    }
  </style>
</head>
<body>
  <div class="container px-3">
    <div class="col-md-5 mx-auto">
      <div class="card login-card shadow-lg">
        <div class="login-header"><img src="<?= BASE_PATH ?>/images/capj.jpg" alt="Captain J" style="width: 60px; height: 60px; border-radius: 50%; border: 3px solid #fff; margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto;">
            Captain J
        </div>
        <div class="card-body p-4">
          <h5 class="text-center mb-4" style="color:#898989;">Welcome Back</h5>
          <?php foreach($errors as $err): ?>
            <div class='alert alert-danger py-2'><?= htmlspecialchars($err) ?></div>
          <?php endforeach; ?>
          <form method="post">
            <div class="mb-3">
              <label class="form-label fw-semibold">Username</label>
              <input name="username" class="form-control" required placeholder="Enter username">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Password</label>
              <input name="password" type="password" class="form-control" required placeholder="Enter password">
            </div>
            <div class="d-grid">
              <button class="btn btn-sage">Login</button>
            </div>
            <div class="text-center mt-3">
              <a href="forgot-password.php" class="text-danger text-decoration-none">Forgot Password?</a>
            </div>
          </form>
        </div>
      </div>
      <div class="footer-text">
        © <?= date('Y') ?> Captain J. All rights reserved.
      </div>
    </div>
  </div>
</body>
</html>
