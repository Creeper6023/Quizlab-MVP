<?php
require_once 'config.php';


function generateQuizId($length = 11) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
    $id = '';
    $charLength = strlen($characters) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $id .= $characters[mt_rand(0, $charLength)];
    }
    
    return $id;
}


$db = new Database();


$columns = $db->resultSet("PRAGMA table_info(quizzes)");
$hashIdExists = false;

foreach ($columns as $column) {
    if ($column['name'] === 'hash_id') {
        $hashIdExists = true;
        break;
    }
}


if (!$hashIdExists) {
    try {
        $db->query("ALTER TABLE quizzes ADD COLUMN hash_id VARCHAR(16)");
        echo "Added hash_id column to quizzes table.<br>";
    } catch (Exception $e) {
        echo "Error adding column: " . $e->getMessage() . "<br>";
    }
} else {
    echo "The hash_id column already exists.<br>";
}


$quizzes = $db->resultSet("SELECT id FROM quizzes WHERE hash_id IS NULL OR hash_id = ''");


$count = 0;
foreach ($quizzes as $quiz) {
    $hashId = generateQuizId();
    

    while ($db->single("SELECT COUNT(*) as count FROM quizzes WHERE hash_id = ?", [$hashId])['count'] > 0) {
        $hashId = generateQuizId();
    }
    

    $db->query("UPDATE quizzes SET hash_id = ? WHERE id = ?", [$hashId, $quiz['id']]);
    $count++;
}

echo "Updated $count quizzes with unique hash IDs.<br>";





echo "Complete. All quizzes now have unique YouTube-like hash IDs.";
?>