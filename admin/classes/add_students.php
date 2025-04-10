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
    $student_ids = $_POST['student_ids'] ?? [];
    
    if (empty($student_ids) || !is_array($student_ids)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No students selected']);
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
    
    // Begin a transaction
    $db->getConnection()->beginTransaction();
    
    try {
        // Add each student to the class
        foreach ($student_ids as $student_id) {
            $student_id = (int)$student_id;
            
            // Verify this is a valid student
            $student = $db->single(
                "SELECT * FROM users WHERE id = ? AND role = 'student'",
                [$student_id]
            );
            
            if ($student) {
                // Check if student is already enrolled
                $existing = $db->single(
                    "SELECT * FROM class_enrollments WHERE class_id = ? AND user_id = ?",
                    [$class_id, $student_id]
                );
                
                if (!$existing) {
                    // Enroll the student
                    $db->query(
                        "INSERT INTO class_enrollments (class_id, user_id) VALUES (?, ?)",
                        [$class_id, $student_id]
                    );
                }
            }
        }
        
        // Commit the transaction
        $db->getConnection()->commit();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Students added to class successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback the transaction if something went wrong
        $db->getConnection()->rollBack();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to add students: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
