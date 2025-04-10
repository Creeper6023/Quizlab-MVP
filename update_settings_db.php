<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/database/db.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error_message'] = "You must be logged in as an admin to update settings.";
    header("Location: auth/login.php");
    exit;
}

// Handle form submission for updating settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    
    // Save DeepSeek API key
    if (isset($_POST['deepseek_api_key'])) {
        $apiKey = trim($_POST['deepseek_api_key']);
        $db->updateSetting('deepseek_api_key', $apiKey);
    }
    
    // Save AI grading enabled setting
    if (isset($_POST['ai_grading_enabled'])) {
        $aiGradingEnabled = $_POST['ai_grading_enabled'] ? '1' : '0';
        $db->updateSetting('ai_grading_enabled', $aiGradingEnabled);
    }
    
    // Save Quick Login enabled setting
    if (isset($_POST['quick_login_enabled'])) {
        $quickLoginEnabled = $_POST['quick_login_enabled'] ? '1' : '0';
        $db->updateSetting('quick_login_enabled', $quickLoginEnabled);
    }
    
    // Set success message
    $_SESSION['success_message'] = "Settings updated successfully.";
    
    // Redirect back to settings page
    header("Location: admin/settings.php");
    exit;
} else {
    // Invalid request method
    $_SESSION['error_message'] = "Invalid request method.";
    header("Location: admin/settings.php");
    exit;
}
