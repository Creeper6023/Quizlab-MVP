<?php
require_once __DIR__ . '/database/db.php';

// Create and initialize the database with test data
$db = new Database();
$pdo = $db->getConnection();

// Create tables if they don't exist
$sql = file_get_contents(__DIR__ . '/database/schema.sql');
$pdo->exec($sql);
echo "Database schema created.<br>";

// Check if we already have users
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$userCount = $stmt->fetchColumn();

if ($userCount > 0) {
    echo "Database already contains users. Skipping test data creation.<br>";
} else {
    // Create test users with proper password hashing
    $users = [
        ['username' => 'admin', 'password' => password_hash('admin123', PASSWORD_DEFAULT), 'role' => 'admin'],
        ['username' => 'teacher', 'password' => password_hash('teacher123', PASSWORD_DEFAULT), 'role' => 'teacher'],
        ['username' => 'student', 'password' => password_hash('student123', PASSWORD_DEFAULT), 'role' => 'student']
    ];

    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    foreach ($users as $user) {
        $stmt->execute([$user['username'], $user['password'], $user['role']]);
    }
    echo "Test users created.<br>";

    // Create test classes
    $classes = [
        ['name' => 'Mathematics 101', 'description' => 'Introduction to Mathematics'],
        ['name' => 'Science 101', 'description' => 'Introduction to Science'],
        ['name' => 'History 101', 'description' => 'Introduction to History']
    ];

    $stmt = $pdo->prepare("INSERT INTO classes (name, description) VALUES (?, ?)");
    foreach ($classes as $class) {
        $stmt->execute([$class['name'], $class['description']]);
    }
    echo "Test classes created.<br>";

    // Get teacher ID
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'teacher' LIMIT 1");
    $teacherId = $stmt->fetchColumn();

    // Get class IDs
    $stmt = $pdo->query("SELECT id FROM classes");
    $classIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Assign teacher to classes
    $stmt = $pdo->prepare("INSERT INTO class_teachers (class_id, teacher_id) VALUES (?, ?)");
    foreach ($classIds as $classId) {
        $stmt->execute([$classId, $teacherId]);
    }
    echo "Teacher assigned to classes.<br>";

    // Get student ID
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'student' LIMIT 1");
    $studentId = $stmt->fetchColumn();

    // Assign student to classes
    $stmt = $pdo->prepare("INSERT INTO class_students (class_id, student_id) VALUES (?, ?)");
    foreach ($classIds as $classId) {
        $stmt->execute([$classId, $studentId]);
    }
    echo "Student assigned to classes.<br>";

    // Create test quizzes
    $quizzes = [
        [
            'title' => 'Math Quiz 1',
            'description' => 'Basic arithmetic operations',
            'created_by' => $teacherId,
            'class_id' => $classIds[0],
            'questions' => [
                ['question_text' => 'What is 2 + 2?', 'model_answer' => '4', 'points' => 5],
                ['question_text' => 'What is 5 * 3?', 'model_answer' => '15', 'points' => 5]
            ]
        ],
        [
            'title' => 'Science Quiz 1',
            'description' => 'Basic science concepts',
            'created_by' => $teacherId,
            'class_id' => $classIds[1],
            'questions' => [
                ['question_text' => 'What is photosynthesis?', 'model_answer' => 'Photosynthesis is the process by which plants convert light energy into chemical energy.', 'points' => 10],
                ['question_text' => 'What are the three states of matter?', 'model_answer' => 'The three states of matter are solid, liquid, and gas.', 'points' => 10]
            ]
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO quizzes (title, description, created_by, class_id) VALUES (?, ?, ?, ?)");
    $stmt2 = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, model_answer, points) VALUES (?, ?, ?, ?)");

    foreach ($quizzes as $quiz) {
        $stmt->execute([$quiz['title'], $quiz['description'], $quiz['created_by'], $quiz['class_id']]);
        $quizId = $pdo->lastInsertId();

        foreach ($quiz['questions'] as $question) {
            $stmt2->execute([$quizId, $question['question_text'], $question['model_answer'], $question['points']]);
        }
    }
    echo "Test quizzes and questions created.<br>";

    // Initialize settings
    $settings = [
        ['key' => 'deepseek_api_key', 'value' => getenv('DEEPSEEK_API_KEY') ?: ''],
        ['key' => 'ai_grading_enabled', 'value' => '1']
    ];

    $stmt = $pdo->prepare("INSERT INTO settings (key, value) VALUES (?, ?)");
    foreach ($settings as $setting) {
        $stmt->execute([$setting['key'], $setting['value']]);
    }
    echo "Settings initialized.<br>";
}

echo "Database setup complete.<br>";
?>
