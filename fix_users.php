<?php
require_once __DIR__ . '/database/db.php';

// Fix user roles and add missing student
$db = new Database();
$pdo = $db->getConnection();

// Fix teacher user's role to 'teacher'
$stmt = $pdo->prepare("UPDATE users SET role = ? WHERE username = ?");
$stmt->execute(['teacher', 'teacher']);
echo "Updated teacher user's role.<br>";

// Add student user if not exists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
$stmt->execute(['student']);
$studentExists = $stmt->fetchColumn() > 0;

if (!$studentExists) {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute(['student', password_hash('student123', PASSWORD_DEFAULT), 'student']);
    echo "Created student user.<br>";
} else {
    echo "Student user already exists.<br>";
}

// Display current users
$stmt = $pdo->query("SELECT id, username, role FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>Updated Users</h1>";
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

echo "User updates complete.";
?>
