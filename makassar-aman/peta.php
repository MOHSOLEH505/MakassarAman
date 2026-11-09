<?php
// peta.php - Halaman Peta Laporan Infrastruktur Kota Makassar
$page_title = "Peta Laporan - MAKASSAR AMAN";
$active_page = "peta";
$enable_leaflet = true;
$base_url = "./";

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Tangkap parameter filter & pencarian
$filter_kategori = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// 1. Ambil daftar kategori master untuk dropdown filter
$kategori_options = [];
try {
    $stmtKat = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY id_kategori ASC");
    $kategori_options = $stmtKat->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching kategori: " . $e->getMessage());
    $kategori_options = [];
}

// 2. Siapkan query data laporan dengan prepared statement
$sql = "SELECT l.id_laporan, l.kode_laporan, l.id_kategori, l.deskripsi, l.foto, 
               l.alamat, l.kecamatan, l.latitude, l.longitude, l.status, l.tanggal_laporan,
               k.nama_kategori 
        FROM laporan l
        JOIN kategori k ON l.id_kategori = k.id_kategori
        WHERE l.latitude IS NOT NULL 
          AND l.longitude IS NOT NULL";
$params = [];

if (!empty($filter_kategori)) {
    $sql .= " AND l.id_kategori = :id_kategori";
    $params[':id_kategori'] = $filter_kategori;
}

if (!empty($filter_status) && in_array($filter_status, ['Menunggu', 'Diproses', 'Selesai'])) {
    $sql .= " AND l.status = :status";
    $params[':status'] = $filter_status;
}

if (!empty($search_query)) {
    $sql .= " AND (l.alamat LIKE :search OR l.kecamatan LIKE :search OR l.kode_laporan LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$sql .= " ORDER BY l.tanggal_laporan DESC";

$raw_laporan = [];
try {
    $stmtLaporan = $pdo->prepare($sql);
    $stmtLaporan->execute($params);
    $raw_laporan = $stmtLaporan->fetchAll();
} catch (PDOException $e) {
    error_log("Error querying laporan: " . $e->getMessage());
    $raw_laporan = [];
}

// 3. Validasi ketat koordinat sebelum dikirim ke JavaScript
$valid_markers = [];
foreach ($raw_laporan as $row) {
    $lat = filter_var($row['latitude'], FILTER_VALIDATE_FLOAT);
    $lng = filter_var($row['longitude'], FILTER_VALIDATE_FLOAT);

    // Jangan tampilkan jika koordinat kosong, bernilai 0, atau di luar batas geografis bumi
    if ($lat === false || $lng === false || ($lat == 0 && $lng == 0)) {
        continue;
    }
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        continue;
    }

    // Periksa keberadaan file foto
    $foto_url = null;
    if (!empty($row['foto'])) {
        $foto_path_assets = __DIR__ . '/assets/uploads/laporan/' . $row['foto'];
        $foto_path_root = __DIR__ . '/uploads/laporan/' . $row['foto'];
        if (file_exists($foto_path_assets)) {
            $foto_url = 'assets/uploads/laporan/' . htmlspecialchars($row['foto'], ENT_QUOTES, 'UTF-8');
        } elseif (file_exists($foto_path_root)) {
            $foto_url = 'uploads/laporan/' . htmlspecialchars($row['foto'], ENT_QUOTES, 'UTF-8');
        }
    }

    // Escape seluruh data untuk mencegah XSS
    $valid_markers[] = [
        'id'        => (int)$row['id_laporan'],
        'kode'      => htmlspecialchars($row['kode_laporan'], ENT_QUOTES, 'UTF-8'),
        'kategori'  => htmlspecialchars($row['nama_kategori'], ENT_QUOTES, 'UTF-8'),
        'alamat'    => htmlspecialchars($row['alamat'], ENT_QUOTES, 'UTF-8'),
        'kecamatan' => htmlspecialchars($row['kecamatan'], ENT_QUOTES, 'UTF-8'),
        'deskripsi' => htmlspecialchars($row['deskripsi'], ENT_QUOTES, 'UTF-8'),
        'status'    => htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'),
        'tanggal'   => formatTanggalIndonesia($row['tanggal_laporan']),
        'latitude'  => (float)$lat,
        'longitude' => (float)$lng,
        'foto'      => $foto_url,
    ];
}

