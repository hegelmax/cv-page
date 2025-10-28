<?php
// /analytics/bootstrap.php
declare(strict_types=1);

$baseDir = __DIR__;
$dbFile  = $baseDir . '/analytics.db';
if (!is_dir($baseDir)) { mkdir($baseDir, 0775, true); }

function db(): PDO {
  static $pdo;
  global $dbFile;
  if ($pdo) return $pdo;
  $pdo = new PDO('sqlite:' . $dbFile, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  $pdo->exec('PRAGMA journal_mode=WAL;');
  $pdo->exec('PRAGMA synchronous=NORMAL;');
  // таблицы
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS visits (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      ts INTEGER NOT NULL,
      ip TEXT, ua TEXT, country TEXT, cf_ray TEXT,
      url TEXT, path TEXT, ref TEXT,
      utm TEXT, lang TEXT, languages TEXT, tz TEXT,
      dpr REAL, vp_w INTEGER, vp_h INTEGER, scr_w INTEGER, scr_h INTEGER,
      theme TEXT, nav_type TEXT,
      ttfb REAL, dom_inter REAL, dcl REAL, load REAL,
      type TEXT DEFAULT 'visit'
    );
    CREATE INDEX IF NOT EXISTS idx_visits_ts ON visits(ts);
    CREATE INDEX IF NOT EXISTS idx_visits_path ON visits(path);
    CREATE INDEX IF NOT EXISTS idx_visits_ref ON visits(ref);
    CREATE INDEX IF NOT EXISTS idx_visits_type ON visits(type);
    
    CREATE TABLE IF NOT EXISTS rate (ip TEXT PRIMARY KEY, ts INTEGER);
  ");
  return $pdo;
}

function get_ip(): string {
  foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $h) {
    if (!empty($_SERVER[$h])) {
      $val = $_SERVER[$h];
      if ($h === 'HTTP_X_FORWARDED_FOR') { $parts = explode(',', $val); $val = trim($parts[0]); }
      return $val;
    }
  }
  return '0.0.0.0';
}

function get_ua(): string {
  return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512);
}

function get_cf_country(): ?string {
  // Если сайт за Cloudflare — берём страну
  return $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null;
}
