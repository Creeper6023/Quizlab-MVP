<?php
require_once 'config.php';
require_once 'lib/database/db.php';


if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    echo "You must be logged in as an administrator to run this script.";
    exit;
}

try {
    $db = new Database();
    
    echo "Updating database schema for quiz retakes...\n";
    
    $sql = file_get_contents('database/update_schema_retakes.sql');
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $db->query($statement);
            echo "Executed: " . substr($statement, 0, 60) . "...\n";
        }
    }
    
    echo "\nDatabase update for quiz retakes completed successfully!\n";
    
} catch (Exception $e) {
    echo "\nError updating database: " . $e->getMessage() . "\n";
}