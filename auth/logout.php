<?php
require_once "../config.php";

// Log the logout
if (isset($_SESSION['username'])) {
    error_log("User {$_SESSION['username']} logged out");
}

// Clear all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Redirect to login page
header("Location: ../auth/login.php");
exit;
?>
