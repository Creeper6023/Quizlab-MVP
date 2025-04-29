<?php

require_once 'config.php';

try {
    $db = new Database();
    

    $sql = file_get_contents(__DIR__ . '/database/update_schema_quiz_sharing.sql');
    

    $statements = explode(';', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $db->query($statement);
        }
    }
    
    echo "Quiz sharing database update completed successfully!";
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>