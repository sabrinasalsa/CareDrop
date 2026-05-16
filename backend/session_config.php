<?php
/**
 * CareDrop – session_config.php
 * Konfigurasi session: timeout 2 jam, regenerate ID
 */
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 7200);   // 2 jam
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Session timeout — paksa logout setelah 2 jam tidak aktif
define('SESSION_TIMEOUT', 7200);
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: ' . (str_contains($_SERVER['PHP_SELF'], 'admin/') ? '../index.php' : 'index.php') . '?timeout=1');
        exit;
    }
}
$_SESSION['last_activity'] = time();

// CSRF token — generate jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token(): string {
    return $_SESSION['csrf_token'] ?? '';
}

function csrf_verify(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}
