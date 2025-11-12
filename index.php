<?php
require_once __DIR__ . '/lib/init.php';

$templatePath  = TEMPLATES_DIR . '/main.template.html';

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
