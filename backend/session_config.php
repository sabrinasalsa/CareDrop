<?php

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime',   7200);  // 2 jam
    ini_set('session.cookie_httponly',  1);
    ini_set('session.use_strict_mode',  1);
    ini_set('session.cookie_samesite',  'Strict');
    // Aktifkan cookie_secure hanya jika HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

// ── Session timeout: paksa logout setelah 2 jam tidak aktif ──
define('SESSION_TIMEOUT', 7200);
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        // Hapus cookie session
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        $redirect = str_contains($_SERVER['PHP_SELF'] ?? '', 'admin/')
            ? '../index.php'
            : (str_contains($_SERVER['PHP_SELF'] ?? '', 'yayasan/') ? '../index.php' : 'index.php');
        header('Location: ' . $redirect . '?timeout=1');
        exit;
    }
}
$_SESSION['last_activity'] = time();

// ── CSRF token ──
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token(): string {
    return $_SESSION['csrf_token'] ?? '';
}

function csrf_verify(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Pastikan user sudah login, jika tidak redirect ke halaman awal.
 * Opsional: cek role.
 */
function require_login(string $role = ''): void {
    if (!isset($_SESSION['id'])) {
        header('Location: ../index.php');
        exit;
    }
    if ($role !== '' && ($_SESSION['role'] ?? '') !== $role) {
        header('Location: ../index.php');
        exit;
    }
}

/**
 * Kirim JSON error dan exit – digunakan di endpoint AJAX.
 */
function json_error(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}
