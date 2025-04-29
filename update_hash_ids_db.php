<?php

require_once 'config.php';
require_once LIB_PATH . '/database/db.php';


$db = new Database();


$sql = file_get_contents(__DIR__ . '/database/update_schema_hash_ids.sql');


$statements = array_filter(array_map('trim', explode(';', $sql)), 'strlen');


$error = false;
foreach ($statements as $statement) {
    try {
        $db->exec($statement);
        echo "Successfully executed: " . substr($statement, 0, 50) . "...\n";
    } catch (Exception $e) {
        echo "Error executing statement: " . $statement . "\n";
        echo "Error message: " . $e->getMessage() . "\n";
        $error = true;
    }
}

if (!$error) {
    echo "Database schema updated successfully.\n";
    echo "Run generate_hash_ids.php to generate hash IDs for existing records.\n";
} else {
    echo "There were errors updating the database schema.\n";
}
?>