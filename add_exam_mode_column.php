<?php

require_once 'lib/database/db.php';


$db = new Database();


try {

    $columnExists = $db->single(
        "SELECT COUNT(*) as count FROM pragma_table_info('quizzes') WHERE name = 'exam_mode'"
    );
    
    if (!$columnExists || $columnExists['count'] == 0) {

        $db->query(
            "ALTER TABLE quizzes ADD COLUMN exam_mode INTEGER DEFAULT 0"
        );
        echo "Added exam_mode column to quizzes table.\n";
    } else {
        echo "exam_mode column already exists in quizzes table.\n";
    }
    

    $db->query(
        "UPDATE quizzes SET allow_redo = 0 WHERE allow_redo IS NULL"
    );
    echo "Updated null allow_redo values to 0.\n";
    
    echo "Database update completed successfully.";
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>