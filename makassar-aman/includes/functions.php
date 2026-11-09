<?php
// includes/functions.php - Fungsi Bantuan (Helper Functions) MAKASSAR AMAN

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Membersihkan input string untuk mencegah XSS
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Menghasilkan kode laporan acak unik (Contoh: MKS-20261109-8472)
 */
function generateKodeLaporan() {
    $datePart = date('Ymd');
    $randomPart = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
    return "MKS-{$datePart}-{$randomPart}";
}

function generateKodeTiket() {
    return generateKodeLaporan();
}

/**
 * Format tanggal Indonesia ramah pengguna
 */
function formatTanggalIndonesia($datetime) {
    if (!$datetime) return '-';
    $timestamp = strtotime($datetime);
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $tgl = date('d', $timestamp);
    $bln = $bulan[(int)date('m', $timestamp)];
    $thn = date('Y', $timestamp);
    $jam = date('H:i', $timestamp);
    return "{$tgl} {$bln} {$thn}, {$jam} WITA";
}

/**
 * Helper class warna status laporan
 */
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Selesai':
            return 'badge-status-selesai';
        case 'Diproses':
            return 'badge-status-proses';
        case 'Menunggu':
        default:
            return 'badge-status-menunggu';
    }
}

/**
 * Generate CSRF Token untuk keamanan form
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifikasi CSRF Token
 */
function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Cek apakah session admin sedang aktif
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Proteksi halaman admin, alihkan ke login jika belum login
 */
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

