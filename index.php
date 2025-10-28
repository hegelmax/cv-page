<?php
// PROD defaults
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/log/php-error.log');

require_once __DIR__ . '/lib/render.php';

// Tracks config (with demo fallbacks)
$TRACKS = [
  'developer' => [
    'file'     => __DIR__ . '/data/user_prog.json',
    'fallback' => __DIR__ . '/data/demo/john_doe_prog.json',
    'label'    => 'Programming / Engineering',
    'icon'     => 'fa-code'
  ],
  'analyst'   => [
    'file'     => __DIR__ . '/data/user_analyst.json',
    'fallback' => __DIR__ . '/data/demo/john_doe_analyst.json',
    'label'    => 'Management / Analytics',
    'icon'     => 'fa-chart-line'
  ],
];

function resolve_track(): string {
  $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
  if (preg_match('#^/(developer|analyst)/?$#i', $path, $m)) return strtolower($m[1]);
  return ''; // chooser
}

$track = resolve_track();
$templatePath = __DIR__ . '/templates/main.template.html';

// AJAX mode for partial
$isPartial = strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'fetch-partial') === 0;

// chooser
if ($track === '') {
  if ($isPartial) { header('X-Partial: 1'); echo render_chooser_inner($TRACKS); exit; }
  echo render_layout_page(render_chooser_inner($TRACKS));
  exit;
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

