<?php
// admin/logout.php - Proses Keluar dari Sesi Administrator MAKASSAR AMAN
require_once __DIR__ . '/../includes/functions.php';

// Hapus seluruh variabel sesi
$_SESSION = [];

// Hapus cookie sesi jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan sesi
session_destroy();

// Alihkan kembali ke halaman login
header("Location: login.php?msg=logout");
exit;

