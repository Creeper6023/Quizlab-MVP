<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db.php';

// Check if user is a teacher
if (!isLoggedIn() || !hasRole(ROLE_TEACHER)) {
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();
$teacher_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify the quiz exists and belongs to this teacher
$quiz = $db->single("
    SELECT * FROM quizzes 
    WHERE id = ? AND created_by = ?
", [$quiz_id, $teacher_id]);

if (!$quiz) {
    // Quiz doesn't exist or doesn't belong to this teacher
    redirect(BASE_URL . '/teacher');
}

// Check if there are any ongoing attempts (not completed)
$activeAttempts = $db->single("
    SELECT COUNT(*) as count FROM quiz_attempts 
    WHERE quiz_id = ? AND status = 'in_progress'
", [$quiz_id]);

if ($activeAttempts && $activeAttempts['count'] > 0) {
    // Can't unpublish with active attempts
    redirect(BASE_URL . '/teacher/edit_quiz.php?id=' . $quiz_id . '&error=active_attempts');
} else {
    // Update quiz to unpublished
    $db->query(
        "UPDATE quizzes SET is_published = 0 WHERE id = ?",
        [$quiz_id]
    );
    
    // Return to the edit page with success message
    redirect(BASE_URL . '/teacher/edit_quiz.php?id=' . $quiz_id . '&success=unpublished');
}
