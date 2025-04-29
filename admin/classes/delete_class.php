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
    
    if ($class_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
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
    
    try {

        $db->getConnection()->beginTransaction();
        

        

        $db->query(
            "DELETE FROM class_enrollments WHERE class_id = ?",
            [$class_id]
        );
        

        $db->query(
            "DELETE FROM class_quizzes WHERE class_id = ?",
            [$class_id]
        );
        

        $db->query(
            "DELETE FROM classes WHERE id = ?",
            [$class_id]
        );
        

        $db->getConnection()->commit();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Class deleted successfully'
        ]);
        
    } catch (Exception $e) {

        $db->getConnection()->rollBack();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to delete class: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
