<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/database/db.php';


header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}


if (!isLoggedIn() || !hasRole(ROLE_STUDENT)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}


$action = isset($_POST['action']) ? $_POST['action'] : '';
$attempt_id = isset($_POST['attempt_id']) ? (int)$_POST['attempt_id'] : 0;
$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$quiz_id = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0;


if (empty($action) || empty($attempt_id) || empty($student_id) || empty($quiz_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}


if ($student_id != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Student ID mismatch']);
    exit;
}

$db = new Database();


if ($action === 'log_exam_exit') {
    try {

        $quiz = $db->single("SELECT exam_mode FROM quizzes WHERE id = ?", [$quiz_id]);
        
        if (!$quiz || !$quiz['exam_mode']) {
            echo json_encode(['success' => false, 'message' => 'Exam mode not enabled for this quiz']);
            exit;
        }
        

        $result = $db->query(
            "INSERT INTO exam_exit_logs (attempt_id, student_id, quiz_id, exit_time, ip_address) 
             VALUES (?, ?, ?, NOW(), ?)",
            [
                $attempt_id, 
                $student_id, 
                $quiz_id, 
                $_SERVER['REMOTE_ADDR']
            ]
        );
        
        if ($result) {

            $teacherId = $db->single("SELECT created_by FROM quizzes WHERE id = ?", [$quiz_id]);
            
            if ($teacherId) {
                $studentName = $db->single("SELECT username FROM users WHERE id = ?", [$student_id]);
                $quizTitle = $db->single("SELECT title FROM quizzes WHERE id = ?", [$quiz_id]);
                
                $message = "Student " . ($studentName ? $studentName['username'] : "ID: $student_id") . 
                           " attempted to exit exam " . ($quizTitle ? $quizTitle['title'] : "ID: $quiz_id");
                

                $db->query(
                    "INSERT INTO notifications (user_id, message, type, created_at, is_read) 
                     VALUES (?, ?, 'exam_exit', NOW(), 0)",
                    [$teacherId['created_by'], $message]
                );
            }
            
            echo json_encode(['success' => true, 'message' => 'Exam exit attempt logged']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to log exit attempt']);
        }
        
    } catch (Exception $e) {

        error_log("Error logging exam exit: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}