-- Create settings table
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key TEXT UNIQUE NOT NULL,
    value TEXT,
    updated_by INTEGER,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Add initial settings
INSERT OR IGNORE INTO settings (key, value) VALUES ('deepseek_api_key', '');
INSERT OR IGNORE INTO settings (key, value) VALUES ('ai_grading_enabled', '1');