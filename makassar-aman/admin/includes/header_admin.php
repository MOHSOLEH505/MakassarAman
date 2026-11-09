<?php
// admin/includes/header_admin.php - Header Layout Panel Administrator
if (!isset($page_title)) {
    $page_title = "Panel Administrator - MAKASSAR AMAN";
}
$admin_name = isset($_SESSION['admin_nama']) ? $_SESSION['admin_nama'] : 'Petugas Administrator';
$admin_user = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <?php if (!empty($enable_leaflet)): ?>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <?php endif; ?>

    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background-color: var(--bg-page);">
<div class="site-wrapper">

    <!-- Navbar Khusus Administrator -->
    <header class="admin-navbar">
        <div class="container admin-nav-container">
            <div style="display: flex; align-items: center; gap: 14px;">
                <a href="index.php" class="nav-brand" style="color: #ffffff;">
                    <div class="brand-logo" style="width: 38px; height: 38px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L3 7V12C3 17.52 6.84 22.74 12 24C17.16 22.74 21 17.52 21 12V7L12 2Z" fill="currentColor"/>
                            <path d="M10 15.5L6.5 12L7.91 10.59L10 12.67L16.09 6.58L17.5 8L10 15.5Z" fill="#0f172a"/>
                        </svg>
                    </div>
                    <div class="brand-text">
                        <span class="brand-title" style="color: #ffffff;">MAKASSAR<span style="color: #38bdf8;">AMAN</span></span>
                    </div>
                </a>
                <span class="admin-badge-label">Portal Admin</span>
            </div>

            <div class="admin-user-menu">
                <a href="../index.php" class="btn btn-sm btn-outline-admin" style="color: #cbd5e1; border-color: rgba(255,255,255,0.2);">
                    <span>Lihat Web Publik</span>
                </a>
                <div class="admin-user-info">
                    <span class="admin-user-name"><?= htmlspecialchars($admin_name); ?></span>
                    <span class="admin-user-role">@<?= htmlspecialchars($admin_user); ?></span>
                </div>
                <a href="logout.php" class="btn-admin-logout" onclick="return confirm('Apakah Anda yakin ingin keluar dari panel admin?');">
                    Keluar
                </a>
            </div>
        </div>
    </header>

