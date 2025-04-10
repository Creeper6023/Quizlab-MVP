<?php
require_once __DIR__ . '/database/db.php';

// Add additional users for testing
$db = new Database();
$pdo = $db->getConnection();

// Check if admin user already exists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
$stmt->execute(['admin']);
$adminExists = $stmt->fetchColumn() > 0;

if (!$adminExists) {
    // Create admin user
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
    echo "Created admin user.<br>";
} else {
    echo "Admin user already exists.<br>";
}

// Check if teacher user already exists
$stmt->execute(['teacher']);
$teacherExists = $stmt->fetchColumn() > 0;

if (!$teacherExists) {
    // Create teacher user
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute(['teacher', password_hash('teacher123', PASSWORD_DEFAULT), 'teacher']);
    echo "Created teacher user.<br>";
} else {
    echo "Teacher user already exists.<br>";
}

// Check if student user already exists
$stmt->execute(['student']);
$studentExists = $stmt->fetchColumn() > 0;

if (!$studentExists) {
    // Create student user
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute(['student', password_hash('student123', PASSWORD_DEFAULT), 'student']);
    echo "Created student user.<br>";
} else {
    echo "Student user already exists.<br>";
}

echo "User setup complete.";
?>
