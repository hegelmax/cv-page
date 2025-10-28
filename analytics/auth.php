<?php
declare(strict_types=1);

// secure cookie flags for sessions
ini_set('session.cookie_httponly','1');
ini_set('session.use_strict_mode','1');
ini_set('session.cookie_samesite','Lax');

// If the site is on HTTPS:
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
  ini_set('session.cookie_secure', '1');
}

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

// Check "login successful?"
function analytics_is_auth(): bool {
  return !empty($_SESSION['auth']) && $_SESSION['auth'] === true;
}

// Require authorization
function analytics_require_auth(): void {
  if (!analytics_is_auth()) {
    $redir = urlencode($_SERVER['REQUEST_URI'] ?? '/analytics/');
    header('Location: /analytics/login.php?redirect='.$redir);
    exit;
  }
}