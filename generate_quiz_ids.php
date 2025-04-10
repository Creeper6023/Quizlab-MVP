<?php
require_once 'config.php';

// Function to generate YouTube-like ID
function generateQuizId($length = 11) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
    $id = '';
    $charLength = strlen($characters) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $id .= $characters[mt_rand(0, $charLength)];
    }
    
    return $id;
}

// Create a database connection
$db = new Database();

// First, check if the hash_id column exists
$columns = $db->resultSet("PRAGMA table_info(quizzes)");
$hashIdExists = false;

foreach ($columns as $column) {
    if ($column['name'] === 'hash_id') {
        $hashIdExists = true;
        break;
    }
}

// Add the column if it doesn't exist
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

// Get all quizzes that don't have a hash ID yet
$quizzes = $db->resultSet("SELECT id FROM quizzes WHERE hash_id IS NULL OR hash_id = ''");

// Generate and update hash IDs for each quiz
$count = 0;
foreach ($quizzes as $quiz) {
    $hashId = generateQuizId();
    
    // Make sure it's unique
    while ($db->single("SELECT COUNT(*) as count FROM quizzes WHERE hash_id = ?", [$hashId])['count'] > 0) {
        $hashId = generateQuizId();
    }
    
    // Update the quiz with the new hash ID
    $db->query("UPDATE quizzes SET hash_id = ? WHERE id = ?", [$hashId, $quiz['id']]);
    $count++;
}

echo "Updated $count quizzes with unique hash IDs.<br>";

// Now update all references to quiz IDs in our code to use hash_id
// Let's update the quiz links first

// Add a function to get quiz by hash ID
echo "Complete. All quizzes now have unique YouTube-like hash IDs.";
?>