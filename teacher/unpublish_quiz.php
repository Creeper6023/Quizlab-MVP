<?php
require_once __DIR__ . '/../config.php';



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

        set_flash_message('error', 'You do not have permission to unpublish this quiz.');
        redirect(BASE_URL . '/teacher');
        exit();
    }
    

    $quiz = $sharedQuiz;
}


$activeAttempts = $db->single("
    SELECT COUNT(*) as count FROM quiz_attempts 
    WHERE quiz_id = ? AND status = 'in_progress'
", [$quiz_id]);

if ($activeAttempts && $activeAttempts['count'] > 0) {

    set_flash_message('error', 'Cannot unpublish quiz with active student attempts in progress.');
    redirect(BASE_URL . '/teacher/assign_quiz_to_class.php?id=' . $quiz_hash_id);
} else {

    $db->query(
        "UPDATE quizzes SET is_published = 0 WHERE id = ?",
        [$quiz_id]
    );
    

    set_flash_message('success', 'Quiz has been unpublished successfully.');
    redirect(BASE_URL . '/teacher/assign_quiz_to_class.php?id=' . $quiz_hash_id);
}
