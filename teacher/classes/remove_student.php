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
    $student_id = (int)($_POST['student_id'] ?? 0);
    
    if ($class_id === 0 || $student_id === 0) {
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
    

    $enrollment = $db->single(
        "SELECT * FROM class_enrollments WHERE class_id = ? AND user_id = ?",
        [$class_id, $student_id]
    );
    
    if (!$enrollment) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Student not found in this class']);
        exit();
    }
    

    $attempts = $db->resultSet(
        "SELECT qa.* FROM quiz_attempts qa
         JOIN class_quizzes cq ON qa.quiz_id = cq.quiz_id
         WHERE cq.class_id = ? AND qa.user_id = ?",
        [$class_id, $student_id]
    );
    
    if (!empty($attempts)) {

        $warning = "Student has quiz attempts in this class. Removing them will not delete their quiz records.";
    } else {
        $warning = null;
    }
    

    try {
        $db->query(
            "DELETE FROM class_enrollments WHERE class_id = ? AND user_id = ?",
            [$class_id, $student_id]
        );
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Student removed from class successfully',
            'warning' => $warning
        ]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to remove student: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}