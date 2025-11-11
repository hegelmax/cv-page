<?php
// /analytics/track.php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

// Ignore admin hits via cookie (set from analytics UI)
if (!empty($_COOKIE['an_ignore'])) {
  header('Content-Type: application/json; charset=UTF-8');
  echo '{"ok":true}';
  exit;
}

// Basic origin/referrer guard (anti-forgery)
$host    = $_SERVER['HTTP_HOST'] ?? '';
$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';

if ($origin && parse_url($origin, PHP_URL_HOST) !== $host) { http_response_code(403); exit; }
if ($referer && parse_url($referer, PHP_URL_HOST) !== $host) { http_response_code(403); exit; }

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$raw = file_get_contents('php://input');
if (!$raw || strlen($raw) > 64*1024) {
  http_response_code(400);
  echo '{"ok":false}';
  exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
  http_response_code(400);
  echo '{"ok":false}';
  exit;
}

$ts   = (int)($payload['ts'] ?? round(microtime(true)*1000));
$url  = substr($payload['url'] ?? '', 0, 2048);
$path = substr($payload['path'] ?? '', 0, 512);

// Do not count analytics pages themselves
if (strpos($path, '/analytics') === 0) {
  echo '{"ok":true}';
  exit;
}

$ref  = substr($payload['ref'] ?? '', 0, 2048);
$utm  = json_encode($payload['utm'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$lang = substr($payload['lang'] ?? '', 0, 32);
$lgs  = json_encode($payload['languages'] ?? []);
$tz   = substr($payload['tz'] ?? '', 0, 64);
$dpr  = (float)($payload['dpr'] ?? 1);
$vp_w = (int)($payload['vp']['w'] ?? 0);
$vp_h = (int)($payload['vp']['h'] ?? 0);
$scr_w= (int)($payload['scr']['w'] ?? 0);
$scr_h= (int)($payload['scr']['h'] ?? 0);
$theme= substr($payload['theme'] ?? '', 0, 16);
$nt   = substr($payload['navType'] ?? '', 0, 16);
$perf = $payload['perf'] ?? null;

$ip   = get_ip();
$ua   = get_ua();
$cc   = get_cf_country();
$cfr  = $_SERVER['HTTP_CF_RAY'] ?? null;

$ttfb = $perf['ttfb'] ?? null;
$domi = $perf['domInteractive'] ?? null;
$dcl  = $perf['domContentLoaded'] ?? null;
$load = $perf['load'] ?? null;

$type = in_array(($payload['type'] ?? 'visit'), ['visit','virtual'], true)
  ? $payload['type']
  : 'visit';

// Multi-user: derive logical resume owner from path
$user = detect_user_from_path($path);

// Simple per-IP rate limit (≤ 1 hit / 300 ms)
$now  = (int)(microtime(true)*1000);
$st   = db()->prepare("SELECT ts FROM rate WHERE ip=?");
$st->execute([$ip]);
$prev = (int)($st->fetchColumn() ?: 0);
if ($now - $prev < 300) {
  echo '{"ok":true}';
  exit;
}

db()->prepare("INSERT INTO rate(ip,ts) VALUES(?,?) ON CONFLICT(ip) DO UPDATE SET ts=excluded.ts")
  ->execute([$ip, $now]);

// Insert into visits (with `user`)
$sql = "
  INSERT INTO visits (
    ts,ip,ua,country,cf_ray,
    url,path,ref,utm,
    lang,languages,tz,
    dpr,vp_w,vp_h,scr_w,scr_h,
    theme,nav_type,
    ttfb,dom_inter,dcl,load,
    type,user
  ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
";

db()->prepare($sql)->execute([
  $ts,$ip,$ua,$cc,$cfr,
  $url,$path,$ref,$utm,
  $lang,$lgs,$tz,
  $dpr,$vp_w,$vp_h,$scr_w,$scr_h,
  $theme,$nt,
  $ttfb,$domi,$dcl,$load,
  $type,$user,
]);

echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
