<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/header.php';


$localTime = date('Y-m-d H:i:s', strtotime('now'));
file_put_contents(__DIR__ . '/../debug.log', $localTime . " - Session: " . print_r($_SESSION, true) . PHP_EOL, FILE_APPEND);
file_put_contents(__DIR__ . '/../debug.log', $localTime . " - Quiz ID: " . $_GET['id'] . PHP_EOL, FILE_APPEND);


if (!function_exists('set_flash_message')) {
    function set_flash_message($type, $message) {
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}


if (!isLoggedIn() || !hasRole(ROLE_TEACHER)) {
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();
$teacher_id = $_SESSION['user_id'];


$quiz_hash_id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($quiz_hash_id)) {
    redirect(BASE_URL . '/teacher');
    exit();
}


$quiz_id = getIdFromHash('quizzes', $quiz_hash_id);

if (!$quiz_id) {
    redirect(BASE_URL . '/teacher');
    exit();
}


$quiz = $db->single("
    SELECT * FROM quizzes WHERE id = ? AND created_by = ?
", [$quiz_id, $teacher_id]);

if (!$quiz) {

    $sharedQuiz = $db->single("
        SELECT q.* FROM quizzes q
        JOIN quiz_shares qs ON q.id = qs.quiz_id 
        WHERE q.id = ? AND qs.shared_with_id = ? 
        AND (qs.permission_level = 'edit' OR qs.permission_level = 'full')
    ", [$quiz_id, $teacher_id]);

    if (!$sharedQuiz) {
        set_flash_message('error', 'You do not have permission to publish this quiz.');
        redirect(BASE_URL . '/teacher');
        exit();
    }


    $quiz = $sharedQuiz;
}


file_put_contents(BASE_PATH . '/debug.log', $localTime . " - Publishing quiz: " . $quiz_id . PHP_EOL, FILE_APPEND);


$questionCount = $db->single("
    SELECT COUNT(*) as count FROM questions WHERE quiz_id = ?
", [$quiz_id]);

file_put_contents(BASE_PATH . '/debug.log', $localTime . " - Question count: " . ($questionCount ? $questionCount['count'] : 'none') . PHP_EOL, FILE_APPEND);

if ($questionCount && $questionCount['count'] > 0) {

    try {
        $result = $db->query(
            "UPDATE quizzes SET is_published = 1 WHERE id = ?",
            [$quiz_id]
        );

        file_put_contents(BASE_PATH . '/debug.log', date('Y-m-d H:i:s') . " - Update result: " . ($result ? "success" : "fail") . PHP_EOL, FILE_APPEND);

        set_flash_message('success', 'Quiz has been published successfully.');
        redirect(BASE_URL . '/teacher/assign_quiz_to_class.php?id=' . $quiz_hash_id);
    } catch (Exception $e) {
        file_put_contents(BASE_PATH . '/debug.log', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        set_flash_message('error', 'Database error: ' . $e->getMessage());
        redirect(BASE_URL . '/teacher/assign_quiz_to_class.php?id=' . $quiz_hash_id);
    }
} else {
    set_flash_message('error', 'Cannot publish a quiz without questions. Please add at least one question first.');
    redirect(BASE_URL . '/teacher/edit_quiz.php?id=' . $quiz_hash_id);
}

require_once __DIR__ . '/../includes/footer.php';