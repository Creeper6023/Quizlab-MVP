<?php
require_once "config.php";

// Check if user is logged in and has admin role
if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    header("Location: ./auth/login.php");
    exit;
}

// Clear logs if requested
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    file_put_contents(__DIR__ . '/debug.log', '');
    file_put_contents(__DIR__ . '/ai_debug.log', '');
    echo "Logs cleared successfully";
    exit;
}

// Determine which log to display
$log_type = $_GET['log'] ?? 'debug';

$log_file = __DIR__ . '/' . ($log_type === 'ai_debug' ? 'ai_debug.log' : 'debug.log');

// Check if file exists, if not create it
if (!file_exists($log_file)) {
    file_put_contents($log_file, "Log file created on " . date('Y-m-d H:i:s') . "\n");
}

// Get log content
$log_content = file_get_contents($log_file);

// Output just the content
echo $log_content;
?>
