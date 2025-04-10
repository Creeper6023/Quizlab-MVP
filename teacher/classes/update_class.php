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
    $class_id = (int)($_POST['class_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if ($class_id === 0 || empty($name)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Class name is required']);
        exit();
    }
    
    // Check if this is the class creator (only creators can update class details)
    $class = $db->single(
        "SELECT * FROM classes WHERE id = ? AND created_by = ?",
        [$class_id, $teacher_id]
    );
    
    if (!$class) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Class not found or you do not have permission to update it. Only the class creator can update class details.'
        ]);
        exit();
    }
    
    // Update the class
    try {
        $db->query(
            "UPDATE classes SET name = ?, description = ?, updated_at = NOW() WHERE id = ?",
            [$name, $description, $class_id]
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