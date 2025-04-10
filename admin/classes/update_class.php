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
    $class_id = (int)($_POST['class_id'] ?? 0);
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
    
    // Check if the class exists
    $class = $db->single(
        "SELECT * FROM classes WHERE id = ?",
        [$class_id]
    );
    
    if (!$class) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Class not found']);
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
        // Update the class
        $db->query(
            "UPDATE classes SET name = ?, description = ?, created_by = ? WHERE id = ?",
            [$name, $description, $teacher_id, $class_id]
        );
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Class updated successfully'
        ]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update class: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
