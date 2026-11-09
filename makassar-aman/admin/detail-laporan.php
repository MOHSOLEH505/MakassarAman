<?php
// admin/detail-laporan.php - Halaman Kelola & Tindak Lanjut Pengaduan Administrator
$page_title = "Kelola Laporan - MAKASSAR AMAN Admin";
$enable_leaflet = true;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Pastikan hanya admin terotentikasi yang dapat mengakses
requireAdminLogin();

$id_laporan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_laporan <= 0) {
    header("Location: index.php");
    exit;
}

$error_msg = '';
$success_msg = '';

// Tangani Pembaruan Status dan Tanggapan Petugas (Metode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verifyCsrfToken($token)) {
        $error_msg = "Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan coba lagi.";
    } else {
        $status_baru = isset($_POST['status']) ? trim($_POST['status']) : '';
        $tanggapan   = isset($_POST['tanggapan']) ? trim($_POST['tanggapan']) : '';

        if (!in_array($status_baru, ['Menunggu', 'Diproses', 'Selesai'], true)) {
            $error_msg = "Silakan pilih status yang valid (Menunggu, Diproses, atau Selesai).";
        } else {
            // Eksekusi pembaruan menggunakan Transaksi Database
            $pdo->beginTransaction();
            try {
                // 1. Perbarui status pada tabel laporan
                $stmtUpdate = $pdo->prepare("UPDATE laporan SET status = :status WHERE id_laporan = :id");
                $stmtUpdate->execute([
                    ':status' => $status_baru,
                    ':id'     => $id_laporan
                ]);

                // 2. Tambahkan catatan ke riwayat_status dengan ID admin yang sedang login
                $stmtInsertLog = $pdo->prepare("
                    INSERT INTO riwayat_status (id_laporan, id_admin, status, tanggapan, tanggal_perubahan)
                    VALUES (:id_lap, :id_adm, :status, :tanggapan, NOW())
                ");
                $stmtInsertLog->execute([
                    ':id_lap'    => $id_laporan,
                    ':id_adm'    => (int)$_SESSION['admin_id'],
                    ':status'    => $status_baru,
                    ':tanggapan' => !empty($tanggapan) ? $tanggapan : 'Status pengaduan diperbarui oleh petugas administrator.'
                ]);

                $pdo->commit();
                $success_msg = "Status laporan berhasil diperbarui menjadi \"" . htmlspecialchars($status_baru) . "\".";
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Error updating report status in admin: " . $e->getMessage());
                $error_msg = "Terjadi gangguan sistem saat memperbarui status laporan.";
            }
        }
    }
}

