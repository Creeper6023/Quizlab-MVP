<?php

require_once 'config.php';
require_once LIB_PATH . '/database/db.php';


$db = new Database();




function generateUniqueHashId($db, $table, $existingIds = []) {
    do {
        $hashId = generateHashId();
        $result = $db->resultSet("SELECT * FROM $table WHERE hash_id = ?", [$hashId]);
    } while (in_array($hashId, $existingIds) || !empty($result));
    
    return $hashId;
}


$quizHashIds = [];
$quizzes = $db->resultSet("SELECT hash_id FROM quizzes WHERE hash_id IS NOT NULL");
foreach ($quizzes as $quiz) {
    if (!empty($quiz['hash_id'])) {
        $quizHashIds[] = $quiz['hash_id'];
    }
}

$classHashIds = [];
$classes = $db->resultSet("SELECT hash_id FROM classes WHERE hash_id IS NOT NULL");
foreach ($classes as $class) {
    if (!empty($class['hash_id'])) {
        $classHashIds[] = $class['hash_id'];
    }
}

$userHashIds = [];
$users = $db->resultSet("SELECT hash_id FROM users WHERE hash_id IS NOT NULL");
foreach ($users as $user) {
    if (!empty($user['hash_id'])) {
        $userHashIds[] = $user['hash_id'];
    }
}


$quizzesWithoutHashId = $db->resultSet("SELECT id FROM quizzes WHERE hash_id IS NULL OR hash_id = ''");
echo "Generating hash IDs for " . count($quizzesWithoutHashId) . " quizzes...\n";
foreach ($quizzesWithoutHashId as $quiz) {
    $hashId = generateUniqueHashId($db, 'quizzes', $quizHashIds);
    $db->query("UPDATE quizzes SET hash_id = ? WHERE id = ?", [$hashId, $quiz['id']]);
    $quizHashIds[] = $hashId;
    echo "Quiz ID {$quiz['id']} updated with hash ID: $hashId\n";
}


$classesWithoutHashId = $db->resultSet("SELECT id FROM classes WHERE hash_id IS NULL OR hash_id = ''");
echo "Generating hash IDs for " . count($classesWithoutHashId) . " classes...\n";
foreach ($classesWithoutHashId as $class) {
    $hashId = generateUniqueHashId($db, 'classes', $classHashIds);
    $db->query("UPDATE classes SET hash_id = ? WHERE id = ?", [$hashId, $class['id']]);
    $classHashIds[] = $hashId;
    echo "Class ID {$class['id']} updated with hash ID: $hashId\n";
}


$usersWithoutHashId = $db->resultSet("SELECT id FROM users WHERE hash_id IS NULL OR hash_id = ''");
echo "Generating hash IDs for " . count($usersWithoutHashId) . " users...\n";
foreach ($usersWithoutHashId as $user) {
    $hashId = generateUniqueHashId($db, 'users', $userHashIds);
    $db->query("UPDATE users SET hash_id = ? WHERE id = ?", [$hashId, $user['id']]);
    $userHashIds[] = $hashId;
    echo "User ID {$user['id']} updated with hash ID: $hashId\n";
}

echo "Hash ID generation complete!\n";
?>