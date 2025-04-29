<?php
require_once __DIR__ . '/../../config.php';


if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    

    if ($userId === $_SESSION['user_id']) {
        $_SESSION['error_message'] = 'You cannot delete your own account';
        header('Location: index.php');
        exit;
    }
    

    $user = $db->single(
        "SELECT id, username FROM users WHERE id = ?", 
        [$userId]
    );
    
    if (!$user) {
        $_SESSION['error_message'] = 'User not found';
        header('Location: index.php');
        exit;
    }
    

    try {

        $db->getConnection()->beginTransaction();
        

        $db->query("DELETE FROM quiz_attempts WHERE student_id = ?", [$userId]);
        

        $db->query("DELETE FROM class_students WHERE student_id = ?", [$userId]);
        

        $result = $db->query("DELETE FROM users WHERE id = ?", [$userId]);
        
        if ($result) {

            $db->getConnection()->commit();
            $_SESSION['success_message'] = 'User deleted successfully';
        } else {

            $db->getConnection()->rollBack();
            $_SESSION['error_message'] = 'Failed to delete user';
        }
    } catch (Exception $e) {

        $db->getConnection()->rollBack();
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
    }
    
} else {
    $_SESSION['error_message'] = 'Invalid request';
}

header('Location: index.php');
exit;
?>