<?php
require_once '../../config.php';
require_once LIB_PATH . '/database/db.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    
    // Validate input
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    
    if (empty($name)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Class name is required']);
        exit();
    }
    
    if ($teacher_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Teacher selection is required']);
        exit();
    }
    
    // Verify the teacher exists
    $teacher = $db->single(
        "SELECT * FROM users WHERE id = ? AND role = 'teacher'",
        [$teacher_id]
    );
    
    if (!$teacher) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Selected teacher does not exist']);
        exit();
    }
    
    try {
        // Create the class
        $db->query(
            "INSERT INTO classes (name, description, created_by) VALUES (?, ?, ?)",
            [$name, $description, $teacher_id]
        );
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Class created successfully'
        ]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to create class: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
