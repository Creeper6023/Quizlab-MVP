-- Add quiz_shares table
CREATE TABLE IF NOT EXISTS quiz_shares (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quiz_id INTEGER NOT NULL,
    shared_by_id INTEGER NOT NULL,
    shared_with_id INTEGER NOT NULL,
    permission_level TEXT NOT NULL, -- 'view', 'edit', 'full'
    shared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_by_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_with_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create index for faster lookups
CREATE INDEX IF NOT EXISTS idx_quiz_shares_quiz_id ON quiz_shares(quiz_id);
CREATE INDEX IF NOT EXISTS idx_quiz_shares_shared_with_id ON quiz_shares(shared_with_id);