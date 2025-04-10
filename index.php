<?php
require_once __DIR__ . '/config.php';

// Redirect to appropriate dashboard based on role
if (isLoggedIn()) {
    switch($_SESSION['role']) {
        case ROLE_ADMIN:
            redirect(BASE_URL . '/admin');
            break;
        case ROLE_TEACHER:
            redirect(BASE_URL . '/teacher');
            break;
        case ROLE_STUDENT:
            redirect(BASE_URL . '/student');
            break;
        default:
            // Should never happen
            redirect(BASE_URL . '/auth/logout.php');
    }
} else {
    // Redirect to login page
    redirect(BASE_URL . '/auth/login.php');
}
