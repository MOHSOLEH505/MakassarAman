<?php
// index.php - Halaman Utama (Beranda) MAKASSAR AMAN
$page_title = "MAKASSAR AMAN - Sistem Informasi Pemetaan & Pelaporan Infrastruktur Kota Makassar";
$active_page = "beranda";
$base_url = "./";

// Memuat konfigurasi database dan fungsi bantuan jika diperlukan
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Data statistik aktual dari database MySQL
$stat_total = 0;
$stat_menunggu = 0;
$stat_proses = 0;
$stat_selesai = 0;
$recent_reports = [];

try {
    // Ambil rekap total status
    $stmtSum = $pdo->query("SELECT 
        COUNT(*) AS total,
        COALESCE(SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END), 0) AS menunggu,
        COALESCE(SUM(CASE WHEN status = 'Diproses' THEN 1 ELSE 0 END), 0) AS proses,
        COALESCE(SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END), 0) AS selesai
    FROM laporan");
    $rowSum = $stmtSum->fetch();
    if ($rowSum) {
        $stat_total    = (int)$rowSum['total'];
        $stat_menunggu = (int)$rowSum['menunggu'];
        $stat_proses   = (int)$rowSum['proses'];
        $stat_selesai  = (int)$rowSum['selesai'];
    }

    // Ambil maksimal 3 laporan terbaru untuk ditampilkan di beranda
    $stmtRecent = $pdo->query("SELECT l.id_laporan, l.kode_laporan, l.id_kategori, l.deskripsi, 
                                      l.alamat, l.kecamatan, l.status, l.tanggal_laporan, k.nama_kategori
                               FROM laporan l
                               JOIN kategori k ON l.id_kategori = k.id_kategori
                               ORDER BY l.tanggal_laporan DESC
                               LIMIT 3");
    $recent_reports = $stmtRecent->fetchAll();
} catch (PDOException $e) {
    error_log("Error in index.php query: " . $e->getMessage());
}

// Ambil kategori dari database jika tabel kategori sudah tersedia
$kategori_list = [];
try {
    $stmtKat = $pdo->query("SELECT id_kategori, nama_kategori, deskripsi FROM kategori ORDER BY id_kategori ASC");
    $kategori_list = $stmtKat->fetchAll();
} catch (PDOException $e) {
    // Fallback jika belum siap
    $kategori_list = [];
}

// Komponen Header & Navbar
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- ====================================================================
     1. Hero Section
     ==================================================================== -->
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-content">
            <div class="hero-tag">
                <span class="tag-dot"></span>
                <span>Layanan Publik E-Government Kota Makassar</span>
            </div>
            <h1 class="hero-title">
                Bersama Wujudkan <span>Makassar Aman</span> & Infrastruktur Nyaman
            </h1>
            <p class="hero-desc">
                Sistem informasi pemetaan dan pelaporan terpadu untuk menyampaikan permasalahan jalan rusak, lampu penerangan padam, genangan air, hingga drainase tersumbat langsung ke dinas terkait secara transparan dan terdata.
            </p>
            <div class="hero-actions">
                <a href="buat-laporan.php" class="btn btn-primary btn-lg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Buat Laporan Sekarang</span>
                </a>
                <a href="peta.php" class="btn btn-outline-primary btn-lg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                        <line x1="8" y1="2" x2="8" y2="18"></line>
                        <line x1="16" y1="6" x2="16" y2="22"></line>
                    </svg>
                    <span>Lihat Peta Laporan</span>
                </a>
            </div>
            <div class="hero-highlights">
                <div class="highlight-item">
                    <svg class="highlight-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Tanpa Perlu Login Warga</span>
                </div>
                <div class="highlight-item">
                    <svg class="highlight-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Pemetaan Spasial Geografis</span>
                </div>
                <div class="highlight-item">
                    <svg class="highlight-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Pantau Status Real-Time</span>
                </div>
            </div>
        </div>

        <!-- Visual Pendukung Infrastruktur Kota Makassar -->
        <div class="hero-visual">
            <div class="city-card-mockup">
                <div class="mockup-header">
                    <div class="mockup-city">KOTA MAKASSAR</div>
                    <div class="mockup-badge">GIS Pemetaan Aktif</div>
                </div>
                <div class="mockup-map-preview">
                    <div class="map-grid-bg"></div>
                    <div class="map-marker-pulse">
                        <div class="pulse-circle">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                        <span>Pusat Koordinat Makassar (-5.1477, 119.4327)</span>
                    </div>
                </div>
                <div class="mockup-footer-info">
                    <span>Wilayah Layanan: <strong>15 Kecamatan</strong></span>
                    <span>Status Sistem: <strong style="color: var(--status-selesai);">Online</strong></span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====================================================================
     2. Ringkasan Statistik
     ==================================================================== -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <!-- Total Laporan -->
            <div class="stat-card total">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Total Laporan Masuk</span>
                    <span class="stat-number"><?= (int)$stat_total; ?></span>
                </div>
            </div>

            <!-- Menunggu -->
            <div class="stat-card menunggu">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Laporan Menunggu</span>
                    <span class="stat-number"><?= (int)$stat_menunggu; ?></span>
                </div>
            </div>

            <!-- Diproses -->
            <div class="stat-card proses">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <polyline points="1 20 1 14 7 14"></polyline>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Sedang Diproses</span>
                    <span class="stat-number"><?= (int)$stat_proses; ?></span>
                </div>
            </div>

            <!-- Selesai -->
            <div class="stat-card selesai">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Laporan Selesai</span>
                    <span class="stat-number"><?= (int)$stat_selesai; ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====================================================================
     3. Kategori Laporan
     ==================================================================== -->
