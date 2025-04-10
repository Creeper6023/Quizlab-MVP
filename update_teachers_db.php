<?php
require_once 'config.php';
require_once 'database/db.php';

// This script updates the database with the new schema for teacher co-teaching functionality

// Initialize the database connection
$db = new Database();
$conn = $db->getConnection();

// Read the SQL file
$sql = file_get_contents('database/update_schema_teachers.sql');

// Execute the SQL statements
try {
    $conn->exec($sql);
    echo "Database schema updated successfully with teacher co-teaching functionality!\n";
    
    // Add test data - assign the test teacher (id=2) as a co-teacher to a class
    // First check if there are any classes
    $classes = $db->resultSet("SELECT id FROM classes LIMIT 1");
    
    if (!empty($classes)) {
        $classId = $classes[0]['id'];
        
        // Check if the teacher is already assigned
        $existing = $db->single(
            "SELECT id FROM class_teachers WHERE class_id = ? AND teacher_id = ?",
            [$classId, 2]
        );
        
        if (!$existing) {
            // Assign the teacher
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