// Ambil data detail laporan saat ini
$laporan = null;
try {
    $stmtDetail = $pdo->prepare("
        SELECT l.*, k.nama_kategori 
        FROM laporan l
        JOIN kategori k ON l.id_kategori = k.id_kategori
        WHERE l.id_laporan = :id
        LIMIT 1
    ");
    $stmtDetail->execute([':id' => $id_laporan]);
    $laporan = $stmtDetail->fetch();

    if (!$laporan) {
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching report detail: " . $e->getMessage());
    header("Location: index.php");
    exit;
}

// Ambil riwayat penanganan laporan
$riwayat_list = [];
try {
    $stmtRiwayat = $pdo->prepare("
        SELECT r.*, a.nama AS nama_petugas, a.username AS user_petugas
        FROM riwayat_status r
        LEFT JOIN admin a ON r.id_admin = a.id_admin
        WHERE r.id_laporan = :id
        ORDER BY r.tanggal_perubahan DESC, r.id_riwayat DESC
    ");
    $stmtRiwayat->execute([':id' => $id_laporan]);
    $riwayat_list = $stmtRiwayat->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching riwayat: " . $e->getMessage());
}

// Tentukan path foto jika tersedia
$foto_url = null;
if (!empty($laporan['foto'])) {
    $foto_name = $laporan['foto'];
    if (file_exists(__DIR__ . '/../assets/uploads/laporan/' . $foto_name)) {
        $foto_url = '../assets/uploads/laporan/' . htmlspecialchars($foto_name, ENT_QUOTES, 'UTF-8');
    } elseif (file_exists(__DIR__ . '/../uploads/laporan/' . $foto_name)) {
        $foto_url = '../uploads/laporan/' . htmlspecialchars($foto_name, ENT_QUOTES, 'UTF-8');
    }
}

$csrf_token = generateCsrfToken();

// Muat Header Khusus Admin
require_once __DIR__ . '/includes/header_admin.php';
?>

<div class="container" style="padding-top: 32px; padding-bottom: 48px;">
    <!-- Breadcrumb & Navigasi Cepat -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <div style="font-size: 13.5px; color: var(--text-muted);">
            <a href="index.php" style="color: var(--primary); text-decoration: none;">&larr; Dashboard</a>
            <span style="margin: 0 6px;">/</span>
            <span>Kelola Laporan #<strong><?= htmlspecialchars($laporan['kode_laporan']); ?></strong></span>
        </div>
        <div>
            <a href="../cek-status.php?kode=<?= urlencode($laporan['kode_laporan']); ?>" class="btn btn-sm btn-outline-admin">
                Lihat Tampilan Publik
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi Sukses / Gagal -->
    <?php if (!empty($success_msg)): ?>
        <div style="background-color: #f0fdf4; border: 1px solid #86efac; border-radius: var(--radius-sm); padding: 14px 18px; font-size: 13.5px; color: #166534; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 18px; font-weight: bold;">&#10003;</span>
            <span><?= htmlspecialchars($success_msg); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="form-alert form-alert-danger" style="margin-bottom: 24px;">
            <?= htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <!-- Grid Detail & Tindak Lanjut -->
    <div class="admin-detail-grid">
        <!-- Kolom Kiri: Detail Pengaduan & Formulir Tindak Lanjut -->
        <div>
            <!-- Kartu Detail Pengaduan Warga -->
            <div class="admin-panel-card">
                <div class="admin-panel-title">
                    <span>Informasi Pengaduan</span>
                    <?php
                    $cur_badge = 'badge-status-menunggu';
                    if ($laporan['status'] === 'Diproses') $cur_badge = 'badge-status-proses';
                    elseif ($laporan['status'] === 'Selesai') $cur_badge = 'badge-status-selesai';
                    ?>
                    <span class="badge-status <?= $cur_badge; ?>" style="font-size: 12px;">
                        Status: <?= htmlspecialchars($laporan['status']); ?>
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <div class="result-label">Kode Tiket</div>
                        <div style="font-family: monospace; font-size: 16px; font-weight: 800; color: var(--navy);">
                            <?= htmlspecialchars($laporan['kode_laporan']); ?>
                        </div>
                    </div>
                    <div>
                        <div class="result-label">Waktu Masuk</div>
                        <div style="font-size: 13.5px; color: var(--navy);">
                            <?= formatTanggalIndonesia($laporan['tanggal_laporan']); ?>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <div class="result-label">Kategori Permasalahan</div>
                    <div style="font-size: 15px; font-weight: 700; color: var(--primary);">
                        <?= htmlspecialchars($laporan['nama_kategori']); ?>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <div class="result-label">Lokasi Kejadian</div>
                    <div style="font-size: 14px; font-weight: 600; color: var(--navy);">
                        <?= htmlspecialchars($laporan['alamat']); ?>, Kec. <?= htmlspecialchars($laporan['kecamatan']); ?>
                    </div>
                </div>

                <div style="margin-bottom: 8px;">
                    <div class="result-label">Deskripsi Permasalahan Warga</div>
                    <div style="background: var(--bg-subtle); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px 16px; font-size: 13.5px; color: var(--navy); line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($laporan['deskripsi'])); ?>
                    </div>
                </div>
            </div>

            <!-- Formulir Pembaruan Status & Tanggapan Petugas -->
            <div class="admin-panel-card" style="border-top: 3px solid var(--primary);">
                <div class="admin-panel-title">
                    <span>Tindak Lanjut & Pembaruan Status</span>
                    <span style="font-size: 12px; color: var(--text-muted); font-weight: normal;">Khusus Petugas</span>
                </div>

                <form action="detail-laporan.php?id=<?= $id_laporan; ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">

                    <div class="form-group">
                        <label for="status" class="form-label">
                            Pilih Status Penanganan <span class="req">*</span>
                        </label>
                        <select name="status" id="status" class="form-control" required style="font-weight: 600;">
                            <option value="Menunggu" <?= ($laporan['status'] === 'Menunggu') ? 'selected' : ''; ?>>
                                🟡 Menunggu (Menunggu Verifikasi & Penjadwalan)
                            </option>
                            <option value="Diproses" <?= ($laporan['status'] === 'Diproses') ? 'selected' : ''; ?>>
                                🔵 Diproses (Tim Teknis Sedang Menindaklanjuti)
                            </option>
                            <option value="Selesai" <?= ($laporan['status'] === 'Selesai') ? 'selected' : ''; ?>>
                                🟢 Selesai (Perbaikan Infrastruktur Telah Rampung)
                            </option>
                        </select>
                        <span class="form-hint">Perubahan status akan langsung terpublikasi pada Peta Laporan, Cek Status, dan Statistik.</span>
                    </div>

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="tanggapan" class="form-label">
                            Catatan / Tanggapan Resmi Petugas
                        </label>
                        <textarea name="tanggapan" id="tanggapan" class="form-control" placeholder="Contoh: Tim Satgas Dinas PU telah melakukan pengukuran dan material aspal akan digelar pada hari Rabu..." style="min-height: 90px;"></textarea>
                        <span class="form-hint">Tanggapan ini dapat dibaca oleh masyarakat saat memantau status pengaduan.</span>
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">
                        <span>Simpan Pembaruan & Tanggapan &rarr;</span>
                    </button>
                </form>
            </div>

            <!-- Log Jejak Riwayat Penanganan Terdahulu -->
            <div class="admin-panel-card">
                <div class="admin-panel-title">
                    <span>Jejak Riwayat Penanganan</span>
                    <span style="font-size: 12px; color: var(--text-muted); font-weight: normal;"><?= count($riwayat_list); ?> Catatan</span>
                </div>

                <?php if (!empty($riwayat_list)): ?>
                    <ul class="timeline-list">
                        <?php foreach ($riwayat_list as $rw): ?>
                            <?php
                            $rw_class = 'menunggu';
                            $rw_badge = 'badge-status-menunggu';
                            if ($rw['status'] === 'Diproses') {
                                $rw_class = 'proses';
                                $rw_badge = 'badge-status-proses';
                            } elseif ($rw['status'] === 'Selesai') {
                                $rw_class = 'selesai';
                                $rw_badge = 'badge-status-selesai';
                            }
                            ?>
                            <li class="timeline-item <?= $rw_class; ?>">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-meta">
                                        <span class="badge-status <?= $rw_badge; ?>" style="font-size: 11.5px;">
                                            <?= htmlspecialchars($rw['status']); ?>
                                        </span>
                                        <span class="timeline-time">
                                            <?= formatTanggalIndonesia($rw['tanggal_perubahan']); ?>
                                        </span>
                                    </div>
                                    <div class="timeline-note">
                                        <?php if (!empty($rw['tanggapan'])): ?>
                                            <?= nl2br(htmlspecialchars($rw['tanggapan'])); ?>
                                        <?php else: ?>
                                            <em style="color: var(--text-light);">Tanpa catatan tambahan.</em>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 6px;">
                                        Oleh: <strong><?= !empty($rw['nama_petugas']) ? htmlspecialchars($rw['nama_petugas']) : 'Sistem Otomatis'; ?></strong>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="font-size: 13px; color: var(--text-muted);">Belum ada riwayat tercatat.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kolom Kanan: Foto Bukti & Mini Peta Lokasi -->
        <div>
            <!-- Foto Bukti Kerusakan -->
            <div class="admin-panel-card">
                <div class="admin-panel-title">
                    <span>Foto Bukti Kerusakan</span>
                </div>
                <?php if ($foto_url): ?>
                    <div style="border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--border-color); background: var(--bg-subtle);">
                        <a href="<?= $foto_url; ?>" target="_blank" title="Klik untuk memperbesar">
                            <img src="<?= $foto_url; ?>" alt="Bukti Foto" style="width: 100%; height: 220px; object-fit: cover; display: block;">
                        </a>
                        <div style="padding: 10px 14px; background: #ffffff; font-size: 12.5px; display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Foto bukti warga</span>
                            <a href="<?= $foto_url; ?>" target="_blank" style="color: var(--primary); font-weight: 600;">Lihat Penuh &nearr;</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="padding: 32px 16px; text-align: center; background: var(--bg-subtle); border: 1px dashed var(--border-color); border-radius: var(--radius-sm); color: var(--text-muted); font-size: 13px;">
                        Tidak ada foto yang dilampirkan pada laporan ini.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mini Peta Titik Lokasi Leaflet -->
            <div class="admin-panel-card">
                <div class="admin-panel-title">
                    <span>Titik Lokasi Geografis</span>
                </div>
                <div style="font-family: monospace; font-size: 12px; color: var(--navy); margin-bottom: 10px;">
                    Koordinat: <?= (float)$laporan['latitude']; ?>, <?= (float)$laporan['longitude']; ?>
                </div>
                <div style="height: 260px; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--border-color);" id="adminMiniMap"></div>
            </div>
        </div>
    </div>
</div>

<!-- Skrip Peta Mini Lokasi Leaflet -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const lat = <?= (float)$laporan['latitude']; ?>;
    const lng = <?= (float)$laporan['longitude']; ?>;

    if (document.getElementById('adminMiniMap') && typeof L !== 'undefined') {
        const miniMap = L.map('adminMiniMap', {
            center: [lat, lng],
            zoom: 16,
            scrollWheelZoom: false
        });

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(miniMap);

        const pinIcon = L.divIcon({
            className: 'custom-pin-container',
            html: `<div class="custom-pin proses"><div class="custom-pin-inner">&#8226;</div></div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 32]
        });

        L.marker([lat, lng], { icon: pinIcon })
            .addTo(miniMap)
            .bindPopup("<b><?= htmlspecialchars($laporan['nama_kategori']); ?></b><br><?= htmlspecialchars($laporan['alamat']); ?>")
            .openPopup();
    }
});
</script>

<?php
// Muat Footer Khusus Admin
require_once __DIR__ . '/includes/footer_admin.php';
?>

