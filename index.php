<?php
// PROD defaults
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/log/php-error.log');

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/lib/render.php';

const DEFAULT_USER = 'default';
const DEMO_USER    = 'demo';

// ****************************************************************
// * Dynamically collect tracks from /data/{user}/*.json.
// * Optionally, specify a "track" block in the root JSON:
// *  {
// *    "track": {
// *      "slug": "developer",
// *      "label": "Programming / Engineering",
// *      "icon": "fa-code",
// *      "description": "Hands-on engineering ...",
// *      "fallback": "demo/john_doe_prog.json"   // optional, relative to /data
// *    }
// *  }
// ****************************************************************
function load_user_tracks(string $user): array {
    $user     = strtolower(preg_replace('~[^a-z0-9_-]+~i', '', $user));
    if ($user === '') {
        $user = DEFAULT_USER;
    }

    $baseRoot = __DIR__ . '/data';
    $baseDir  = $baseRoot . '/' . $user;

    if (!is_dir($baseDir)) {
        return [];
    }

    $tracks = [];

    foreach (glob($baseDir . '/*.json') as $path) {
        $json = json_decode(@file_get_contents($path), true);
        if (!is_array($json)) {
            continue;
        }

        $meta = is_array($json['track'] ?? null) ? $json['track'] : [];

        // slug
        $slug = $meta['slug'] ?? null;
        if (!$slug) {
            $fn   = basename($path);
            $slug = pathinfo($fn, PATHINFO_FILENAME);
        }
        $slug = strtolower(preg_replace('~[^a-z0-9_-]+~i', '', $slug));
        if ($slug === '') {
            continue;
        }

        // label / icon / description
        $label       = $meta['label'] ?? ($json['title'] ?? ucfirst($slug));
        $icon        = $meta['icon']  ?? 'fa-file-lines';
        $description = $meta['description'] ?? '';

        // fallback: только из JSON, относительный к /data
        $fallback = null;
        if (!empty($meta['fallback'])) {
            $rel   = ltrim((string)$meta['fallback'], '/');
            $fb    = $baseRoot . '/' . $rel;
            if (is_file($fb)) {
                $fallback = $fb;
            }
        }

        $tracks[$slug] = [
            'user'        => $user,
            'slug'        => $slug,
            'file'        => $path,
            'fallback'    => $fallback,
            'label'       => $label,
            'icon'        => $icon,
            'description' => $description,
        ];
    }

    // stable order (by label)
    uasort($tracks, fn($a, $b) => strcmp($a['label'], $b['label']));

    return $tracks;
}

// ****************************************************************
// * Route analysis:
// *
// *  /                    -> [default, '']
// *  /resume_slug         -> [default, resume_slug]
// *  /user_name           -> [user_name, '']  (if exists /data/user_name)
// *                          else [default, user_name]  (as a default user track)
// *  /user_name/resume    -> [user_name, resume]
// ****************************************************************
function resolve_route(): array {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = rtrim($path, '/');

    // root
    if ($path === '' || $path === '/') {
        return [DEFAULT_USER, ''];
    }

    // don't touch analytics
    if (stripos($path, '/analytics') === 0) {
        return [DEFAULT_USER, ''];
    }

    $trimmed = ltrim($path, '/');
    $parts   = explode('/', $trimmed, 3);
    $first   = strtolower($parts[0] ?? '');
    $second  = strtolower($parts[1] ?? '');

    $dataRoot = __DIR__ . '/data';

    // one segment: either /user_name or /resume_slug for default
    if ($second === '' || $second === null) {
        if ($first !== '' && is_dir($dataRoot . '/' . $first)) {
            // /user_name -> select this user's track or the only one
            return [$first, ''];
        }

        // /resume_slug -> default user track
        return [DEFAULT_USER, $first];
    }

    // /user_name/resume_slug
    return [$first, $second];
}

list($currentUser, $trackSlug) = resolve_route();
$TRACKS        = load_user_tracks($currentUser);
$templatePath  = __DIR__ . '/templates/main.template.html';
$isPartial     = strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'fetch-partial') === 0;
$trackCount    = count($TRACKS);

// Try demo user if default is empty
if ($trackCount === 0 && $currentUser === DEFAULT_USER){
    header('Location: /'.DEMO_USER.'/'.$trackSlug);
    exit();
}

