<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
if (!is_logged_in()) header('Location: ' . APP_BASE_URL . '/login.php');
refresh_user_session($pdo);
$u = current_user();
if ($u['role'] !== 'admin') { echo "Admin only."; exit; }

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['clear'])) {
        $pdo->exec("UPDATE notifications SET is_read = 1 WHERE type = 'low_stock' AND is_read = 0");
        $result = ['success' => true, 'error' => 'Cleared old low stock notifications'];
    } else {
        require_once __DIR__ . '/../../includes/mail.php';
        $subject = "Test Email from " . APP_NAME;
        $body = "<h2>Gmail API Test</h2><p>If you're reading this, the Gmail API is working!</p>";
        $result = send_via_phpmailer($subject, $body, ADMIN_EMAIL, ADMIN_NAME);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Test Mail - Captain J</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body class="bg-light">
<div class="container py-5">
  <div class="card shadow" style="max-width:500px;margin:auto;">
    <div class="card-header bg-primary text-white"><h5 class="mb-0">📧 Gmail API Test</h5></div>
    <div class="card-body">
      <p><strong>Sending as:</strong> <?= GMAIL_USER ?><br>
         <strong>Client ID:</strong> <?= GMAIL_CLIENT_ID ? '✓ Set (' . strlen(GMAIL_CLIENT_ID) . ' chars)' : '✗ Not set' ?><br>
         <strong>Client Secret:</strong> <?= GMAIL_CLIENT_SECRET ? '✓ Set' : '✗ Not set' ?><br>
         <strong>Refresh Token:</strong> <?= GMAIL_REFRESH_TOKEN ? '✓ Set (' . strlen(GMAIL_REFRESH_TOKEN) . ' chars)' : '✗ Not set — run gmail-oauth-setup.php first' ?><br>
         <strong>Sending to:</strong> <?= ADMIN_EMAIL ?>
      </p>
      <form method="post">
        <button type="submit" class="btn btn-primary w-100 mb-2">Send Test Email</button>
        <button type="submit" name="clear" value="1" class="btn btn-warning w-100">Clear Old Notifications & Retry</button>
      </form>
      <?php if ($result): ?>
        <hr>
        <?php if ($result['success']): ?>
          <div class="alert alert-success mt-3">✓ Test email sent successfully to <?= ADMIN_EMAIL ?>!</div>
        <?php else: ?>
          <div class="alert alert-danger mt-3">
            <strong>✗ Failed:</strong> <?= htmlspecialchars($result['error']) ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="text-center mt-3"><a href="dashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a></div>
</div>
</body>
</html>
