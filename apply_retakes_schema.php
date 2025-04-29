<?php
require_once(__DIR__.'/lib/database/db.php');

try {
    $db = new Database();
    
    $sql = file_get_contents(__DIR__.'/database/update_schema_retakes.sql');
    

    $db->query($sql);
    
    echo "Quiz retakes table created successfully!\n";
} catch (Exception $e) {
    echo "Error updating database schema: " . $e->getMessage() . "\n";
}