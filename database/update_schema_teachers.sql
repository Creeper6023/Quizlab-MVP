-- Add class_teachers table for co-teacher functionality
CREATE TABLE IF NOT EXISTS class_teachers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    class_id INTEGER NOT NULL,
    teacher_id INTEGER NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(class_id, teacher_id)
);

-- For SQLite, we need to use a different approach to update tables since ALTER TABLE is limited
-- We'll create a new version of classes table with the updated_at column
CREATE TABLE IF NOT EXISTS classes_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    created_by INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Copy data from the old table to the new one
INSERT OR IGNORE INTO classes_new (id, name, description, created_by, created_at)
SELECT id, name, description, created_by, created_at FROM classes;

-- Drop old table if it exists and rename new one
DROP TABLE IF EXISTS classes_old;
ALTER TABLE classes RENAME TO classes_old;
ALTER TABLE classes_new RENAME TO classes;

-- Create trigger to update the updated_at timestamp for classes
DROP TRIGGER IF EXISTS update_classes_timestamp;
CREATE TRIGGER update_classes_timestamp 
AFTER UPDATE ON classes
BEGIN
    UPDATE classes SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;