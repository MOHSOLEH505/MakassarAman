<?php
// includes/navbar.php - Navigasi Publik MAKASSAR AMAN
if (!isset($active_page)) {
    $active_page = 'beranda';
}
if (!isset($base_url)) {
    $base_url = "./";
}
?>
<header class="navbar" id="mainNavbar">
    <div class="container nav-container">
        <!-- Logo Identitas Aplikasi -->
        <a href="<?= $base_url; ?>index.php" class="nav-brand">
            <div class="brand-logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L3 7V12C3 17.52 6.84 22.74 12 24C17.16 22.74 21 17.52 21 12V7L12 2Z" fill="currentColor" fill-opacity="0.2"/>
                    <path d="M12 4.18L19 8.07V12C19 16.36 16.03 20.47 12 21.64C7.97 20.47 5 16.36 5 12V8.07L12 4.18ZM12 2L3 7V12C3 17.52 6.84 22.74 12 24C17.16 22.74 21 17.52 21 12V7L12 2Z" fill="currentColor"/>
                    <path d="M10 15.5L6.5 12L7.91 10.59L10 12.67L16.09 6.58L17.5 8L10 15.5Z" fill="#ffffff"/>
                </svg>
            </div>
            <div class="brand-text">
                <span class="brand-title">MAKASSAR<span>AMAN</span></span>
                <span class="brand-subtitle">Layanan Pengaduan Infrastruktur</span>
            </div>
        </a>

        <!-- Tombol Hamburger Mobile -->
        <button class="nav-toggle" id="navToggle" aria-label="Buka navigasi menu" aria-expanded="false">
            <span class="hamburger-bar"></span>
            <span class="hamburger-bar"></span>
            <span class="hamburger-bar"></span>
        </button>

        <!-- Menu Navigasi Utama -->
        <nav class="nav-menu" id="navMenu">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?= $base_url; ?>index.php" class="nav-link <?= ($active_page === 'beranda') ? 'active' : ''; ?>">Beranda</a>
                </li>
                <li class="nav-item">
                    <a href="<?= $base_url; ?>peta.php" class="nav-link <?= ($active_page === 'peta') ? 'active' : ''; ?>">Peta Laporan</a>
                </li>
                <li class="nav-item">
                    <a href="<?= $base_url; ?>buat-laporan.php" class="nav-link <?= ($active_page === 'lapor' || $active_page === 'buat-laporan') ? 'active' : ''; ?>">Buat Laporan</a>
                </li>
                <li class="nav-item">
                    <a href="<?= $base_url; ?>cek-status.php" class="nav-link <?= ($active_page === 'status' || $active_page === 'cek-status') ? 'active' : ''; ?>">Cek Status</a>
                </li>
                <li class="nav-item">
                    <a href="<?= $base_url; ?>statistik.php" class="nav-link <?= ($active_page === 'statistik') ? 'active' : ''; ?>">Statistik</a>
                </li>
            </ul>

            <!-- Tombol Aksi Login Admin -->
            <div class="nav-cta">
                <a href="<?= $base_url; ?>admin/login.php" class="btn btn-outline-admin">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Login Admin</span>
                </a>
            </div>
        </nav>
    </div>
</header>

