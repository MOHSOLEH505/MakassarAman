<?php
// admin/index.php - Dashboard Manajemen Laporan Administrator MAKASSAR AMAN
$page_title = "Dashboard Pengaduan - MAKASSAR AMAN Admin";

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Pastikan hanya admin yang telah terotentikasi yang dapat mengakses
requireAdminLogin();

// Tangkap filter status, kategori, dan pencarian
$filter_status   = isset($_GET['status']) ? trim($_GET['status']) : '';
$filter_kategori = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
$search_query    = isset($_GET['q']) ? trim($_GET['q']) : '';

// 1. Ambil Data Metrik Ringkasan
$total_laporan  = 0;
$total_menunggu = 0;
$total_proses   = 0;
$total_selesai  = 0;

try {
    $stmtSum = $pdo->query("SELECT 
        COUNT(*) AS total,
        COALESCE(SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END), 0) AS menunggu,
        COALESCE(SUM(CASE WHEN status = 'Diproses' THEN 1 ELSE 0 END), 0) AS proses,
        COALESCE(SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END), 0) AS selesai
    FROM laporan");
    $summary = $stmtSum->fetch();

    if ($summary) {
        $total_laporan  = (int)$summary['total'];
        $total_menunggu = (int)$summary['menunggu'];
        $total_proses   = (int)$summary['proses'];
        $total_selesai  = (int)$summary['selesai'];
    }
} catch (PDOException $e) {
    error_log("Error in admin index metrics: " . $e->getMessage());
}

// 2. Ambil Master Kategori untuk Dropdown
$kategori_list = [];
try {
    $stmtKat = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY id_kategori ASC");
    $kategori_list = $stmtKat->fetchAll();
} catch (PDOException $e) {
    error_log("Error in admin index kategori: " . $e->getMessage());
}

// 3. Susun Kueri Daftar Laporan dengan Prepared Statements
$sql = "SELECT l.id_laporan, l.kode_laporan, l.id_kategori, l.deskripsi, l.foto, 
               l.alamat, l.kecamatan, l.latitude, l.longitude, l.status, l.tanggal_laporan,
               k.nama_kategori
        FROM laporan l
        JOIN kategori k ON l.id_kategori = k.id_kategori
        WHERE 1=1";
$params = [];

if (!empty($filter_status) && in_array($filter_status, ['Menunggu', 'Diproses', 'Selesai'])) {
    $sql .= " AND l.status = :status";
    $params[':status'] = $filter_status;
}

if (!empty($filter_kategori)) {
    $sql .= " AND l.id_kategori = :id_kategori";
    $params[':id_kategori'] = $filter_kategori;
}