<section class="section section-alt" id="kategori">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Fokus Pelayanan</span>
            <h2 class="section-title">Kategori Permasalahan Infrastruktur</h2>
            <p class="section-subtitle">
                Pemerintah Kota Makassar menerima dan menindaklanjuti 4 kategori utama kerusakan fasilitas umum demi kenyamanan mobilitas warga.
            </p>
        </div>

        <div class="categories-grid">
            <!-- 1. Jalan Rusak -->
            <div class="category-card">
                <div class="category-icon-box">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="18" rx="2"></rect>
                        <line x1="8" y1="8" x2="8" y2="8.01"></line>
                        <line x1="16" y1="8" x2="16" y2="8.01"></line>
                        <line x1="12" y1="12" x2="12" y2="12.01"></line>
                        <line x1="8" y1="16" x2="8" y2="16.01"></line>
                        <line x1="16" y1="16" x2="16" y2="16.01"></line>
                    </svg>
                </div>
                <h3 class="category-name">Jalan Rusak</h3>
                <p class="category-desc">
                    Jalanan berlubang, aspal retak, amblas, atau bergelombang yang berpotensi membahayakan keselamatan pengguna jalan dan pengendara.
                </p>
                <a href="buat-laporan.php?kategori=1" class="category-link">
                    <span>Laporkan Masalah Ini</span> &rarr;
                </a>
            </div>

            <!-- 2. Lampu Jalan Mati -->
            <div class="category-card">
                <div class="category-icon-box">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="9" y1="18" x2="15" y2="18"></line>
                        <line x1="10" y1="22" x2="14" y2="22"></line>
                        <path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"></path>
                    </svg>
                </div>
                <h3 class="category-name">Lampu Jalan Mati</h3>
                <p class="category-desc">
                    Lampu Penerangan Jalan Umum (PJU) yang padam total, berkedip, rusak, atau kabel penerangan yang membahayakan di malam hari.
                </p>
                <a href="buat-laporan.php?kategori=2" class="category-link">
                    <span>Laporkan Masalah Ini</span> &rarr;
                </a>
            </div>

            <!-- 3. Genangan Air -->
            <div class="category-card">
                <div class="category-icon-box">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                    </svg>
                </div>
                <h3 class="category-name">Genangan Air</h3>
                <p class="category-desc">
                    Air meluap dan menggenangi ruas jalan atau pemukiman warga saat dan setelah hujan akibat kontur atau daya serap air yang buruk.
                </p>
                <a href="buat-laporan.php?kategori=3" class="category-link">
                    <span>Laporkan Masalah Ini</span> &rarr;
                </a>
            </div>

            <!-- 4. Saluran Tersumbat -->
            <div class="category-card">
                <div class="category-icon-box">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="21" x2="4" y2="14"></line>
                        <line x1="4" y1="10" x2="4" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12" y2="3"></line>
                        <line x1="20" y1="21" x2="20" y2="16"></line>
                        <line x1="20" y1="12" x2="20" y2="3"></line>
                        <line x1="1" y1="14" x2="7" y2="14"></line>
                        <line x1="9" y1="8" x2="15" y2="8"></line>
                        <line x1="17" y1="16" x2="23" y2="16"></line>
                    </svg>
                </div>
                <h3 class="category-name">Saluran Tersumbat</h3>
                <p class="category-desc">
                    Drainase, gorong-gorong, atau got yang tertutup tumpukan sampah, sedimentasi lumpur tebal, atau penyumbatan konstruksi fisik.
                </p>
                <a href="buat-laporan.php?kategori=4" class="category-link">
                    <span>Laporkan Masalah Ini</span> &rarr;
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ====================================================================
     4. Laporan Terbaru (Komponen dengan Empty State Informatif)
     ==================================================================== -->
