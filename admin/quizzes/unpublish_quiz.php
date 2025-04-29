<?php
require_once '../../config.php';
require_once LIB_PATH . '/database/db.php';


if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    redirect(BASE_URL . '/auth/login.php');
    exit();
}

$db = new Database();


$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($quiz_id <= 0) {
    $_SESSION['error'] = 'Invalid quiz ID';
    redirect(BASE_URL . '/admin/quizzes');
    exit();
}


$quiz = $db->single("SELECT * FROM quizzes WHERE id = ?", [$quiz_id]);

if (!$quiz) {
    $_SESSION['error'] = 'Quiz not found';
    redirect(BASE_URL . '/admin/quizzes');
    exit();
}

try {

    $db->query(
        "UPDATE quizzes SET is_published = 0 WHERE id = ?",
        [$quiz_id]
    );
    
    $_SESSION['success'] = 'Quiz unpublished successfully';
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to unpublish quiz: ' . $e->getMessage();
}


redirect(BASE_URL . '/admin/quizzes');