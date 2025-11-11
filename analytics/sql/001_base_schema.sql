-- 001_base_schema.sql
-- Base schema for analytics: visits + rate tables and indexes.

CREATE TABLE IF NOT EXISTS visits (
  id        INTEGER PRIMARY KEY AUTOINCREMENT,
  ts        INTEGER NOT NULL,
  ip        TEXT,
  ua        TEXT,
  country   TEXT,
  cf_ray    TEXT,
  url       TEXT,
  path      TEXT,
  ref       TEXT,
  utm       TEXT,
  lang      TEXT,
  languages TEXT,
  tz        TEXT,
  dpr       REAL,
  vp_w      INTEGER,
  vp_h      INTEGER,
  scr_w     INTEGER,
  scr_h     INTEGER,
  theme     TEXT,
  nav_type  TEXT,
  ttfb      REAL,
  dom_inter REAL,
  dcl       REAL,
  load      REAL,
  type      TEXT DEFAULT 'visit'
);

CREATE TABLE IF NOT EXISTS rate (
  ip TEXT PRIMARY KEY,
  ts INTEGER
);

CREATE INDEX IF NOT EXISTS idx_visits_ts   ON visits(ts);
CREATE INDEX IF NOT EXISTS idx_visits_path ON visits(path);
CREATE INDEX IF NOT EXISTS idx_visits_ref  ON visits(ref);
CREATE INDEX IF NOT EXISTS idx_visits_type ON visits(type);
