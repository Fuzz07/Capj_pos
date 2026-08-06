<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/mail.php';

if (!is_logged_in()) header('Location: ' . BASE_PATH . '/login.php');
refresh_user_session($pdo);
$u = current_user();
if ($u['role'] !== 'admin') { echo 'Access denied'; exit; }

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

// === CREATE or UPDATE ===
$errors = [];
$formData = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = $_POST;
    $username = trim($_POST['username']);
    $password = $_POST['password'] ?? '';
    $full = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'staff';
    $email = trim($_POST['email'] ?? '');
    $id = $_POST['id'] ?? '';

    if ($username === '') $errors[] = 'Username is required.';
    if ($full === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) && $email !== '') $errors[] = 'Invalid email format.';
    if (!in_array($role, ['admin', 'staff'])) $errors[] = 'Invalid role selected.';

    if (!$id) {
        if ($password === '') $errors[] = 'Password is required for new users.';
        elseif (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    }

    if (!$errors) {
        // Check duplicate username
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?" . ($id ? " AND id != ?" : ""));
        if ($id) { $check->execute([$username, $id]); } else { $check->execute([$username]); }
        if ($check->fetch()) $errors[] = 'Username "' . htmlspecialchars($username) . '" is already taken.';
    }

    if (!$errors) {
        if ($id) {
            // Check if email changed
            $stmt = $pdo->prepare("SELECT email FROM users WHERE id=?");
            $stmt->execute([$id]);
            $oldEmail = $stmt->fetchColumn();
            $emailChanged = ($email !== $oldEmail);

            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username=?, password=?, full_name=?, role=?, email=? WHERE id=?");
                $stmt->execute([$username, $hash, $full, $role, $email, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, full_name=?, role=?, email=? WHERE id=?");
                $stmt->execute([$username, $full, $role, $email, $id]);
            }

            if ($emailChanged && !empty($email) && !empty(GMAIL_REFRESH_TOKEN)) {
                $vtoken = bin2hex(random_bytes(32));
                $pdo->prepare("UPDATE users SET email_verified_at = NULL, email_verification_token = ? WHERE id = ?")
                    ->execute([hash('sha256', $vtoken), $id]);
                send_verification_email($email, $vtoken, $full);
            }
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $vtoken = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("INSERT INTO users (username,password,full_name,role,email,email_verification_token) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$username, $hash, $full, $role, $email, hash('sha256', $vtoken)]);

            if (!empty($email) && !empty(GMAIL_REFRESH_TOKEN)) {
                send_verification_email($email, $vtoken, $full);
            }
        }

        header('Location: users.php?saved=1');
        exit;
    }
}

// === RESEND VERIFICATION ===
if ($action === 'resend_verify' && $id) {
    $stmt = $pdo->prepare("SELECT id, email, full_name, email_verified_at FROM users WHERE id=?");
    $stmt->execute([$id]);
    $u2 = $stmt->fetch();
    if ($u2 && $u2['email'] && !$u2['email_verified_at'] && !empty(GMAIL_REFRESH_TOKEN)) {
        $vtoken = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("UPDATE users SET email_verification_token=? WHERE id=?");
        $stmt->execute([hash('sha256', $vtoken), $id]);
        send_verification_email($u2['email'], $vtoken, $u2['full_name'] ?: 'User');
        $msg = 'Verification email sent.';
    } elseif (empty(GMAIL_REFRESH_TOKEN)) {
        $msg = 'Mail not configured. Cannot send email.';
    } else {
        $msg = 'User already verified or no email set.';
    }
    header('Location: users.php?msg=' . urlencode($msg));
    exit;
}

// === FETCH USERS ===
$users = $pdo->query("SELECT id,username,full_name,email,email_verified_at,role,created_at FROM users ORDER BY id DESC")->fetchAll();

// === EDIT MODE ===
$editUser = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$id]);
    $editUser = $stmt->fetch();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>User Management - Captain J</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/responsive.css">
  <style>
    /* === Sage Green Admin Theme === */
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f5f7f2;
      color: #2f3b2f;
      display: flex;
      height: 100vh;
      margin: 0;
    }
    .sidebar {
      width: 230px;
      background-color: #f10000;
      color: #fff;
      padding-top: 1.5rem;
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
    }
    .sidebar a {
      color: #fff;
      text-decoration: none;
      display: block;
      padding: 12px 20px;
      transition: 0.3s;
      border-left: 4px solid transparent;
    }
    .sidebar a:hover, .sidebar a.active {
      background-color: #ef9a9a;
      border-left: 4px solid #fff;
    }
    .sidebar-footer {
      padding: 1rem;
      text-align: center;
      border-top: 1px solid rgba(255,255,255,0.2);
    }
    .main-content {
      margin-left: 230px;
      padding: 2rem;
      width: 100%;
    }
    .welcome-msg {
      background: #c8d3c0;
      padding: 1rem;
      border-radius: 10px;
      text-align: center;
      color: #2f3b2f;
      font-weight: 500;
      animation: fadeOut 1s ease 3s forwards;
    }
    @keyframes fadeOut { to { opacity: 0; visibility: hidden; } }
    .card {
      background: #fff;
      border: none;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      margin-bottom: 1.5rem;
    }
    .btn-sage {
      background-color:  #18b318;
      color: #fff;
      border: none;
    }
    .btn-sage:hover {
      background-color:  #18b318;
    }
    .toast-notification {
      position: fixed; top: 20px; right: 20px; z-index: 9999;
      padding: 12px 24px; border-radius: 8px; color: #fff;
      font-weight: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      opacity: 0; transform: translateX(100%); transition: all 0.4s ease;
    }
    .toast-notification.show {
      opacity: 1; transform: translateX(0);
    }
    .toast-success { background: #28a745; }
  </style>
</head>
<body>
  <div id="toast" class="toast-notification"></div>
  <button class="hamburger">&#9776;</button>
  <div class="sidebar-overlay"></div>

 <!-- Sidebar -->
  <div class="sidebar">
    <div>
      <h5 class="text-center mb-4"><img src="<?= BASE_PATH ?>/images/capj.jpg" alt="Captain J" style="float: center; width: 30%; border-radius: 200px 200px 200px 200px; border: 3px solid #fff; margin-bottom: 15px;"> Captain J <?= ucfirst($u['role']) ?></h5>

      <?php if ($u['role'] === 'admin'): ?>
        <a href="profile.php" >Profile</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="inventory.php">Inventory</a>
        <a href="users.php"class="active">Users</a>
        <a href="ordering.php?v=2">Create Order</a>
        <a href="order.php">Orders</a>

      <?php elseif ($u['role'] === 'staff'): ?>
       <a href="profile.php">Profile</a>
        <a href="inventory.php">Inventory</a>
        <a href="ordering.php?v=2">Create Order</a>
        <a href="order.php">Orders</a>
      <?php endif; ?>

    </div>
    <div class="sidebar-footer">
      <p class="mb-1 small">Logged in as:</p>
      <strong><?= htmlspecialchars($u['full_name'] ?? $u['username']) ?></strong><br>
      <a href="<?= BASE_PATH ?>/logout.php" class="btn btn-secondary btn-sm mt-2">Logout</a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">


    <h4 class="mb-4">User Management</h4>

    <!-- USERS TABLE -->
    <div class="card p-3">
      <h6>Existing Users</h6>
      <div class="table-responsive-wrap">
      <table class="table table-striped align-middle mt-2">
        <thead class="table-success">
          <tr>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Created</th>
            <th width="150">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $usr): ?>
          <tr>
            <td><?= htmlspecialchars($usr['username']) ?></td>
            <td><?= htmlspecialchars($usr['full_name']) ?></td>
            <td><?= $usr['email'] ? htmlspecialchars($usr['email']) : '<span class="text-danger fw-bold">No Email</span>' ?> <?= $usr['email_verified_at'] ? '<span class="badge bg-success">Verified</span>' : ($usr['email'] ? '<a href="?action=resend_verify&id=' . $usr['id'] . '" class="badge bg-warning text-dark text-decoration-none">Resend</a>' : '') ?></td>
            <td><span class="badge bg-<?= $usr['role']==='admin'?'dark':'secondary' ?>"><?= htmlspecialchars($usr['role']) ?></span></td>
            <td><?= htmlspecialchars($usr['created_at']) ?></td>
            <td>
              <a href="?action=edit&id=<?= $usr['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
              <?php if ($usr['id'] != $u['id']): ?>
                <a href="user_delete.php?id=<?= $usr['id'] ?>" class="btn btn-sm btn-danger">Delete</a>
              <?php else: ?>
                <button class="btn btn-sm btn-secondary" disabled>Protected</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>

    <!-- ADD/EDIT FORM -->
    <div class="card p-3">
      <h6><?= ($editUser || !empty($formData)) ? 'Edit User' : 'Create New User' ?></h6>
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger py-2">
          <?php foreach ($errors as $err): ?>
            <div>• <?= htmlspecialchars($err) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php
        $fv = !empty($formData) ? $formData : ($editUser ?: []);
      ?>
      <form method="post" class="row g-3 mt-2">
        <input type="hidden" name="id" value="<?= htmlspecialchars($editUser['id'] ?? $formData['id'] ?? '') ?>">
        <div class="col-md-3">
          <label class="form-label">Username</label>
          <input name="username" class="form-control" required value="<?= htmlspecialchars($fv['username'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label"><?= $editUser ? 'New Password (optional)' : 'Password' ?></label>
          <input name="password" type="password" class="form-control" <?= $editUser && empty($formData) ? '' : 'required' ?>>
          <?php if ($editUser): ?><div class="form-text">Leave blank to keep current password.</div><?php endif; ?>
        </div>
        <div class="col-md-3">
          <label class="form-label">Full Name</label>
          <input name="full_name" class="form-control" value="<?= htmlspecialchars($fv['full_name'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Email</label>
          <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($fv['email'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Role</label>
          <select name="role" class="form-select">
            <option value="staff" <?= ($fv['role'] ?? 'staff')==='staff'?'selected':'' ?>>Staff</option>
            <option value="admin" <?= ($fv['role'] ?? '')==='admin'?'selected':'' ?>>Admin</option>
          </select>
        </div>
        <div class="col-12">
          <button class="btn btn-sage"><?= ($editUser || !empty($formData)) ? 'Update User' : 'Create User' ?></button>
          <?php if ($editUser || !empty($formData)): ?>
            <a href="users.php" class="btn btn-secondary">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <script>
    function showToast(msg, type) {
      const t = document.getElementById('toast');
      t.textContent = msg; t.className = 'toast-notification toast-' + type + ' show';
      setTimeout(() => t.classList.remove('show'), 3000);
    }
    var up = new URLSearchParams(window.location.search);
    if (up.has('saved')) showToast('User saved successfully!', 'success');
    if (up.has('deleted')) showToast('User "' + (up.get('name') || '') + '" deleted successfully!', 'success');
    if (up.has('saved') || up.has('deleted')) window.history.replaceState({}, '', window.location.pathname);
    <?php if (isset($_GET['msg'])): ?>
      showToast('<?= htmlspecialchars($_GET['msg'], ENT_QUOTES) ?>', 'success');
      window.history.replaceState({}, '', window.location.pathname);
    <?php endif; ?>
  </script>
  <script src="../js/mobile-sidebar.js"></script>
</body>
</html>
