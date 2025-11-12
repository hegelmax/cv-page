<?php
// Resume Studio — API endpoints (PHP)
// Config
$BASE_DATA_DIR = __DIR__ . '/../data'; // adjust if needed
$BACKUP_KEEP   = 5;                    // backups per file

@mkdir($BASE_DATA_DIR, 0775, true);

// Helpers
function ok($payload = []) { header('Content-Type: application/json'); echo json_encode(['ok' => true] + $payload); exit; }
function fail($msg, $code = 400) { http_response_code($code); header('Content-Type: application/json'); echo json_encode(['ok' => false, 'error' => $msg]); exit; }
function sanitize_user($u) { return preg_match('~^[A-Za-z0-9_\-.]+$~',$u) ? $u : null; }
function user_dir($user){ global $BASE_DATA_DIR; $u = sanitize_user($user); if(!$u) return null; $d = rtrim($BASE_DATA_DIR,'/').'/'.$u; @mkdir($d,0775,true); return $d; }
function safe_file($user,$file){ $d=user_dir($user); if(!$d) return null; if(!preg_match('~^[A-Za-z0-9_\-.]+\.json$~',$file)) return null; return $d.'/'.$file; }
function backup_dir($user){ $d = user_dir($user).'/_backups'; @mkdir($d,0775,true); return $d; }
function list_backups($user,$file){ $list = glob(backup_dir($user).'/'.$file.'.*.json') ?: []; rsort($list); return array_map('basename',$list); }
function make_backup($user,$file){ global $BACKUP_KEEP; $src = safe_file($user,$file); if(!$src || !is_file($src)) return; $ts=date('Ymd-His'); $dst=backup_dir($user).'/'.$file.'.'.$ts.'.json'; @copy($src,$dst); $all=glob(backup_dir($user).'/'.$file.'.*.json')?:[]; rsort($all); foreach(array_slice($all,$BACKUP_KEEP) as $p) @unlink($p); }

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$user   = $_GET['user'] ?? $_POST['user'] ?? '';
if(!$action) fail('No action');
if(!user_dir($user)) fail('Invalid user');

switch($action){
  case 'list': {
    $files = glob(user_dir($user).'/*.json') ?: [];
    $files = array_map('basename',$files);
    sort($files);
    ok(['files'=>$files]);
  }
  case 'load': {
    $file = $_GET['file'] ?? ''; $p = safe_file($user,$file);
    if(!$p || !is_file($p)) fail('File not found',404);
    ok(['file'=>$file,'content'=>file_get_contents($p),'backups'=>list_backups($user,$file)]);
  }
  case 'save': {
    $file = $_POST['file'] ?? ''; $p = safe_file($user,$file);
    if(!$p) fail('Bad filename');
    $content = $_POST['content'] ?? '';
    $dec = json_decode($content,true);
    if($dec===null && json_last_error()!==JSON_ERROR_NONE) fail('Invalid JSON: '.json_last_error_msg());
    $pretty = json_encode($dec, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    if(file_exists($p)) make_backup($user,$file);
    if(@file_put_contents($p,$pretty)===false) fail('Write failed');
    ok(['saved'=>$file,'backups'=>list_backups($user,$file)]);
  }
  case 'new': {
    $file = $_POST['file'] ?? ''; $p = safe_file($user,$file);
    if(!$p) fail('Bad filename'); if(file_exists($p)) fail('File exists');
    $template = $_POST['template'] ?? '';
    $data=[];
    if($template==='empty'){
      $data = [
        'version'=>'1.0.0','name'=>'','title'=>'','contact'=>['email'=>'','phone'=>'','location'=>''],
        'summary'=>'','experience'=>[],'education'=>[],'skills'=>['list'=>[]],'projects'=>[],'awards'=>[]
      ];
    } elseif($template){
      $tp = safe_file($user,$template); if($tp && is_file($tp)) $data = json_decode(file_get_contents($tp),true) ?: [];
    }
    $pretty = json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    if(@file_put_contents($p,$pretty)===false) fail('Create failed');
    ok(['created'=>basename($p)]);
  }
  case 'copy': {
    $from = $_POST['from'] ?? ''; $to = $_POST['to'] ?? '';
    $src = safe_file($user,$from); $dst = safe_file($user,$to);
    if(!$src || !is_file($src)) fail('Source missing'); if(!$dst) fail('Bad destination'); if(file_exists($dst)) fail('Destination exists');
    if(!copy($src,$dst)) fail('Copy failed');
    ok(['copied'=>[$from,basename($dst)]]);
  }
  case 'delete': {
    $file = $_POST['file'] ?? ''; $p = safe_file($user,$file);
    if(!$p || !file_exists($p)) fail('File not found');
    if(!unlink($p)) fail('Delete failed');
    ok(['deleted'=>$file]);
  }
  case 'rename': {
    $from = $_POST['from'] ?? ''; $to = $_POST['to'] ?? '';
    $src = safe_file($user,$from); $dst = safe_file($user,$to);
    if(!$src || !is_file($src)) fail('Source missing'); if(!$dst) fail('Bad destination'); if(file_exists($dst)) fail('Destination exists');
    if(!rename($src,$dst)) fail('Rename failed');
    ok(['renamed'=>[$from,basename($dst)]]);
  }
  case 'backups': { $file=$_GET['file']??''; ok(['backups'=>list_backups($user,$file)]); }
  case 'restore': {
    $file = $_POST['file'] ?? ''; $bak = $_POST['backup'] ?? '';
    $src = backup_dir($user).'/'.basename($bak); $dst = safe_file($user,$file);
    if(!is_file($src) || !$dst) fail('Backup not found');
    if(file_exists($dst)) make_backup($user,$file);
    if(!copy($src,$dst)) fail('Restore failed');
    ok(['restored'=>basename($bak),'content'=>file_get_contents($dst),'backups'=>list_backups($user,$file)]);
  }
  default: fail('Unknown action');
}