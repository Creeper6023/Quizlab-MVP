<?php
require_once '../../config.php';
require_once LIB_PATH . '/database/db.php';


if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    

    $class_id = (int)($_POST['class_id'] ?? 0);
    $quiz_ids = $_POST['quiz_ids'] ?? [];
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    
    if (empty($quiz_ids) || !is_array($quiz_ids)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No quizzes selected']);
        exit();
    }
    

    $class = $db->single(
        "SELECT * FROM classes WHERE id = ?",
        [$class_id]
    );
    
    if (!$class) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Class not found']);
        exit();
    }
    

    $db->getConnection()->beginTransaction();
    
    try {

        foreach ($quiz_ids as $quiz_id) {
            $quiz_id = (int)$quiz_id;
            

            $quiz = $db->single(
                "SELECT * FROM quizzes WHERE id = ?",
                [$quiz_id]
            );
            
            if ($quiz) {

                $existing = $db->single(
                    "SELECT * FROM class_quizzes WHERE class_id = ? AND quiz_id = ?",
                    [$class_id, $quiz_id]
                );
                
                if (!$existing) {

                    $db->query(
                        "INSERT INTO class_quizzes (class_id, quiz_id, due_date) VALUES (?, ?, ?)",
                        [$class_id, $quiz_id, $due_date]
                    );
                }
            }
        }
        

        $db->getConnection()->commit();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Quizzes added to class successfully'
        ]);
        
    } catch (Exception $e) {

        $db->getConnection()->rollBack();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to add quizzes: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
