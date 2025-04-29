-- Add hash_id field to quizzes table if it doesn't exist
-- We're using SQLite syntax for checking if column exists
CREATE TABLE IF NOT EXISTS temp_quizzes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hash_id TEXT
);
INSERT INTO temp_quizzes (id) VALUES (1);
DROP TABLE temp_quizzes;
ALTER TABLE quizzes ADD COLUMN hash_id TEXT DEFAULT NULL;

-- Add hash_id field to classes table if it doesn't exist
CREATE TABLE IF NOT EXISTS temp_classes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hash_id TEXT
);
INSERT INTO temp_classes (id) VALUES (1);
DROP TABLE temp_classes;
ALTER TABLE classes ADD COLUMN hash_id TEXT DEFAULT NULL;

-- Add hash_id field to users table if it doesn't exist
CREATE TABLE IF NOT EXISTS temp_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hash_id TEXT
);
INSERT INTO temp_users (id) VALUES (1);
DROP TABLE temp_users;
ALTER TABLE users ADD COLUMN hash_id TEXT DEFAULT NULL;