if (!empty($search_query)) {
    $sql .= " AND (l.kode_laporan LIKE :search OR l.alamat LIKE :search OR l.kecamatan LIKE :search OR l.deskripsi LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$sql .= " ORDER BY l.tanggal_laporan DESC";

$laporan_list = [];
try {
    $stmtLaporan = $pdo->prepare($sql);
    $stmtLaporan->execute($params);
    $laporan_list = $stmtLaporan->fetchAll();
} catch (PDOException $e) {
    error_log("Error querying admin laporan: " . $e->getMessage());
}

$is_filtered = (!empty($filter_status) || !empty($filter_kategori) || !empty($search_query));

// Muat Header Khusus Admin
require_once __DIR__ . '/includes/header_admin.php';
?>

<div class="container" style="padding-top: 32px; padding-bottom: 48px;">
    <!-- Judul Halaman Dashboard -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 800; color: var(--navy); margin-bottom: 4px;">
                Dashboard Manajemen Pengaduan
            </h1>
            <p style="font-size: 13.5px; color: var(--text-muted); margin: 0;">
                Kelola status penanganan pengaduan infrastruktur Kota Makassar dan berikan tanggapan resmi dinas terkait.
            </p>
        </div>
        <div>
            <a href="../buat-laporan.php" class="btn btn-sm btn-outline-primary">
                + Input Pengaduan Baru
            </a>
        </div>
    </div>

    <!-- Kartu Ringkasan Metrik (KPI) -->
    <div class="stats-grid" style="margin-bottom: 28px;">
        <div class="stat-card total">
            <div class="stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-label">Total Pengaduan</span>
                <span class="stat-number"><?= $total_laporan; ?></span>
            </div>
        </div>

        <div class="stat-card menunggu">
            <div class="stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-label">Perlu Verifikasi</span>
                <span class="stat-number"><?= $total_menunggu; ?></span>
            </div>
        </div>

        <div class="stat-card proses">
            <div class="stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <polyline points="1 20 1 14 7 14"></polyline>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-label">Sedang Ditangani</span>
                <span class="stat-number"><?= $total_proses; ?></span>
            </div>
        </div>

        <div class="stat-card selesai">
            <div class="stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-label">Telah Selesai</span>
                <span class="stat-number"><?= $total_selesai; ?></span>
            </div>
        </div>
    </div>

    <!-- Toolbar Filter dan Pencarian -->
    <div class="admin-toolbar-card">
        <form action="index.php" method="GET" class="admin-filter-form">
            <div style="flex-grow: 1; min-width: 220px;">
                <input type="text" name="q" class="form-control" style="height: 40px; font-size: 13.5px;" placeholder="Cari kode tiket, alamat, kecamatan, atau keluhan..." value="<?= htmlspecialchars($search_query); ?>">
            </div>

            <div style="min-width: 160px;">
                <select name="status" class="form-control" style="height: 40px; font-size: 13.5px;">
                    <option value="">-- Semua Status --</option>
                    <option value="Menunggu" <?= ($filter_status === 'Menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                    <option value="Diproses" <?= ($filter_status === 'Diproses') ? 'selected' : ''; ?>>Diproses</option>
                    <option value="Selesai" <?= ($filter_status === 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                </select>
            </div>

            <div style="min-width: 180px;">
                <select name="kategori" class="form-control" style="height: 40px; font-size: 13.5px;">
                    <option value="">-- Semua Kategori --</option>
                    <?php foreach ($kategori_list as $kat): ?>
                        <option value="<?= (int)$kat['id_kategori']; ?>" <?= ($filter_kategori == $kat['id_kategori']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($kat['nama_kategori']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary btn-sm" style="height: 40px; padding: 0 16px;">
                    Filter Data
                </button>
                <?php if ($is_filtered): ?>
                    <a href="index.php" class="btn btn-outline-admin btn-sm" style="height: 40px; display: inline-flex; align-items: center;" title="Reset filter">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Laporan Pengaduan -->
    <div class="admin-table-card">
        <div style="padding: 16px 20px; background-color: #ffffff; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
            <div style="font-weight: 700; font-size: 14.5px; color: var(--navy);">
                Daftar Pengaduan Masuk
            </div>
            <div style="font-size: 12.5px; color: var(--text-muted);">
                Menampilkan <strong><?= count($laporan_list); ?></strong> laporan
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="admin-data-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th>Kode Tiket</th>
                        <th>Waktu Masuk</th>
                        <th>Kategori</th>
                        <th>Lokasi Permasalahan</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($laporan_list)): ?>
                        <?php $no = 1; foreach ($laporan_list as $row): ?>
                            <?php
                            $badge_class = 'badge-status-menunggu';
                            if ($row['status'] === 'Diproses') $badge_class = 'badge-status-proses';
                            elseif ($row['status'] === 'Selesai') $badge_class = 'badge-status-selesai';
                            ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td>
                                    <strong style="font-family: monospace; font-size: 13.5px; color: var(--navy);">
                                        <?= htmlspecialchars($row['kode_laporan']); ?>
                                    </strong>
                                </td>
                                <td style="font-size: 12.5px; color: var(--text-muted); white-space: nowrap;">
                                    <?= formatTanggalIndonesia($row['tanggal_laporan']); ?>
                                </td>
                                <td>
                                    <span style="font-size: 12px; font-weight: 600; color: var(--primary); background: var(--primary-light); padding: 3px 8px; border-radius: var(--radius-full); white-space: nowrap;">
                                        <?= htmlspecialchars($row['nama_kategori']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong>Kec. <?= htmlspecialchars($row['kecamatan']); ?></strong><br>
                                    <span style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars(mb_strimwidth($row['alamat'], 0, 45, '...')); ?></span>
                                </td>
                                <td>
                                    <span class="badge-status <?= $badge_class; ?>" style="font-size: 12px;">
                                        <?= htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="detail-laporan.php?id=<?= (int)$row['id_laporan']; ?>" class="btn btn-sm btn-primary">
                                        <span>Kelola &rarr;</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 48px 20px; color: var(--text-muted);">
                                <?= $is_filtered ? 'Tidak ada laporan yang sesuai dengan filter pencarian.' : 'Belum ada pengaduan infrastruktur yang masuk ke dalam sistem.'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Muat Footer Khusus Admin
require_once __DIR__ . '/includes/footer_admin.php';
?>

