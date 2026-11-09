<?php
// cek-status.php - Halaman Cek Status Laporan Infrastruktur Kota Makassar
$page_title = "Cek Status Laporan - MAKASSAR AMAN";
$active_page = "status";
$base_url = "./";

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Tangkap input kode laporan dari parameter URL (GET)
$search_code  = isset($_GET['kode']) ? trim($_GET['kode']) : '';
$has_searched = ($search_code !== '');

$laporan = null;
$riwayat_list = [];
$error_message = '';
$foto_url = null;

if ($has_searched) {
    // Validasi format minimal kode laporan
    if (mb_strlen($search_code) < 4 || mb_strlen($search_code) > 50) {
        $error_message = "Format kode laporan tidak valid. Kode laporan umumnya berformat MKS-YYYYMMDD-XXXX.";
    } else {
        try {
            // Cari data laporan dengan prepared statement (Pencegahan SQL Injection)
            $stmt = $pdo->prepare("
                SELECT l.id_laporan, l.kode_laporan, l.id_kategori, l.deskripsi, l.foto, 
                       l.alamat, l.kecamatan, l.latitude, l.longitude, l.status, l.tanggal_laporan,
                       k.nama_kategori
                FROM laporan l
                JOIN kategori k ON l.id_kategori = k.id_kategori
                WHERE l.kode_laporan = :kode
                LIMIT 1
            ");
            $stmt->execute([':kode' => $search_code]);
            $laporan = $stmt->fetch();

            if ($laporan) {
                // Ambil riwayat status secara kronologis (dari awal hingga terbaru)
                $stmtRiwayat = $pdo->prepare("
                    SELECT r.id_riwayat, r.status, r.tanggapan, r.tanggal_perubahan
                    FROM riwayat_status r
                    WHERE r.id_laporan = :id_laporan
                    ORDER BY r.tanggal_perubahan ASC, r.id_riwayat ASC
                ");
                $stmtRiwayat->execute([':id_laporan' => $laporan['id_laporan']]);
                $riwayat_list = $stmtRiwayat->fetchAll();

                // Periksa ketersediaan file foto
                if (!empty($laporan['foto'])) {
                    $foto_name = $laporan['foto'];
                    $path_assets = __DIR__ . '/assets/uploads/laporan/' . $foto_name;
                    $path_root   = __DIR__ . '/uploads/laporan/' . $foto_name;

                    if (file_exists($path_assets)) {
                        $foto_url = 'assets/uploads/laporan/' . htmlspecialchars($foto_name, ENT_QUOTES, 'UTF-8');
                    } elseif (file_exists($path_root)) {
                        $foto_url = 'uploads/laporan/' . htmlspecialchars($foto_name, ENT_QUOTES, 'UTF-8');
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Error in cek-status query: " . $e->getMessage());
            $error_message = "Terjadi kendala pada sistem saat mencari data laporan. Silakan coba kembali.";
            $laporan = null;
        }
    }
}

// Muat kerangka Header dan Navbar
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Header Halaman Cek Status -->
<section class="status-page-header">
    <div class="container">
        <div class="status-page-title">
            <h1>Cek Status Laporan Infrastruktur</h1>
            <p>Pantau perkembangan dan tindak lanjut penanganan pengaduan fasilitas umum di Kota Makassar secara transparan.</p>
        </div>
    </div>
</section>

<div class="container">
    <div class="status-content-wrapper">

        <!-- Formulir Pencarian Kode Laporan -->
        <div class="status-search-card">
            <form action="cek-status.php" method="GET" class="status-search-form">
                <div class="status-input-wrap">
                    <div class="status-input-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                    <input type="text" name="kode" class="status-search-input" placeholder="Masukkan Kode Laporan (Contoh: MKS-20261109-8472)..." value="<?= htmlspecialchars($search_code); ?>" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary" style="height: 48px; padding: 0 24px; white-space: nowrap;">
                    <span>Cari Laporan</span>
                </button>
            </form>

            <div class="status-search-hints">
                <span>💡 <strong>Tips:</strong> Kode laporan diberikan otomatis sesaat setelah Anda mengirimkan pengaduan.</span>
                <?php if ($has_searched): ?>
                    <a href="cek-status.php" style="color: var(--text-muted); text-decoration: underline;">Reset Pencarian</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($error_message)): ?>
            <!-- Pesan Galat Validasi atau Sistem -->
            <div class="form-alert form-alert-danger" style="margin-bottom: 32px;">
                <div style="font-weight: bold; font-size: 18px; line-height: 1;">&times;</div>
                <div><?= htmlspecialchars($error_message); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($has_searched && $laporan): ?>
            <!-- HASIL PENCARIAN DITEMUKAN -->
            <?php
            $status_class = 'menunggu';
            $status_badge_class = 'badge-status-menunggu';
            if ($laporan['status'] === 'Diproses') {
                $status_class = 'proses';
                $status_badge_class = 'badge-status-proses';
            } elseif ($laporan['status'] === 'Selesai') {
                $status_class = 'selesai';
                $status_badge_class = 'badge-status-selesai';
            }
            ?>
            <div class="status-result-card">
                <!-- Header Kartu Detail -->
                <div class="result-card-header">
                    <div class="result-code-group">
                        <span class="result-ticket-code" id="resultTicketCode"><?= htmlspecialchars($laporan['kode_laporan']); ?></span>
                        <button type="button" class="btn-copy" onclick="copySearchResultCode()" id="btnCopyResult">
                            Salin Kode
                        </button>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="badge-status <?= $status_badge_class; ?>" style="font-size: 13px; padding: 6px 14px;">
                            Status: <?= htmlspecialchars($laporan['status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Isi Informasi Pengaduan -->
                <div class="result-card-body">
                    <div class="result-grid">
                        <!-- Kolom Detail Kiri -->
                        <div>
                            <div class="result-item">
                                <div class="result-label">Kategori Permasalahan</div>
                                <div class="result-value">
                                    <strong style="color: var(--primary); font-size: 16px;">
                                        <?= htmlspecialchars($laporan['nama_kategori']); ?>
                                    </strong>
                                </div>
                            </div>

                            <div class="result-item">
                                <div class="result-label">Waktu Pelaporan</div>
                                <div class="result-value">
                                    <?= formatTanggalIndonesia($laporan['tanggal_laporan']); ?>
                                </div>
                            </div>

                            <div class="result-item">
                                <div class="result-label">Lokasi Kejadian</div>
                                <div class="result-value">
                                    <strong><?= htmlspecialchars($laporan['alamat']); ?></strong><br>
                                    <span style="color: var(--text-muted);">Kecamatan <?= htmlspecialchars($laporan['kecamatan']); ?>, Kota Makassar</span>
                                </div>
                            </div>

                            <div class="result-item">
                                <div class="result-label">Deskripsi Permasalahan</div>
                                <div class="result-value" style="background: var(--bg-subtle); padding: 12px 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                    <?= nl2br(htmlspecialchars($laporan['deskripsi'])); ?>
                                </div>
                            </div>

                            <div style="margin-top: 14px;">
                                <a href="peta.php?q=<?= urlencode($laporan['kode_laporan']); ?>" class="btn btn-sm btn-outline-primary">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                                        <line x1="8" y1="2" x2="8" y2="18"></line>
                                        <line x1="16" y1="6" x2="16" y2="22"></line>
                                    </svg>
                                    <span>Lihat Titik di Peta Laporan</span>
                                </a>
                            </div>
                        </div>

                        <!-- Kolom Foto Bukti Kanan -->
                        <div>
                            <div class="result-label" style="margin-bottom: 8px;">Foto Bukti Permasalahan</div>
                            <?php if ($foto_url): ?>
                                <div class="result-photo-box">
                                    <a href="<?= $foto_url; ?>" target="_blank" title="Klik untuk memperbesar gambar">
                                        <img src="<?= $foto_url; ?>" alt="Bukti <?= htmlspecialchars($laporan['nama_kategori']); ?>" class="result-photo-img">
                                    </a>
                                    <div class="result-photo-caption">
                                        <span>Bukti unggahan warga</span>
                                        <a href="<?= $foto_url; ?>" target="_blank" style="color: var(--primary); font-weight: 600;">Perbesar &rarr;</a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div style="background: var(--bg-subtle); border: 1px dashed var(--border-color); border-radius: var(--radius-md); padding: 36px 16px; text-align: center; color: var(--text-muted); font-size: 13px;">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 8px; opacity: 0.5;">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                    <div>Tidak ada lampiran foto untuk laporan ini.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Timeline Riwayat Status Vertikal -->
                    <div class="timeline-section">
                        <h3 class="timeline-section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <span>Riwayat Progres Penanganan</span>
                        </h3>

                        <?php if (!empty($riwayat_list)): ?>
                            <ul class="timeline-list">
                                <?php foreach ($riwayat_list as $rw): ?>
                                    <?php
                                    $item_class = 'menunggu';
                                    $item_badge = 'badge-status-menunggu';
                                    if ($rw['status'] === 'Diproses') {
                                        $item_class = 'proses';
                                        $item_badge = 'badge-status-proses';
                                    } elseif ($rw['status'] === 'Selesai') {
                                        $item_class = 'selesai';
                                        $item_badge = 'badge-status-selesai';
                                    }
                                    ?>
                                    <li class="timeline-item <?= $item_class; ?>">
                                        <div class="timeline-dot"></div>
                                        <div class="timeline-content">
                                            <div class="timeline-meta">
                                                <span class="badge-status <?= $item_badge; ?> timeline-status-badge">
                                                    Status: <?= htmlspecialchars($rw['status']); ?>
                                                </span>
                                                <span class="timeline-time">
                                                    <?= formatTanggalIndonesia($rw['tanggal_perubahan']); ?>
                                                </span>
                                            </div>
                                            <div class="timeline-note">
                                                <?php if (!empty($rw['tanggapan'])): ?>
                                                    <strong>Tanggapan Petugas:</strong> <?= nl2br(htmlspecialchars($rw['tanggapan'])); ?>
                                                <?php else: ?>
                                                    <em style="color: var(--text-light);">Laporan diperbarui ke status <?= htmlspecialchars($rw['status']); ?> tanpa catatan tambahan.</em>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p style="font-size: 13.5px; color: var(--text-muted);">Belum ada catatan riwayat penanganan untuk laporan ini.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($has_searched && !$laporan): ?>
            <!-- HASIL PENCARIAN TIDAK DITEMUKAN -->
            <div class="status-notfound-box">
                <div class="status-notfound-icon">!</div>
                <h3 style="font-size: 20px; font-weight: 700; color: var(--navy); margin-bottom: 8px;">
                    Laporan Tidak Ditemukan
                </h3>
                <p style="font-size: 14px; color: var(--text-muted); max-width: 460px; margin: 0 auto 20px auto; line-height: 1.6;">
                    Tidak ditemukan data laporan dengan kode <strong style="color: var(--navy); font-family: monospace;"><?= htmlspecialchars($search_code); ?></strong>. Pastikan tidak ada kesalahan pengetikan huruf besar/kecil atau tanda hubung.
                </p>
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <a href="cek-status.php" class="btn btn-outline-primary btn-sm">
                        Coba Masukkan Kode Lain
                    </a>
                    <a href="buat-laporan.php" class="btn btn-primary btn-sm">
                        Buat Laporan Baru
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- PANDUAN AWAL SEBELUM PENCARIAN -->
            <div class="status-guide-box">
                <div class="status-guide-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <h3 class="status-guide-title">Lacak Status Pengaduan Anda</h3>
                <p class="status-guide-desc">
                    Ketik atau tempelkan kode laporan pengaduan Anda pada formulir di atas untuk melihat status verifikasi, tindak lanjut dinas, dan catatan resmi petugas terkait permasalahan yang Anda laporkan.
                </p>
                <div style="display: inline-flex; align-items: center; gap: 10px; background: var(--bg-subtle); padding: 8px 16px; border-radius: var(--radius-full); font-size: 13px; color: var(--text-muted); border: 1px solid var(--border-color);">
                    <span>Belum memiliki laporan?</span>
                    <a href="buat-laporan.php" style="font-weight: 600; color: var(--primary);">Kirim Pengaduan &rarr;</a>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Skrip Salin Kode ke Clipboard -->
<script>
function copySearchResultCode() {
    const codeEl = document.getElementById('resultTicketCode');
    const btn = document.getElementById('btnCopyResult');
    if (codeEl) {
        const text = codeEl.textContent.trim();
        navigator.clipboard.writeText(text).then(function () {
            btn.textContent = 'Tersalin!';
            btn.style.background = '#15803d';
            btn.style.color = '#ffffff';
            setTimeout(function () {
                btn.textContent = 'Salin Kode';
                btn.style.background = '#ffffff';
                btn.style.color = '#15803d';
            }, 2000);
        }).catch(function () {
            prompt('Salin kode laporan berikut:', text);
        });
    }
}
</script>

<?php
// Muat kerangka Footer
require_once __DIR__ . '/includes/footer.php';
?>

