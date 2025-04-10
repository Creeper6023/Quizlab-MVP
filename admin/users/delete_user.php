<?php
require_once __DIR__ . '/../../config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();

// Process delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    
    // Don't allow deleting own account
    if ($userId === $_SESSION['user_id']) {
        $_SESSION['error_message'] = 'You cannot delete your own account';
        header('Location: index.php');
        exit;
    }
    
    // Check if user exists
    $user = $db->single(
        "SELECT id, username FROM users WHERE id = ?", 
        [$userId]
    );
    
    if (!$user) {
        $_SESSION['error_message'] = 'User not found';
        header('Location: index.php');
        exit;
    }
    
    // Try to delete the user
    try {
        // Start transaction - this is important to prevent orphaned records
        $db->getConnection()->beginTransaction();
        
        // Delete user's quiz attempts if any
        $db->query("DELETE FROM quiz_attempts WHERE student_id = ?", [$userId]);
        
        // Delete user's class enrollments if any
        $db->query("DELETE FROM class_students WHERE student_id = ?", [$userId]);
        
        // Delete the user
        $result = $db->query("DELETE FROM users WHERE id = ?", [$userId]);
        
        if ($result) {
            // Commit the transaction
            $db->getConnection()->commit();
            $_SESSION['success_message'] = 'User deleted successfully';
        } else {
            // Rollback the transaction
            $db->getConnection()->rollBack();
            $_SESSION['error_message'] = 'Failed to delete user';
        }
    } catch (Exception $e) {
        // Rollback the transaction on error
        $db->getConnection()->rollBack();
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
    }
    
} else {
    $_SESSION['error_message'] = 'Invalid request';
}

header('Location: index.php');
exit;
?>