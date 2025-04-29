<?php
require_once __DIR__ . '/../../config.php';

class Database {
    private $conn;
    
    public function __construct() {
        try {
            $dir = dirname(DB_FILE);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            
            $this->conn = new PDO("sqlite:" . DB_FILE);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $this->conn->exec('PRAGMA foreign_keys = ON');
            
            $this->conn->exec('
                CREATE TABLE IF NOT EXISTS settings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    key TEXT UNIQUE NOT NULL,
                    value TEXT,
                    updated_by INTEGER,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (updated_by) REFERENCES users(id)
                )
            ');
            
            $settingsCount = $this->single("SELECT COUNT(*) as count FROM settings", []);
            if ($settingsCount && $settingsCount['count'] == 0) {
                $this->conn->exec("INSERT INTO settings (key, value) VALUES ('deepseek_api_key', '')");
                $this->conn->exec("INSERT INTO settings (key, value) VALUES ('ai_grading_enabled', '1')");
                $this->conn->exec("INSERT INTO settings (key, value) VALUES ('quick_login_enabled', '1')");
            }
            
            $this->query("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)", ['quick_login_enabled', '1']);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
    }
    
    public function single($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function resultSet($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function rowCount($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    public function exec($sql) {
        try {
            return $this->conn->exec($sql);
        } catch(PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
    }
    
    public function getSetting($key) {
        $result = $this->single("SELECT value FROM settings WHERE key = ?", [$key]);
        return $result ? $result['value'] : null;
    }
    
    public function updateSetting($key, $value, $userId = null) {
        try {
            $this->query(
                "UPDATE settings SET value = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP WHERE key = ?",
                [$value, $userId, $key]
            );
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function getAllSettings() {
        return $this->resultSet("SELECT * FROM settings");
    }
    
    public function getAllUsers() {
        return $this->resultSet("SELECT id, username, role, created_at FROM users ORDER BY username ASC");
    }
    
    public function getUserById($id) {
        return $this->single("SELECT id, username, role, created_at FROM users WHERE id = ?", [$id]);
    }
    
    public function getUserByUsername($username) {
        return $this->single("SELECT id, username, role, created_at FROM users WHERE username = ?", [$username]);
    }
    
    public function createUser($username, $password, $role) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $this->query("INSERT INTO users (username, password, role) VALUES (?, ?, ?)", 
                    [$username, $hashedPassword, $role]);
        return $this->lastInsertId();
    }
    
    public function updateUser($id, $username, $role, $password = null) {
        if ($password) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            return $this->query("UPDATE users SET username = ?, role = ?, password = ? WHERE id = ?", 
                         [$username, $role, $hashedPassword, $id]);
        } else {
            return $this->query("UPDATE users SET username = ?, role = ? WHERE id = ?", 
                         [$username, $role, $id]);
        }
    }
    
    public function deleteUser($id) {
        return $this->query("DELETE FROM users WHERE id = ?", [$id]);
    }
}
