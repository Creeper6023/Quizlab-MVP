<?php
require_once '../../config.php';
require_once LIB_PATH . '/database/db.php';

// Check if user is logged in and is a teacher
if (!isLoggedIn() || !hasRole(ROLE_TEACHER)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $teacher_id = $_SESSION['user_id'];
    
    // Validate input
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Class name is required']);
        exit();
    }
    
    try {
        // Generate hash
        $hash = bin2hex(random_bytes(8));
        
        // Insert new class
        $db->query(
            "INSERT INTO classes (name, description, created_by, hash) VALUES (?, ?, ?, ?)",
            [$name, $description, $teacher_id, $hash]
        );
        
        $class_id = $db->lastInsertId();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Class created successfully',
            'class_id' => $class_id
        ]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to create class: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
