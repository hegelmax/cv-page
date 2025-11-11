ALTER TABLE visits ADD COLUMN user TEXT;
CREATE INDEX IF NOT EXISTS idx_visits_user ON visits(user);