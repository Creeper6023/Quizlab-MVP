<?php
require_once '../../config.php';
require_once LIB_PATH . '/database/db.php';


if (!isLoggedIn() || !hasRole(ROLE_TEACHER)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}


error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $teacher_id = $_SESSION['user_id'];
    

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Class name is required']);
        exit();
    }
    
    try {

        $hashId = generateHashId();
        

        while ($db->resultSet("SELECT id FROM classes WHERE hash_id = ?", [$hashId])) {
            $hashId = generateHashId();
        }
        

        $db->query(
            "INSERT INTO classes (name, description, created_by, hash_id) VALUES (?, ?, ?, ?)",
            [$name, $description, $teacher_id, $hashId]
        );
        
        $class_id = $db->lastInsertId();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Class created successfully',
            'class_id' => $class_id
        ]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to create class: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
