<?php
require_once __DIR__ . '/../../config.php';


header('Content-Type: application/json');

if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$quiz_id = $_POST['quiz_id'] ?? null;

if (!$quiz_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid quiz ID provided']);
    exit;
}

$db = new Database();

try {

    $quiz = $db->single("SELECT * FROM quizzes WHERE id = ?", [$quiz_id]);

    if (!$quiz) {
        throw new Exception("Quiz not found.");
    }


    $db->query("DELETE FROM quiz_student_access WHERE quiz_id = ?", [$quiz_id]);
    $db->query("DELETE FROM class_quizzes WHERE quiz_id = ?", [$quiz_id]);
    $db->query("DELETE FROM student_answers WHERE question_id IN (SELECT id FROM questions WHERE quiz_id = ?)", [$quiz_id]);
    $db->query("DELETE FROM questions WHERE quiz_id = ?", [$quiz_id]);
    $db->query("DELETE FROM quiz_attempts WHERE quiz_id = ?", [$quiz_id]);


    $db->query("DELETE FROM quizzes WHERE id = ?", [$quiz_id]);


    echo json_encode(['success' => true, 'message' => 'Quiz deleted successfully']);

} catch (Exception $e) {

    echo json_encode(['success' => false, 'message' => 'Error deleting quiz: ' . $e->getMessage()]);
}
exit;
?>