$total_ditemukan = count($valid_markers);
$is_filtered = (!empty($filter_kategori) || !empty($filter_status) || !empty($search_query));

// Muat kerangka Header dan Navbar
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Header Halaman Peta Laporan -->
<section class="map-page-header">
    <div class="container">
        <div class="map-page-title">
            <h1>Peta Sebaran Laporan Infrastruktur</h1>
            <p>Pantau titik persebaran kerusakan fasilitas publik di Kota Makassar secara spasial dan transparan.</p>
        </div>
        <div class="map-page-stats">
            <div class="map-stat-badge">
                <span>Total Ditampilkan:</span>
                <strong><?= $total_ditemukan; ?> Titik</strong>
            </div>
            <a href="buat-laporan.php" class="btn btn-primary btn-sm">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Buat Laporan</span>
            </a>
        </div>
    </div>
</section>

<div class="container">
    <!-- Formulir Filter dan Pencarian -->
    <div class="map-filter-card">
        <form method="GET" action="peta.php" class="map-filter-form">
            <!-- Filter Kategori -->
            <div class="filter-group">
                <label for="filter_kategori" class="filter-label">Kategori</label>
                <select name="kategori" id="filter_kategori" class="filter-select">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($kategori_options as $kat): ?>
                        <option value="<?= (int)$kat['id_kategori']; ?>" <?= ($filter_kategori == $kat['id_kategori']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($kat['nama_kategori']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Status -->
            <div class="filter-group">
                <label for="filter_status" class="filter-label">Status Penanganan</label>
                <select name="status" id="filter_status" class="filter-select">
                    <option value="">Semua Status</option>
                    <option value="Menunggu" <?= ($filter_status === 'Menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                    <option value="Diproses" <?= ($filter_status === 'Diproses') ? 'selected' : ''; ?>>Diproses</option>
                    <option value="Selesai" <?= ($filter_status === 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                </select>
            </div>

            <!-- Pencarian Alamat / Nama Jalan -->
            <div class="filter-group filter-group-search">
                <label for="filter_q" class="filter-label">Cari Lokasi / Kode</label>
                <input type="text" name="q" id="filter_q" class="filter-input" placeholder="Nama jalan, kecamatan, atau kode tiket..." value="<?= htmlspecialchars($search_query); ?>">
            </div>

            <!-- Tombol Aksi Filter -->
            <div class="filter-group filter-group-actions" style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary btn-filter">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <span>Filter</span>
                </button>
                <?php if ($is_filtered): ?>
                    <a href="peta.php" class="btn btn-outline-primary btn-filter" title="Reset semua filter">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Area Peta Interaktif -->
    <div class="map-main-wrapper">
        <!-- Toolbar Informasi & Legenda Status -->
        <div class="map-toolbar-top">
            <div class="map-legend">
                <span class="legend-title">Status Penanda:</span>
                <div class="legend-item">
                    <span class="legend-dot menunggu"></span>
                    <span>Menunggu</span>
                </div>
                <div class="legend-item">
                    <span class="legend-dot proses"></span>
                    <span>Diproses</span>
                </div>
                <div class="legend-item">
                    <span class="legend-dot selesai"></span>
                    <span>Selesai</span>
                </div>
            </div>
            <div>
                <span style="color: var(--text-muted);">Pusat: Kota Makassar (-5.1477, 119.4327)</span>
            </div>
        </div>

        <!-- Wadah Elemen Peta Leaflet -->
        <div id="map"></div>

        <!-- Tampilan Empty State jika belum ada laporan atau filter tidak cocok -->
        <?php if ($total_ditemukan === 0): ?>
            <div class="map-empty-overlay">
                <h4>Belum Ada Titik Laporan yang Ditampilkan</h4>
                <p>
                    <?= $is_filtered ? 'Tidak ada laporan yang sesuai dengan kriteria filter atau pencarian Anda. Coba atur ulang filter pencarian.' : 'Saat ini belum terdapat data laporan infrastruktur yang tercatat di sistem.'; ?>
                </p>
                <div style="display: flex; gap: 8px; justify-content: center;">
                    <?php if ($is_filtered): ?>
                        <a href="peta.php" class="btn btn-sm btn-outline-primary">Reset Filter</a>
                    <?php endif; ?>
                    <a href="buat-laporan.php" class="btn btn-sm btn-primary">Buat Laporan Pertama</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Data JSON Marker yang divalidasi dan diescape secara aman -->
<script>
    const makassarReports = <?= json_encode($valid_markers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>

<!-- Skrip Inisialisasi Peta Leaflet -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Inisialisasi Peta terpusat di Kota Makassar
    const MAKASSAR_LAT = -5.147665;
    const MAKASSAR_LNG = 119.432732;
    const DEFAULT_ZOOM = 13;

    const map = L.map('map', {
        center: [MAKASSAR_LAT, MAKASSAR_LNG],
        zoom: DEFAULT_ZOOM,
        scrollWheelZoom: true
    });

    // 2. Tambahkan Tile Layer OpenStreetMap
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors &bull; MAKASSAR AMAN'
    }).addTo(map);

    // 3. Ikon Marker Kustom Berdasarkan Status
    function createCustomPin(status, kategori) {
        let statusClass = 'menunggu';
        let initialLetter = 'M';

        if (status === 'Diproses') {
            statusClass = 'proses';
            initialLetter = 'P';
        } else if (status === 'Selesai') {
            statusClass = 'selesai';
            initialLetter = 'S';
        }

        return L.divIcon({
            className: 'custom-pin-container',
            html: `<div class="custom-pin ${statusClass}">
                       <div class="custom-pin-inner">${initialLetter}</div>
                   </div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -30]
        });
    }

    // 4. Tambahkan Penanda (Markers) ke dalam Peta
    if (makassarReports && makassarReports.length > 0) {
        const markerGroup = L.featureGroup();

        makassarReports.forEach(function (report) {
            const pinIcon = createCustomPin(report.status, report.kategori);
            const marker = L.marker([report.latitude, report.longitude], { icon: pinIcon });

            // Susun Konten Popup
            let badgeClass = 'badge-status-menunggu';
            if (report.status === 'Diproses') badgeClass = 'badge-status-proses';
            if (report.status === 'Selesai') badgeClass = 'badge-status-selesai';

            let photoHtml = '';
            if (report.foto) {
                photoHtml = `<img src="${report.foto}" alt="Bukti ${report.kategori}" class="popup-photo">`;
            }

            const popupContent = `
                <div class="popup-card">
                    ${photoHtml}
                    <div class="popup-body">
                        <div class="popup-badges">
                            <span class="popup-kategori">${report.kategori}</span>
                            <span class="badge-status ${badgeClass}">${report.status}</span>
                        </div>
                        <div class="popup-code">${report.kode}</div>
                        <div class="popup-address">${report.alamat}, Kec. ${report.kecamatan}</div>
                        <p class="popup-desc">${report.deskripsi}</p>
                        <div class="popup-footer">
                            <span>${report.tanggal}</span>
                            <a href="cek-status.php?kode=${encodeURIComponent(report.kode)}" class="popup-link-status">
                                Cek Status &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            `;

            marker.bindPopup(popupContent, { maxWidth: 320 });
            markerGroup.addLayer(marker);
        });

        markerGroup.addTo(map);

        // Sesuaikan pandangan peta agar seluruh marker terlihat
        try {
            map.fitBounds(markerGroup.getBounds().pad(0.12));
        } catch (e) {
            map.setView([MAKASSAR_LAT, MAKASSAR_LNG], DEFAULT_ZOOM);
        }
    }
});
</script>

<?php
// Muat kerangka Footer
require_once __DIR__ . '/includes/footer.php';
?>

