<?php
require_once '../../config.php';
require_once LIB_PATH . '/database/db.php';


if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}


error_log("Admin create class - Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Admin create class - POST data: " . print_r($_POST, true));


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    
    if (empty($name)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Class name is required']);
        exit();
    }
    
    if ($teacher_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Teacher selection is required']);
        exit();
    }
    

    $teacher = $db->single(
        "SELECT * FROM users WHERE id = ? AND role = 'teacher'",
        [$teacher_id]
    );
    
    if (!$teacher) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Selected teacher does not exist']);
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
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Class created successfully',
            'hash_id' => $hashId
        ]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to create class: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
