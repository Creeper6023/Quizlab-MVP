<?php
// Make sure no output has been sent before starting the session
// Session configuration - this must be at the top before any output
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// Database configuration
define('DB_FILE', __DIR__ . '/../../database/quizlabs.db');

// Application configuration
define('APP_NAME', 'QuizLabs - 培正');
define('BASE_URL', '');

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_TEACHER', 'teacher');
define('ROLE_STUDENT', 'student');

// DeepSeek API Configuration
// Default API key should be defined here, admin can update it later
// IMPORTANT: This is a placeholder API key, it should be updated in the admin settings
define('DEEPSEEK_API_KEY', getenv('DEEPSEEK_API_KEY') ?: '');

// Database interface required for settings
require_once __DIR__ . '/../database/db.php';

// Function to get setting from database with fallback
function getSettingWithDefault($key, $default) {
    try {
        $db = new Database();
        $value = $db->getSetting($key);
        
        if ($value !== null && $value !== '') {
            return $value;
        }
    } catch (Exception $e) {
        // Fallback to default value on error
    }
    
    return $default;
}

// AI Grading Settings - get from database with fallback to true
$aiGradingEnabled = getSettingWithDefault('ai_grading_enabled', '1');
define('AI_GRADING_ENABLED', $aiGradingEnabled === '1');

// Quick Login Settings - get from database with fallback to true 
$quickLoginEnabled = getSettingWithDefault('quick_login_enabled', '1');
define('QUICK_LOGIN_ENABLED', $quickLoginEnabled === '1');

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to check if user has a specific role
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Function to redirect user
function redirect($location) {
    header("Location: $location");
    exit;
}

// Function to get user's data
function getUserData() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ];
    }
    return null;
}
