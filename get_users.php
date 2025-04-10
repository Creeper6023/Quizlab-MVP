<?php
require_once __DIR__ . '/database/db.php';

// Display all users
$db = new Database();
$pdo = $db->getConnection();

$stmt = $pdo->query("SELECT id, username, role FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>All Users</h1>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Username</th><th>Role</th></tr>";
foreach ($users as $user) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($user['id']) . "</td>";
    echo "<td>" . htmlspecialchars($user['username']) . "</td>";
    echo "<td>" . htmlspecialchars($user['role']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
