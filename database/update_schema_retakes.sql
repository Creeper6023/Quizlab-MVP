-- Add quiz retakes table for tracking retake permissions

CREATE TABLE IF NOT EXISTS quiz_retakes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quiz_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    granted_by INTEGER NOT NULL,
    granted_at DATETIME NOT NULL,
    used BOOLEAN NOT NULL DEFAULT 0,
    used_at DATETIME,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Create an index for faster lookups
CREATE INDEX IF NOT EXISTS idx_quiz_retakes_lookup 
ON quiz_retakes(quiz_id, student_id, used);