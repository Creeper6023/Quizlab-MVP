<?php
// This file updates the database with new tables for the Classes feature
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';

echo "Starting database update...\n";

try {
    $db = new Database();
    
    // Read the SQL update file
    $sql = file_get_contents(__DIR__ . '/database/update_schema.sql');
    
    // Execute the SQL statements
    $db->getConnection()->exec($sql);
    
    echo "Database updated successfully!\n";
} catch (PDOException $e) {
    echo "Database update failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Done.\n";
