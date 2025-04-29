<?php
require_once "config.php";


if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    header("Location: ./auth/login.php");
    exit;
}


if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    file_put_contents(__DIR__ . '/debug.log', '');
    file_put_contents(__DIR__ . '/ai_debug.log', '');
    echo "Logs cleared successfully";
    exit;
}


$log_type = $_GET['log'] ?? 'debug';

$log_file = __DIR__ . '/' . ($log_type === 'ai_debug' ? 'ai_debug.log' : 'debug.log');


if (!file_exists($log_file)) {
    file_put_contents($log_file, "Log file created on " . date('Y-m-d H:i:s') . "\n");
}


$log_content = file_get_contents($log_file);


echo $log_content;
?>
