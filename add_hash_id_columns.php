<?php

require_once 'config.php';
require_once LIB_PATH . '/database/db.php';


$db = new Database();


function columnExists($db, $table, $column) {
    $result = $db->resultSet("PRAGMA table_info($table)");
    foreach ($result as $col) {
        if ($col['name'] === $column) {
            return true;
        }
    }
    return false;
}


$tables = ['quizzes', 'classes', 'users'];

foreach ($tables as $table) {
    if (!columnExists($db, $table, 'hash_id')) {
        echo "Adding hash_id column to $table table...\n";
        try {
            $db->exec("ALTER TABLE $table ADD COLUMN hash_id TEXT");
            echo "Successfully added hash_id column to $table table.\n";
        } catch (Exception $e) {
            echo "Error adding hash_id column to $table table: " . $e->getMessage() . "\n";
        }
    } else {
        echo "hash_id column already exists in $table table.\n";
    }
}

echo "Column update complete. You can now run generate_hash_ids.php to generate hash IDs for existing records.\n";
?>