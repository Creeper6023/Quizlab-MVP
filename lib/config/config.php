<?php

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(dirname(__DIR__)));
}


if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}


define('DB_FILE', BASE_PATH . '/database/quizlabs.db');


define('APP_NAME', 'QuizLabs - 培正');
define('BASE_URL', '');


define('ROLE_ADMIN', 'admin');
define('ROLE_TEACHER', 'teacher');
define('ROLE_STUDENT', 'student');


define('DEEPSEEK_API_KEY', getenv('DEEPSEEK_API_KEY') ?: '');


require_once __DIR__ . '/../database/db.php';


function getSettingWithDefault($key, $default) {
    try {
        $db = new Database();
        $value = $db->getSetting($key);
        return ($value !== null && $value !== '') ? $value : $default;
    } catch (Exception $e) {
        return $default;
    }
}


define('AI_GRADING_ENABLED', getSettingWithDefault('ai_grading_enabled', '1') === '1');


define('QUICK_LOGIN_ENABLED', getSettingWithDefault('quick_login_enabled', '1') === '1');


function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function hasRole($role) {
    if (!isLoggedIn() || !isset($_SESSION['role'])) {
        return false;
    }
    return $_SESSION['role'] === ROLE_ADMIN || $_SESSION['role'] === $role;
}

function redirect($location) {
    if (!headers_sent()) {
        header("Location: $location");
    }
    exit;
}

function getUserData() {
    return isLoggedIn() ? [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role']
    ] : null;
}

function generateHashId($length = 11) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
    $id = '';
    for ($i = 0; $i < $length; $i++) {
        $id .= $characters[mt_rand(0, strlen($characters) - 1)];
    }
    return $id;
}


function getIdFromHash($table, $hashId) {
    global $db;
    
    if (!$db) {

        require_once __DIR__ . '/../database/db.php';
        $db = new Database();
    }
    
    if (empty($hashId)) {
        return null;
    }
    
    $result = $db->single("SELECT id FROM $table WHERE hash_id = ?", [$hashId]);
    return $result ? $result['id'] : null;
}


function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}


function getHashFromId($table, $id) {
    global $db;
    
    if (!$db) {

        require_once __DIR__ . '/../database/db.php';
        $db = new Database();
    }
    
    if (empty($id)) {
        return null;
    }
    
    $result = $db->single("SELECT hash_id FROM $table WHERE id = ?", [$id]);
    return $result ? $result['hash_id'] : null;
}