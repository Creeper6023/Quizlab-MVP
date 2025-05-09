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
    $student_ids = $_POST['student_ids'] ?? [];
    
    if ($class_id === 0 || empty($student_ids) || !is_array($student_ids)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No students selected']);
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
    

    $db->getConnection()->beginTransaction();
    
    try {
        $added_count = 0;
        

        foreach ($student_ids as $student_id) {
            $student_id = (int)$student_id;
            

            $student = $db->single(
                "SELECT * FROM users WHERE id = ? AND role = ?",
                [$student_id, ROLE_STUDENT]
            );
            
            if ($student) {

                $existing = $db->single(
                    "SELECT * FROM class_enrollments WHERE class_id = ? AND user_id = ?",
                    [$class_id, $student_id]
                );
                
                if (!$existing) {

                    $db->query(
                        "INSERT INTO class_enrollments (class_id, user_id) VALUES (?, ?)",
                        [$class_id, $student_id]
                    );
                    $added_count++;
                }
            }
        }
        

        $db->getConnection()->commit();
        
        header('Content-Type: application/json');
        if ($added_count > 0) {
            echo json_encode([
                'success' => true, 
                'message' => "Added $added_count student" . ($added_count !== 1 ? 's' : '') . " to class successfully"
            ]);
        } else {
            echo json_encode([
                'success' => true, 
                'message' => "No new students were added. They may already be enrolled in this class."
            ]);
        }
        
    } catch (Exception $e) {

        $db->getConnection()->rollBack();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to add students: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}