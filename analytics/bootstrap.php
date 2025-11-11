<?php
// /analytics/bootstrap.php
declare(strict_types=1);

$baseDir = __DIR__;
$dbFile  = $baseDir . '/analytics.db';
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0775, true);
}

/**
 * Get shared PDO instance.
 * - opens SQLite DB
 * - applies PRAGMAs
 * - runs SQL migrations from ./sql
 */
function db(): PDO {
    static $pdo;
    global $dbFile;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . $dbFile, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec('PRAGMA journal_mode=WAL;');
    $pdo->exec('PRAGMA synchronous=NORMAL;');

    run_migrations($pdo);

    return $pdo;
}

/**
 * Apply SQL migrations from ./sql directory.
 *
 * - Files are executed in alphabetical order.
 * - Each file is only applied once (tracked in schema_migrations table).
 * - Each file may contain multiple SQL statements separated by ';'.
 */
function run_migrations(PDO $pdo): void {
    $sqlDir = __DIR__ . '/sql';
    if (!is_dir($sqlDir)) {
        return;
    }

    // Track applied migrations
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            filename   TEXT PRIMARY KEY,
            applied_at INTEGER NOT NULL
        );
    ");

    $applied = $pdo->query('SELECT filename FROM schema_migrations')
                   ->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $appliedSet = [];
    foreach ($applied as $fn) {
        $appliedSet[$fn] = true;
    }

    $files = glob($sqlDir . '/*.sql') ?: [];
    sort($files, SORT_STRING);

    foreach ($files as $path) {
        $file = basename($path);
        if (isset($appliedSet[$file])) {
            continue; // already applied
        }

        $sql = file_get_contents($path);
        if ($sql === false || trim($sql) === '') {
            continue;
        }

        try {
            $pdo->beginTransaction();
            $pdo->exec($sql);
            $stmt = $pdo->prepare('INSERT INTO schema_migrations(filename, applied_at) VALUES(?, ?)');
            $stmt->execute([$file, time()]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Analytics migration failed for ' . $file . ': ' . $e->getMessage());
            // Stop on first failed migration to avoid partial state
            break;
        }
    }
}

/**
 * Resolve client IP (supports Cloudflare and X-Forwarded-For).
 */
function get_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $h) {
        if (!empty($_SERVER[$h])) {
            $val = $_SERVER[$h];
            if ($h === 'HTTP_X_FORWARDED_FOR') {
                $parts = explode(',', $val);
                $val   = trim($parts[0]);
            }
            return $val;
        }
    }
    return '0.0.0.0';
}

/**
 * Resolve user agent (shortened).
 */
function get_ua(): string {
    return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512);
}

/**
 * Resolve country code from Cloudflare header (if behind CF).
 */
function get_cf_country(): ?string {
    return $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null;
}

/**
 * Detect logical resume owner ("user") from request path.
 *
 * Rules:
 *  - "/" and "/{slug}" → "default"
 *  - "/{user}" and "/{user}/{slug}" where "data/{user}" is a directory → that user
 *  - "/analytics/*" and other technical paths → null (no resume user)
 */
function detect_user_from_path(?string $path): ?string {
    $path = (string)$path;
    $pure = parse_url($path, PHP_URL_PATH) ?? '';
    $pure = trim($pure, '/');

    if ($pure === '' || $pure === 'index.php') {
        // Root chooser belongs to default user
        return 'default';
    }

    // Ignore analytics area completely
    if (strpos($pure, 'analytics/') === 0) {
        return null;
    }

    static $userDirs = null;
    if ($userDirs === null) {
        $userDirs = [];
        $root = realpath(__DIR__ . '/../data') ?: (__DIR__ . '/../data');
        if (is_dir($root)) {
            foreach (scandir($root) as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                if (is_dir($root . '/' . $item)) {
                    $userDirs[$item] = true;
                }
            }
        }
    }

    $segments = explode('/', $pure);
    $first    = $segments[0] ?? '';

    // If first segment is a known user directory — treat as that user
    if ($first !== '' && isset($userDirs[$first])) {
        return $first;
    }

    // Single-segment paths like "/resume_slug" belong to default user
    if (count($segments) === 1) {
        return 'default';
    }

    // Fallback: treat anything else as default
    return 'default';
}