<section class="section" id="laporan-terbaru">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Aktivitas Terkini</span>
            <h2 class="section-title">Laporan Infrastruktur Terbaru</h2>
            <p class="section-subtitle">
                Pantau pengaduan permasalahan fasilitas publik yang baru saja dikirimkan oleh warga Kota Makassar.
            </p>
        </div>

        <?php if (!empty($recent_reports)): ?>
            <!-- Grid Daftar Laporan Aktual Terbaru -->
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
                <?php foreach ($recent_reports as $rep): ?>
                    <?php
                    $rep_badge = 'badge-status-menunggu';
                    if ($rep['status'] === 'Diproses') $rep_badge = 'badge-status-proses';
                    elseif ($rep['status'] === 'Selesai') $rep_badge = 'badge-status-selesai';
                    ?>
                    <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 22px; box-shadow: var(--shadow-xs); display: flex; flex-direction: column;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; gap: 8px;">
                            <span style="font-size: 11.5px; font-weight: 700; color: var(--primary); text-transform: uppercase; background: var(--primary-light); padding: 2px 8px; border-radius: var(--radius-full);">
                                <?= htmlspecialchars($rep['nama_kategori']); ?>
                            </span>
                            <span class="badge-status <?= $rep_badge; ?>" style="font-size: 11px;">
                                <?= htmlspecialchars($rep['status']); ?>
                            </span>
                        </div>
                        <div style="font-family: monospace; font-size: 13.5px; font-weight: 700; color: var(--navy); margin-bottom: 6px;">
                            <?= htmlspecialchars($rep['kode_laporan']); ?>
                        </div>
                        <div style="font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 8px;">
                            <?= htmlspecialchars($rep['alamat']); ?>, Kec. <?= htmlspecialchars($rep['kecamatan']); ?>
                        </div>
                        <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 16px; flex-grow: 1;">
                            <?= htmlspecialchars(mb_strimwidth($rep['deskripsi'], 0, 110, '...')); ?>
                        </p>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 12px; border-top: 1px solid var(--border-color); font-size: 12px;">
                            <span style="color: var(--text-light);"><?= formatTanggalIndonesia($rep['tanggal_laporan']); ?></span>
                            <a href="cek-status.php?kode=<?= urlencode($rep['kode_laporan']); ?>" style="color: var(--primary); font-weight: 600;">
                                Cek Progres &rarr;
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 28px;">
                <a href="peta.php" class="btn btn-outline-primary btn-sm">
                    Lihat Seluruh Laporan di Peta &rarr;
                </a>
            </div>
        <?php else: ?>
            <!-- Tampilan Empty State Informatif (Database laporan masih kosong) -->
            <div class="empty-state-box">
                <div class="empty-state-icon">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                        <path d="M7 15h0M2 9.5h20"></path>
                    </svg>
                </div>
                <h3 class="empty-state-title">Belum Ada Laporan Masuk</h3>
                <p class="empty-state-text">
                    Saat ini belum ada pengaduan infrastruktur yang tercatat di dalam sistem. Jadilah warga pertama yang berpartisipasi melaporkan kendala di sekitar Anda!
                </p>
                <a href="buat-laporan.php" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Mulai Buat Laporan Pertama</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ====================================================================
     5. Ajakan Membuat Laporan (Call To Action / CTA)
     ==================================================================== -->
<section class="section" style="padding-top: 0;">
    <div class="container">
        <div class="cta-banner">
            <div class="cta-grid">
                <div class="cta-content">
                    <h2>Lihat Fasilitas Rusak di Makassar? Jangan Didiamkan!</h2>
                    <p>
                        Partisipasi aktif Anda membantu Pemerintah Kota Makassar mendeteksi titik kerusakan lebih cepat dan memprioritaskan perbaikan demi keselamatan bersama.
                    </p>
                    <a href="buat-laporan.php" class="btn btn-primary btn-lg" style="background-color: #38bdf8; color: #0f172a; font-weight: 700;">
                        Kirim Laporan Kerusakan Sekarang &rarr;
                    </a>
                </div>

                <div class="cta-steps">
                    <div class="cta-step-item">
                        <div class="cta-step-num">1</div>
                        <div class="cta-step-text">
                            <h4>Foto & Tentukan Lokasi</h4>
                            <p>Ambil foto kondisi kerusakan dan tandai titik lokasi pada peta.</p>
                        </div>
                    </div>
                    <div class="cta-step-item">
                        <div class="cta-step-num">2</div>
                        <div class="cta-step-text">
                            <h4>Kirim Pengaduan</h4>
                            <p>Isi deskripsi singkat lalu dapatkan kode tiket unik pelaporan Anda.</p>
                        </div>
                    </div>
                    <div class="cta-step-item">
                        <div class="cta-step-num">3</div>
                        <div class="cta-step-text">
                            <h4>Pantau Penyelesaian</h4>
                            <p>Cek perkembangan perbaikan langsung dari status verifikasi admin.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Komponen Footer
require_once __DIR__ . '/includes/footer.php';
?>
