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
    $quiz_ids = $_POST['quiz_ids'] ?? [];
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    
    if (empty($quiz_ids) || !is_array($quiz_ids)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No quizzes selected']);
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
        // Add each quiz to the class
        foreach ($quiz_ids as $quiz_id) {
            $quiz_id = (int)$quiz_id;
            
            // Verify this is a valid quiz
            $quiz = $db->single(
                "SELECT * FROM quizzes WHERE id = ?",
                [$quiz_id]
            );
            
            if ($quiz) {
                // Check if quiz is already assigned to this class
                $existing = $db->single(
                    "SELECT * FROM class_quizzes WHERE class_id = ? AND quiz_id = ?",
                    [$class_id, $quiz_id]
                );
                
                if (!$existing) {
                    // Assign the quiz to the class
                    $db->query(
                        "INSERT INTO class_quizzes (class_id, quiz_id, due_date) VALUES (?, ?, ?)",
                        [$class_id, $quiz_id, $due_date]
                    );
                }
            }
        }
        
        // Commit the transaction
        $db->getConnection()->commit();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Quizzes added to class successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback the transaction if something went wrong
        $db->getConnection()->rollBack();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to add quizzes: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
