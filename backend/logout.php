<?php
/**
 * CareDrop – backend/logout.php
 * Logout: hancurkan session + hapus cookie session
 */
require_once __DIR__ . '/session_config.php';

// Hapus semua variabel session
session_unset();

// Hapus cookie session di browser
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/', '', false, true);
}

// Hancurkan session di server
session_destroy();

header('Location: ../index.php');
exit;
