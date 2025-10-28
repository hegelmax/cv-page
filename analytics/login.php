<?php
declare(strict_types=1);
require __DIR__.'/auth.php';

// If config not created yet — go to setup
if (!is_file(__DIR__.'/config.php')) { header('Location: /analytics/setup.php'); exit; }
require __DIR__.'/config.php';

// simple rate limit: ≤10 attempts per 15 minutes per IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (!isset($_SESSION['tries'])) $_SESSION['tries'] = [];
$_SESSION['tries'] = array_filter(
  $_SESSION['tries'],
  fn($t) => $t > (time() - 15*60)
);
$tooMany = (count($_SESSION['tries']) >= 10);

$error = '';
// already signed in? go to dashboard
if (analytics_is_auth()) { header('Location: /analytics/'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$tooMany) {
  $csrf = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf'], $csrf)) {
    $error = 'Security token invalid. Please retry.';
  } else {
    $login = trim((string)($_POST['login'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');
    // record login attempt
    $_SESSION['tries'][] = time();

    if (hash_equals(ANALYTICS_LOGIN, $login) && password_verify($pass, ANALYTICS_PASS_HASH)) {
      // success: regenerate session, clear counter, login
      session_regenerate_id(true);
      // success: clear the counter and log in
      $_SESSION['tries'] = [];
      $_SESSION['auth'] = true;
      // optional session signature (IP/UA)
      $_SESSION['sig'] = hash('sha256', ($ip.'|'.($_SERVER['HTTP_USER_AGENT'] ?? '')));
      // ignore admin's own visits in analytics
      setcookie('an_ignore', '1', time()+31536000, '/', '', !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', true);
      
      $to = $_GET['redirect'] ?? '/analytics/';
      header('Location: '.$to);
      exit;
    } else {
      $error = 'Invalid credentials.';
    }
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Analytics — Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/modern-normalize/2.0.0/modern-normalize.min.css"/>
  <style>
    :root{color-scheme:dark}
    body{background:#0b0f14;color:#e5e7eb;font:14px/1.45 system-ui,Segoe UI,Roboto,Arial;margin:0;padding:0}
    .wrap{min-height:100dvh;display:grid;place-items:center}
    .card{background:#0f1520;border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:22px;min-width:320px;box-shadow:0 14px 34px rgba(0,0,0,.6)}
    h1{margin:0 0 12px;font-size:18px}
    label{display:block;margin:10px 0 6px;color:#cfe3ff}
    input[type=text],input[type=password]{
      width:100%;padding:10px;border-radius:10px;background:#0c1118;border:1px solid rgba(255,255,255,.15);color:#e5e7eb
    }
    .row{display:flex;gap:10px;align-items:center;justify-content:space-between;margin-top:14px}
    button{background:#7cc0ff;color:#0a0e14;border:0;border-radius:10px;padding:10px 12px;cursor:pointer}
    .muted{color:#9aa4b2}
    .error{color:#ffb4b4;margin-top:10px}
    .lock{font-size:12px;color:#ffcc99}
  </style>
</head>
<body>
<div class="wrap">
  <form method="post" class="card" autocomplete="off">
    <h1>Analytics login</h1>
    <?php if ($tooMany): ?>
      <div class="lock">Too many attempts. Try again later.</div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES) ?>"/>

    <label>Login</label>
    <input type="text" name="login" required autofocus placeholder="admin" <?= $tooMany?'disabled':'' ?>>

    <label>Password</label>
    <input type="password" name="password" required placeholder="••••••••" <?= $tooMany?'disabled':'' ?>>

    <div class="row">
      <a class="muted" href="/">← Back to site</a>
      <button type="submit" <?= $tooMany?'disabled':'' ?>>Sign in</button>
    </div>
  </form>
</div>
</body>
</html>
