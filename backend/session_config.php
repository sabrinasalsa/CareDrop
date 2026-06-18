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


function json_error(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}
