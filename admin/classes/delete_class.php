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
    
    if ($class_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
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
    
    try {
        // Begin a transaction
        $db->getConnection()->beginTransaction();
        
        // Delete related records first
        
        // Delete class enrollments
        $db->query(
            "DELETE FROM class_enrollments WHERE class_id = ?",
            [$class_id]
        );
        
        // Delete class quizzes associations
        $db->query(
            "DELETE FROM class_quizzes WHERE class_id = ?",
            [$class_id]
        );
        
        // Finally, delete the class
        $db->query(
            "DELETE FROM classes WHERE id = ?",
            [$class_id]
        );
        
        // Commit the transaction
        $db->getConnection()->commit();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Class deleted successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback the transaction if something went wrong
        $db->getConnection()->rollBack();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to delete class: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
