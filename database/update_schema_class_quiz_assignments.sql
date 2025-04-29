-- Add class_quiz_assignments table to link quizzes to classes
CREATE TABLE IF NOT EXISTS class_quiz_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    class_id INTEGER NOT NULL,
    quiz_id INTEGER NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Create index for faster lookups
CREATE INDEX IF NOT EXISTS idx_class_quiz_assignments_class_id ON class_quiz_assignments(class_id);
CREATE INDEX IF NOT EXISTS idx_class_quiz_assignments_quiz_id ON class_quiz_assignments(quiz_id);