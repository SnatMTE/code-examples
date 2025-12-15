<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

// Secure session settings
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
// Avoid setting cookie domain when HTTP_HOST contains a port (e.g., localhost:8000)
$host = $_SERVER['HTTP_HOST'] ?? '';
$domain = '';
if (!empty($host) && strpos($host, ':') === false) {
    $domain = $host;
}
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $domain,
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF helpers
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token)
{
    return isset($_SESSION['csrf_token']) && isset($token) && hash_equals($_SESSION['csrf_token'], $token);
}

// Simple escaping helper
function e($str)
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// Basic input sanitiser
function sanitize_username($u)
{
    $u = trim($u);
    return preg_replace('/[^A-Za-z0-9_\-\.]/', '', $u);
}

function require_login()
{
    if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
        header('Location: login.php');
        exit;
    }
}

// Regenerate session id on demand (e.g., after login)
function regenerate_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

?>
