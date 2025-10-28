<?php
declare(strict_types=1);

// secure cookie flags for sessions
ini_set('session.cookie_httponly','1');
ini_set('session.use_strict_mode','1');
ini_set('session.cookie_samesite','Lax');

session_name('cv_analytics');
session_start();

// If config is missing, route user to setup wizard (except when already there)
if (!is_file(__DIR__ . '/config.php')) {
  $req = $_SERVER['REQUEST_URI'] ?? '';
  if (stripos($req, '/analytics/setup.php') === false) {
    header('Location: /analytics/setup.php');
    exit;
  }
} else {
  require_once __DIR__ . '/config.php';
}

// CSRF token
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

// helper: require login
function ensure_logged_in(): void {
  if (empty($_SESSION['auth_ok'])) {
    header('Location: /analytics/login.php');
    exit;
  }
}