// there are no tracks
if ($trackCount === 0) {
    $html  = '<h1>No resumes configured</h1>';
    $html .= '<p>Put at least one JSON file into /data/' . h($currentUser) . '/.</p>';
    if ($isPartial) {
        header('X-Partial: 1');
        echo $html;
        exit;
    }
    echo render_layout_page($html, 'Resume or user not found');
    exit;
}

// routing within the user
$track = null;

if ($trackSlug === '') {
    // / or /user_name
    if ($trackCount === 1) {
        // only one resume - open it immediately
        $track = array_key_first($TRACKS);
    } else {
        // several - selection page
        if ($isPartial) {
            header('X-Partial: 1');
            echo render_chooser_inner($TRACKS, $currentUser);
            exit;
        }
        echo render_layout_page(render_chooser_inner($TRACKS, $currentUser), 'Resume select');
        exit;
    }
} else {
    // /resume_slug or /user_name/resume_slug
    if (!isset($TRACKS[$trackSlug])) {
        http_response_code(404);
        $html = '<h1>Not found</h1>';
        if ($isPartial) {
            header('X-Partial: 1');
            echo $html;
            exit;
        }
        echo render_layout_page($html, '404 - Not found');
        exit;
    }

    $track = $trackSlug;
}

$meta = $TRACKS[$track] ?? null;
if (!$meta) {
    http_response_code(404);
    echo render_layout_page('<h1>Not found</h1>', '404 - Not found');
    exit;
}

// Choose JSON and template (primary → fallback if missing or invalid)
$jsonPrimary  = $meta['file'] ?? null;
$jsonFallback = $meta['fallback'] ?? null;
$jsonPath     = (is_string($jsonPrimary) && file_exists($jsonPrimary))
  ? $jsonPrimary
  : (is_string($jsonFallback) ? $jsonFallback : $jsonPrimary);

// cache key: user + track, so that different users do not overlap
$cacheKey  = ($currentUser === DEFAULT_USER ? '' : $currentUser . ':') . $track;
$innerPath = __DIR__ . "/cache/{$cacheKey}.inner.html";

// cache invalidation: by mtime + version in JSON (if any)
$tplMTime   = @filemtime($templatePath) ?: 0;
$jsonMTime  = @filemtime($jsonPath) ?: 0; // might be fallback
$innerMTime = @filemtime($innerPath) ?: 0;

$json       = json_decode(@file_get_contents($jsonPath), true) ?: [];
// If primary exists but JSON fails to parse/empty — try fallback
if (empty($json) && $jsonFallback && is_file($jsonFallback)) {
  $jsonPath   = $jsonFallback;
  $jsonMTime  = @filemtime($jsonPath) ?: $jsonMTime;
  $json       = json_decode(@file_get_contents($jsonPath), true) ?: [];
}
$jsonVer    = isset($json['version']) ? (string)$json['version'] : '';
$verPath    = __DIR__ . "/cache/{$cacheKey}.version";
$prevVer    = file_exists($verPath) ? trim(file_get_contents($verPath)) : '';
$verChanged = ($jsonVer !== '' && $jsonVer !== $prevVer);

$needsRebuild = $verChanged || ($innerMTime < max($tplMTime, $jsonMTime));

$innerExists = file_exists($innerPath);
if ($needsRebuild || !$innerExists) {
  // Flag: are we using demo data?
  $usingDemo = ($jsonPath === ($meta['fallback'] ?? null));
  // Build inner (and cache)
  $inner = render_resume_inner($currentUser, $track, $meta + ['usingDemo' => $usingDemo], $json, $templatePath, $TRACKS);
  @file_put_contents($innerPath, $inner);
  if ($jsonVer !== '') @file_put_contents($verPath, $jsonVer);
}

// Headers
$lastMod = gmdate('D, d M Y H:i:s', filemtime($innerPath)).' GMT';
$etag    = '"'.md5_file($innerPath).'"';
header('Content-Type: text/html; charset=UTF-8');
header('Last-Modified: '.$lastMod);
header('ETag: '.$etag);
header('Cache-Control: public, max-age=0, must-revalidate');

if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
  http_response_code(304); exit;
}
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] === $lastMod) {
  http_response_code(304); exit;
}

// partial / full
if ($isPartial) {
  header('X-Partial: 1');
  readfile($innerPath); exit;
}
echo render_layout_page(file_get_contents($innerPath), strtoupper($json['name'] ?? '').' - Resume'.($json['track']['label'] ? ' ['.($json['track']['label'].']' : ''));
