<?php
require_once '../../config.php';
require_once LIB_PATH . '/database/db.php';


if (!isLoggedIn() || !hasRole(ROLE_TEACHER)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $teacher_id = $_SESSION['user_id'];
    

    $class_id = (int)($_POST['class_id'] ?? 0);
    $quiz_id = (int)($_POST['quiz_id'] ?? 0);
    
    if ($class_id === 0 || $quiz_id === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    

    $class = $db->single(
        "SELECT c.* FROM classes c
         LEFT JOIN class_teachers ct ON c.id = ct.class_id
         WHERE c.id = ? AND (c.created_by = ? OR ct.teacher_id = ?)",
        [$class_id, $teacher_id, $teacher_id]
    );
    
    if (!$class) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Class not found or you do not have permission to edit it']);
        exit();
    }
    

    $class_quiz = $db->single(
        "SELECT * FROM class_quizzes WHERE class_id = ? AND quiz_id = ?",
        [$class_id, $quiz_id]
    );
    
    if (!$class_quiz) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Quiz not found in this class']);
        exit();
    }
    

    $attempts = $db->resultSet(
        "SELECT * FROM quiz_attempts 
         WHERE quiz_id = ? 
         AND user_id IN (
             SELECT user_id FROM class_enrollments WHERE class_id = ?
         )",
        [$quiz_id, $class_id]
    );
    
    if (!empty($attempts)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot remove this quiz because students have already started or completed it.'
        ]);
        exit();
    }
    

    try {
        $db->query(
            "DELETE FROM class_quizzes WHERE class_id = ? AND quiz_id = ?",
            [$class_id, $quiz_id]
        );
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Quiz removed from class successfully'
        ]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to remove quiz: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}