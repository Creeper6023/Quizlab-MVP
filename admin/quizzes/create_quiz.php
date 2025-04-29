<?php
require_once '../../config.php';
require_once LIB_PATH . '/database/db.php';


if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    error_log("Admin create quiz - Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Admin create quiz - POST data: " . print_r($_POST, true));
    
    $db = new Database();
    $admin_id = $_SESSION['user_id'];
    

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($title)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Quiz title is required']);
        exit();
    }
    
    try {

        $hashId = generateHashId();
        

        while ($db->resultSet("SELECT id FROM quizzes WHERE hash_id = ?", [$hashId])) {
            $hashId = generateHashId();
        }
        

        $db->query(
            "INSERT INTO quizzes (title, description, created_by, is_published, hash_id) VALUES (?, ?, ?, 0, ?)",
            [$title, $description, $admin_id, $hashId]
        );
        
        $quiz_id = $db->lastInsertId();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Quiz created successfully',
            'quiz_id' => $quiz_id,
            'hash_id' => $hashId
        ]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to create quiz: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}