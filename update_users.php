<?php
require_once __DIR__ . '/database/db.php';

// Update user passwords to ensure they are correctly hashed
$db = new Database();
$pdo = $db->getConnection();

// Display information
echo "<h1>User Password Update</h1>";

// First, let's check the current users
$stmt = $pdo->query("SELECT id, username, password, role FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Current Users:</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Password Hash (first 10 chars)</th></tr>";
foreach ($users as $user) {
    echo "<tr>";
    echo "<td>" . $user['id'] . "</td>";
    echo "<td>" . $user['username'] . "</td>";
    echo "<td>" . $user['role'] . "</td>";
    echo "<td>" . substr($user['password'], 0, 10) . "...</td>";
    echo "</tr>";
}
echo "</table>";

// Update default test users with proper password hashing
$updateUsers = [
    ['username' => 'admin', 'password' => password_hash('admin123', PASSWORD_DEFAULT)],
    ['username' => 'teacher', 'password' => password_hash('teacher123', PASSWORD_DEFAULT)],
    ['username' => 'student', 'password' => password_hash('student123', PASSWORD_DEFAULT)],
    ['username' => 'at', 'password' => password_hash('pa', PASSWORD_DEFAULT)],
    ['username' => 'tt', 'password' => password_hash('pt', PASSWORD_DEFAULT)],
    ['username' => 'st', 'password' => password_hash('ps', PASSWORD_DEFAULT)]
];

$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
$updatedCount = 0;

echo "<h2>Password Update Results:</h2>";
echo "<ul>";

foreach ($updateUsers as $user) {
    try {
        $stmt->execute([$user['password'], $user['username']]);
        $affected = $stmt->rowCount();
        $updatedCount += $affected;
        echo "<li>User '{$user['username']}': " . ($affected > 0 ? "Updated" : "Not found") . "</li>";
    } catch (PDOException $e) {
        echo "<li>Error updating '{$user['username']}': " . $e->getMessage() . "</li>";
    }
}

echo "</ul>";
echo "<p>Total users updated: $updatedCount</p>";

// Finally, let's verify the updates
$stmt = $pdo->query("SELECT id, username, password, role FROM users");
$updatedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Updated Users:</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Password Hash (first 20 chars)</th></tr>";
foreach ($updatedUsers as $user) {
    echo "<tr>";
    echo "<td>" . $user['id'] . "</td>";
    echo "<td>" . $user['username'] . "</td>";
    echo "<td>" . $user['role'] . "</td>";
    echo "<td>" . substr($user['password'], 0, 20) . "...</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p>Password update completed.</p>";
?>
