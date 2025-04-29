<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/database/db.php';


if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error_message'] = "You must be logged in as an admin to update settings.";
    header("Location: auth/login.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    

    if (isset($_POST['deepseek_api_key'])) {
        $apiKey = trim($_POST['deepseek_api_key']);
        $db->updateSetting('deepseek_api_key', $apiKey);
    }
    

    if (isset($_POST['ai_grading_enabled'])) {
        $aiGradingEnabled = $_POST['ai_grading_enabled'] ? '1' : '0';
        $db->updateSetting('ai_grading_enabled', $aiGradingEnabled);
    }
    

    if (isset($_POST['quick_login_enabled'])) {
        $quickLoginEnabled = $_POST['quick_login_enabled'] ? '1' : '0';
        $db->updateSetting('quick_login_enabled', $quickLoginEnabled);
    }
    

    $_SESSION['success_message'] = "Settings updated successfully.";
    

    header("Location: admin/settings.php");
    exit;
} else {

    $_SESSION['error_message'] = "Invalid request method.";
    header("Location: admin/settings.php");
    exit;
}
