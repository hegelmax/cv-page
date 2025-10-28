<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

// If config already exists, redirect to login
if (is_file(__DIR__ . '/config.php')) {
  header('Location: /analytics/login.php');
  exit;
}

$error = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf'], $csrf)) {
    $error = 'Security token invalid. Please refresh and try again.';
  } else {
    $login = trim((string)($_POST['login'] ?? ''));
    $pass1 = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password2'] ?? '');

    if ($login === '' || $pass1 === '' || $pass2 === '') {
      $error = 'All fields are required.';
    } elseif ($pass1 !== $pass2) {
      $error = 'Passwords do not match.';
    } elseif (strlen($login) < 3 || strlen($pass1) < 6) {
      $error = 'Use a longer login (≥3) and password (≥6).';
    } else {
      $hash = password_hash($pass1, PASSWORD_DEFAULT);
      $cfg  = "<?php\ndeclare(strict_types=1);\n"
            . "/** Auto-generated on ".date('c')." */\n"
            . "const ANALYTICS_LOGIN = ".var_export($login, true).";\n"
            . "const ANALYTICS_PASS_HASH = ".var_export($hash, true).";\n";
      $target = __DIR__ . '/config.php';
      $tmp = $target . '.tmp';

      if (@file_put_contents($tmp, $cfg, LOCK_EX) === false) {
        $error = 'Failed to write config. Check /analytics folder permissions.';
      } else {
        if (!@rename($tmp, $target)) {
          @unlink($tmp);
          $error = 'Failed to move config into place. Check permissions.';
        } else {
          @chmod($target, 0660);
          $ok = true;
          // redirect back to analytics index
          header('Location: /analytics/');
          exit;
        }
      }
    }
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Analytics — First-time setup</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/modern-normalize/2.0.0/modern-normalize.min.css"/>
  <style>
    :root{color-scheme:dark}
    body{background:#0b0f14;color:#e5e7eb;font:14px/1.45 system-ui,Segoe UI,Roboto,Arial;margin:0}
    .wrap{min-height:100dvh;display:grid;place-items:center}
    .card{background:#0f1520;border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:22px;min-width:340px;box-shadow:0 14px 34px rgba(0,0,0,.6)}
    h1{margin:0 0 12px;font-size:18px}
    label{display:block;margin:10px 0 6px;color:#cfe3ff}
    input[type=text],input[type=password]{width:100%;padding:10px;border-radius:10px;background:#0c1118;border:1px solid rgba(255,255,255,.15);color:#e5e7eb}
    .row{display:flex;gap:10px;align-items:center;justify-content:space-between;margin-top:14px}
    button{background:#7cc0ff;color:#0a0e14;border:0;border-radius:10px;padding:10px 12px;cursor:pointer}
    .muted{color:#9aa4b2}
    .error{color:#ffb4b4;margin-top:10px}
    .ok{color:#bdfc9d;margin-top:10px}
    a{color:#9ed0ff}
  </style>
</head>
<body>
<div class="wrap">
  <form method="post" class="card" autocomplete="off">
    <h1>Analytics — first-time setup</h1>
    <p class="muted">Create administrator credentials for the analytics dashboard.</p>

    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div><?php endif; ?>
    <?php if ($ok): ?>
      <div class="ok">Config saved. You can now <a href="/analytics/login.php">sign in</a>.</div>
    <?php endif; ?>

    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES) ?>"/>

    <label>Admin login</label>
    <input type="text" name="login" required placeholder="admin" minlength="3">

    <label>Password</label>
    <input type="password" name="password" required placeholder="••••••••" minlength="6">

    <label>Confirm password</label>
    <input type="password" name="password2" required placeholder="••••••••" minlength="6">

    <div class="row">
      <a class="muted" href="/">← Back to site</a>
      <button type="submit">Save config</button>
    </div>
  </form>
</div>
</body>
</html>
