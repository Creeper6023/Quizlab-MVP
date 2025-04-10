<?php
require_once "config.php";
require_once "database/db.php";

// Function to update a user's password
function updateUserPassword($db, $username, $newPassword) {
    try {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        $stmt = $db->getConnection()->prepare("UPDATE users SET password = ? WHERE username = ?");
        $result = $stmt->execute([$hashedPassword, $username]);
        
        if ($result) {
            echo "Password updated for user: $username<br>";
            return true;
        } else {
            echo "Failed to update password for user: $username<br>";
            return false;
        }
    } catch (Exception $e) {
        echo "Error updating password for $username: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Connect to database
try {
    $db = new Database();
    echo "Connected to database successfully.<br>";
    
    // Update passwords for default users
    updateUserPassword($db, "admin", "admin123");
    updateUserPassword($db, "teacher", "teacher123");
    updateUserPassword($db, "student", "student123");
    
    echo "<br>Password update complete.<br>";
    echo "<a href='auth/login.php'>Go to login page</a>";
    
} catch (Exception $e) {
    echo "Database connection error: " . $e->getMessage();
}
?>