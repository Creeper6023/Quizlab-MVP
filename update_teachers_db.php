<?php
require_once 'config.php';
require_once 'database/db.php';




$db = new Database();
$conn = $db->getConnection();


$sql = file_get_contents('database/update_schema_teachers.sql');


try {
    $conn->exec($sql);
    echo "Database schema updated successfully with teacher co-teaching functionality!\n";
    


    $classes = $db->resultSet("SELECT id FROM classes LIMIT 1");
    
    if (!empty($classes)) {
        $classId = $classes[0]['id'];
        

        $existing = $db->single(
            "SELECT id FROM class_teachers WHERE class_id = ? AND teacher_id = ?",
            [$classId, 2]
        );
        
        if (!$existing) {

            $db->query(
                "INSERT INTO class_teachers (class_id, teacher_id) VALUES (?, ?)",
                [$classId, 2]
            );
            echo "Assigned teacher ID 2 (tt) as co-teacher to class ID $classId for testing.\n";
        } else {
            echo "Teacher ID 2 (tt) is already assigned as co-teacher to class ID $classId.\n";
        }
    } else {
        echo "No classes found. Please create a class first using the admin account.\n";
    }
    
} catch (PDOException $e) {
    echo "Error updating database schema: " . $e->getMessage() . "\n";
}
?>