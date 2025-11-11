<?php
// PROD defaults
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/log/php-error.log');

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/lib/render.php';

// ****************************************************************
// * Dynamically collect tracks from /data/*.json.
// * Optionally, specify a "track" block in the root JSON:
// *  {
// *    "track": {
// *      "slug": "developer",
// *      "label": "Programming / Engineering",
// *      "icon": "fa-code",
// *      "description": "Hands-on engineering ...",
// *      "fallback": "demo/john_doe_prog.json"
// *    }
// *  }
// ****************************************************************
function load_tracks(): array {
    $baseDir = __DIR__ . '/data';
    $tracks  = [];

    foreach (glob($baseDir . '/*.json') as $path) {
        $json = json_decode(@file_get_contents($path), true);
        if (!is_array($json)) continue;

        $meta = is_array($json['track'] ?? null) ? $json['track'] : [];

        // slug
        $slug = $meta['slug'] ?? null;
        if (!$slug) {
            $fn = basename($path);
            $slug = pathinfo($fn, PATHINFO_FILENAME);
        }
        $slug = strtolower(preg_replace('~[^a-z0-9_-]+~i', '', $slug));
        if ($slug === '') continue;

        // label / icon / description
        $label = $meta['label'] ?? ($json['title'] ?? ucfirst($slug));
        $icon  = $meta['icon']  ?? 'fa-file-lines';
        $description = $meta['description'] ?? '';

        // demo fallback: either from JSON or built-in for dev/analyst
        $fallback = null;
        if (!empty($meta['fallback'])) {
            $fb = $baseDir . '/' . ltrim((string)$meta['fallback'], '/');
            if (is_file($fb)) $fallback = $fb;
        } else {
            if ($slug === 'developer') {
                $fb = $baseDir . '/demo/john_doe_prog.json';
                if (is_file($fb)) $fallback = $fb;
            } elseif ($slug === 'analyst') {
                $fb = $baseDir . '/demo/john_doe_analyst.json';
                if (is_file($fb)) $fallback = $fb;
            }
        }

        $tracks[$slug] = [
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

function resolve_track(): string {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = rtrim($path, '/');
    if ($path === '' || $path === '/') return ''; // root
    // we don't touch analytics
    if (stripos($path, '/analytics') === 0) return '';
    return strtolower(ltrim($path, '/'));
}

$TRACKS        = load_tracks();
echo '<!--'.PHP_EOL;
print_r $TRACKS;
echo PHP_EOL.'-->'.PHP_EOL;

$templatePath  = __DIR__ . '/templates/main.template.html';
$isPartial     = strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'fetch-partial') === 0;
$trackSlug     = resolve_track();
$trackCount    = count($TRACKS);

// there are no tracks
if ($trackCount === 0) {
    $html = '<h1>No resumes configured</h1><p>Put at least one JSON file into /data.</p>';
    if ($isPartial) { header('X-Partial: 1'); echo $html; exit; }
    echo render_layout_page($html);
    exit;
}

// one track - no buttons or chooser, always open it
if ($trackCount === 1) {
    $track = array_key_first($TRACKS);
} else {
    // several tracks
    if ($trackSlug === '') {
        // root -> selection page
        if ($isPartial) { header('X-Partial: 1'); echo render_chooser_inner($TRACKS); exit; }
        echo render_layout_page(render_chooser_inner($TRACKS));
        exit;
    }

    if (!isset($TRACKS[$trackSlug])) {
        http_response_code(404);
        $html = '<h1>Not found</h1>';
        if ($isPartial) { header('X-Partial: 1'); echo $html; exit; }
        echo render_layout_page($html);
        exit;
    }

    $track = $trackSlug;
}

$meta = $TRACKS[$track] ?? null;
if (!$meta) { http_response_code(404); echo render_layout_page('<h1>Not found</h1>'); exit; }

// Choose JSON and template (primary → fallback if missing or invalid)
$jsonPrimary = $meta['file'] ?? null;
$jsonFallback= $meta['fallback'] ?? null;
$jsonPath    = (is_string($jsonPrimary) && file_exists($jsonPrimary))
  ? $jsonPrimary
  : (is_string($jsonFallback) ? $jsonFallback : $jsonPrimary);
$innerPath   = __DIR__ . "/cache/{$track}.inner.html";

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
$verPath    = __DIR__ . "/cache/{$track}.version";
$prevVer    = file_exists($verPath) ? trim(file_get_contents($verPath)) : '';
$verChanged = ($jsonVer !== '' && $jsonVer !== $prevVer);

$needsRebuild = $verChanged || ($innerMTime < max($tplMTime, $jsonMTime));

$innerExists = file_exists($innerPath);
if ($needsRebuild || !$innerExists) {
  // Flag: are we using demo data?
  $usingDemo = ($jsonPath === ($meta['fallback'] ?? null));
  // Build inner (and cache)
  $inner = render_resume_inner($track, $meta + ['usingDemo' => $usingDemo], $json, $templatePath, $TRACKS);
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
echo render_layout_page(file_get_contents($innerPath));

