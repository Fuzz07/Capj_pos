<?php
require_once __DIR__ . '/../includes/config.php';

echo "<h2>Database Check</h2>";

try {
    $stmt = $pdo->query("SELECT id, username, full_name, email, email_verified_at, role FROM users");
    $users = $stmt->fetchAll();
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Verified</th><th>Role</th></tr>";
    foreach ($users as $u) {
        echo "<tr>";
        echo "<td>" . $u['id'] . "</td>";
        echo "<td>" . htmlspecialchars($u['username']) . "</td>";
        echo "<td>" . htmlspecialchars($u['full_name'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($u['email'] ?? 'NULL') . "</td>";
        echo "<td>" . ($u['email_verified_at'] ?? 'NULL') . "</td>";
        echo "<td>" . $u['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h3>Password Test</h3>";
    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = 'admin'");
    $stmt->execute();
    $hash = $stmt->fetchColumn();
    if ($hash) {
        echo "Hash found: " . substr($hash, 0, 20) . "...<br>";
        $test = password_verify('admin123', $hash);
        echo "Password 'admin123' matches: " . ($test ? 'YES' : 'NO') . "<br>";
    } else {
        echo "No admin user found!<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
