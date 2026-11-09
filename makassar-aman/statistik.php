<?php
// statistik.php - Halaman Statistik & Analitik Laporan Infrastruktur Kota Makassar
$page_title = "Statistik Laporan - MAKASSAR AMAN";
$active_page = "statistik";
$enable_chartjs = true;
$base_url = "./";

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Inisialisasi variabel agregasi
$total_laporan  = 0;
$total_menunggu = 0;
$total_proses   = 0;
$total_selesai  = 0;
$persen_selesai = 0;

$kategori_stats  = [];
$chart_kategori_labels = [];
$chart_kategori_data   = [];

$chart_tren_labels = [];
$chart_tren_data   = [];

$kecamatan_stats = [];
$chart_kecamatan_labels = [];
$chart_kecamatan_data   = [];

try {
    // 1. Kueri Agregasi Kartu Ringkasan (Total & Status)
    $sqlSummary = "SELECT 
        COUNT(*) AS total,
        COALESCE(SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END), 0) AS menunggu,
        COALESCE(SUM(CASE WHEN status = 'Diproses' THEN 1 ELSE 0 END), 0) AS proses,
        COALESCE(SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END), 0) AS selesai
    FROM laporan";
    $stmtSummary = $pdo->query($sqlSummary);
    $summary = $stmtSummary->fetch();

    if ($summary) {
        $total_laporan  = (int)$summary['total'];
        $total_menunggu = (int)$summary['menunggu'];
        $total_proses   = (int)$summary['proses'];
        $total_selesai  = (int)$summary['selesai'];
        $persen_selesai = ($total_laporan > 0) ? round(($total_selesai / $total_laporan) * 100, 1) : 0;
    }

    // 2. Kueri Agregasi Distribusi Kategori (LEFT JOIN agar kategori bernilai 0 tetap tampil)
    $sqlKategori = "SELECT 
        k.id_kategori,
        k.nama_kategori,
        COUNT(l.id_laporan) AS total_laporan,
        COALESCE(SUM(CASE WHEN l.status = 'Menunggu' THEN 1 ELSE 0 END), 0) AS jml_menunggu,
        COALESCE(SUM(CASE WHEN l.status = 'Diproses' THEN 1 ELSE 0 END), 0) AS jml_proses,
        COALESCE(SUM(CASE WHEN l.status = 'Selesai' THEN 1 ELSE 0 END), 0) AS jml_selesai
    FROM kategori k
    LEFT JOIN laporan l ON k.id_kategori = l.id_kategori
    GROUP BY k.id_kategori, k.nama_kategori
    ORDER BY k.id_kategori ASC";
    $stmtKat = $pdo->query($sqlKategori);
    $kategori_stats = $stmtKat->fetchAll();

    foreach ($kategori_stats as $ks) {
        $chart_kategori_labels[] = $ks['nama_kategori'];
        $chart_kategori_data[]   = (int)$ks['total_laporan'];
    }

    // 3. Kueri Tren Laporan 12 Bulan Terakhir
    $bulan_nama = [
        '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
        '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agu',
        '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'
    ];

    $tren_map = [];
    for ($i = 11; $i >= 0; $i--) {
        $dt = new DateTime("first day of -$i month");
        $key = $dt->format('Y-m');
        $label = $bulan_nama[$dt->format('m')] . ' ' . $dt->format('y');
        $tren_map[$key] = [
            'label' => $label,
            'count' => 0
        ];
    }

    $sqlTren = "SELECT 
        DATE_FORMAT(tanggal_laporan, '%Y-%m') AS periode,
        COUNT(*) AS jumlah
    FROM laporan
    WHERE tanggal_laporan >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(tanggal_laporan, '%Y-%m')
    ORDER BY periode ASC";
    $stmtTren = $pdo->query($sqlTren);
    $tren_db = $stmtTren->fetchAll();

    foreach ($tren_db as $td) {
        if (isset($tren_map[$td['periode']])) {
            $tren_map[$td['periode']]['count'] = (int)$td['jumlah'];
        }
    }

    foreach ($tren_map as $item) {
        $chart_tren_labels[] = $item['label'];
        $chart_tren_data[]   = $item['count'];
    }

    // 4. Kueri Statistik Berdasarkan Kecamatan (Diurutkan dari Terbanyak)
    $sqlKecamatan = "SELECT 
        kecamatan,
        COUNT(*) AS total_laporan,
        COALESCE(SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END), 0) AS menunggu,
        COALESCE(SUM(CASE WHEN status = 'Diproses' THEN 1 ELSE 0 END), 0) AS proses,
        COALESCE(SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END), 0) AS selesai
    FROM laporan
    WHERE kecamatan IS NOT NULL AND kecamatan != ''
    GROUP BY kecamatan
    ORDER BY total_laporan DESC";
    $stmtKec = $pdo->query($sqlKecamatan);
    $kecamatan_stats = $stmtKec->fetchAll();

    foreach ($kecamatan_stats as $kcs) {
        $chart_kecamatan_labels[] = 'Kec. ' . $kcs['kecamatan'];
        $chart_kecamatan_data[]   = (int)$kcs['total_laporan'];
    }

} catch (PDOException $e) {
    error_log("Error in statistik.php query: " . $e->getMessage());
}

// Muat kerangka Header dan Navbar
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Header Halaman Statistik -->
<section class="stat-page-header">
    <div class="container">
        <div class="stat-page-title">
            <h1>Statistik & Keterbukaan Data Infrastruktur</h1>
            <p>Pantau data persebaran pengaduan fasilitas umum, rasio penanganan dinas terkait, serta tren pelaporan di Kota Makassar secara transparan.</p>
        </div>
    </div>
</section>

<div class="container">
    <div class="stat-content-wrapper">

        <!-- 1. KARTU RINGKASAN METRIK (KPI Cards) -->
        <div class="stats-grid" style="margin-bottom: 24px;">
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
                    <span class="stat-label">Total Pengaduan Masuk</span>
                    <span class="stat-number"><?= number_format($total_laporan, 0, ',', '.'); ?></span>
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
                    <span class="stat-label">Menunggu Verifikasi</span>
                    <span class="stat-number"><?= number_format($total_menunggu, 0, ',', '.'); ?></span>
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
                    <span class="stat-number"><?= number_format($total_proses, 0, ',', '.'); ?></span>
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
                    <span class="stat-label">Selesai Ditangani</span>
                    <span class="stat-number"><?= number_format($total_selesai, 0, ',', '.'); ?></span>
                </div>
            </div>
        </div>

        <!-- Banner Persentase Tingkat Penyelesaian -->
        <div class="stat-progress-card">
            <div class="stat-progress-info">
                <h4>Tingkat Penyelesaian Laporan</h4>
                <p>Rasio pengaduan yang telah rampung ditindaklanjuti dari total laporan masyarakat.</p>
            </div>
            <div class="stat-progress-bar-wrap">
                <div class="stat-progress-meta">
                    <span>Progres Penanganan</span>
                    <span><strong><?= $persen_selesai; ?>%</strong> (<?= $total_selesai; ?> dari <?= $total_laporan; ?>)</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" style="width: <?= min(100, max(0, $persen_selesai)); ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- 2. BARIS GRAFIK 1: KATEGORI & KOMPOSISI STATUS -->
        <div class="charts-grid-2col">
            <!-- Grafik Distribusi Kategori -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="chart-card-title">
                        <h3>Distribusi Laporan per Kategori</h3>
                        <p>Perbandingan jumlah pengaduan berdasarkan 4 kategori utama</p>
                    </div>
                    <span class="chart-badge">Data Aktual</span>
                </div>
                <div class="chart-canvas-container">
                    <canvas id="chartKategori"></canvas>
                </div>
            </div>

            <!-- Grafik Komposisi Status -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="chart-card-title">
                        <h3>Komposisi Status Penanganan</h3>
                        <p>Proporsi laporan Menunggu, Diproses, dan Selesai</p>
                    </div>
                    <span class="chart-badge">Status Terkini</span>
                </div>
                <div class="chart-canvas-container doughnut-container">
                    <canvas id="chartStatus"></canvas>
                </div>
            </div>
        </div>

        <!-- 3. BARIS GRAFIK 2: TREN LAPORAN 12 BULAN TERAKHIR -->
        <div class="charts-grid-full">
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="chart-card-title">
                        <h3>Tren Pengaduan 12 Bulan Terakhir</h3>
                        <p>Dinamika jumlah laporan infrastruktur dari waktu ke waktu berdasarkan tanggal laporan</p>
                    </div>
                    <span class="chart-badge">Tren Bulanan</span>
                </div>
                <div class="chart-canvas-container line-container">
                    <canvas id="chartTren"></canvas>
                </div>
            </div>
        </div>

        <!-- 4. BARIS GRAFIK 3: STATISTIK SEBARAN KECAMATAN -->
        <div class="charts-grid-2col">
            <!-- Grafik Horizontal Bar Kecamatan -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="chart-card-title">
                        <h3>Peringkat Sebaran per Kecamatan</h3>
                        <p>Wilayah kecamatan dengan jumlah laporan terbanyak</p>
                    </div>
                    <span class="chart-badge">Ranking Wilayah</span>
                </div>
                <div class="chart-canvas-container" style="height: <?= max(300, count($chart_kecamatan_labels) * 32); ?>px;">
                    <?php if (!empty($chart_kecamatan_labels)): ?>
                        <canvas id="chartKecamatan"></canvas>
                    <?php else: ?>
                        <div style="height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 13.5px;">
                            Belum ada data laporan berdasarkan kecamatan.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tabel Rincian Data Kecamatan -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="chart-card-title">
                        <h3>Rekapitulasi Wilayah Kecamatan</h3>
                        <p>Detail jumlah laporan per status penanganan</p>
                    </div>
                    <span class="chart-badge"><?= count($kecamatan_stats); ?> Kecamatan Tercatat</span>
                </div>
                <div class="stat-table-wrap">
                    <table class="stat-data-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Kecamatan</th>
                                <th style="text-align: center;">Menunggu</th>
                                <th style="text-align: center;">Proses</th>
                                <th style="text-align: center;">Selesai</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($kecamatan_stats)): ?>
                                <?php $rank = 1; foreach ($kecamatan_stats as $krow): ?>
                                    <?php
                                    $rank_class = '';
                                    if ($rank === 1) $rank_class = 'rank-1';
                                    elseif ($rank === 2) $rank_class = 'rank-2';
                                    elseif ($rank === 3) $rank_class = 'rank-3';
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="rank-num <?= $rank_class; ?>"><?= $rank++; ?></span>
                                        </td>
                                        <td><strong><?= htmlspecialchars($krow['kecamatan']); ?></strong></td>
                                        <td style="text-align: center; color: var(--status-menunggu); font-weight: 600;">
                                            <?= (int)$krow['menunggu']; ?>
                                        </td>
                                        <td style="text-align: center; color: var(--status-proses); font-weight: 600;">
                                            <?= (int)$krow['proses']; ?>
                                        </td>
                                        <td style="text-align: center; color: var(--status-selesai); font-weight: 600;">
                                            <?= (int)$krow['selesai']; ?>
                                        </td>
                                        <td style="text-align: right; font-weight: 700; color: var(--navy);">
                                            <?= (int)$krow['total_laporan']; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 28px; color: var(--text-muted);">
                                        Belum ada data laporan yang tercatat di database.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Data JSON Aktual dari MySQL (Aman dari XSS) -->
<script>
    const dataKategoriLabels  = <?= json_encode($chart_kategori_labels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const dataKategoriValues  = <?= json_encode($chart_kategori_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    const dataStatusValues    = [<?= (int)$total_menunggu; ?>, <?= (int)$total_proses; ?>, <?= (int)$total_selesai; ?>];
    const totalSemuaLaporan   = <?= (int)$total_laporan; ?>;

    const dataTrenLabels      = <?= json_encode($chart_tren_labels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const dataTrenValues      = <?= json_encode($chart_tren_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    const dataKecamatanLabels = <?= json_encode($chart_kecamatan_labels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const dataKecamatanValues = <?= json_encode($chart_kecamatan_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>

<!-- Skrip Inisialisasi Chart.js -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Pengaturan bawaan font & warna Chart.js
    if (typeof Chart !== 'undefined') {
        Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
        Chart.defaults.color = '#64748b';
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.cornerRadius = 8;

        // 1. GRAFIK KATEGORI (Bar Chart)
        const ctxKategori = document.getElementById('chartKategori');
        if (ctxKategori) {
            new Chart(ctxKategori, {
                type: 'bar',
                data: {
                    labels: dataKategoriLabels,
                    datasets: [{
                        label: 'Jumlah Pengaduan',
                        data: dataKategoriValues,
                        backgroundColor: [
                            'rgba(2, 132, 199, 0.85)',
                            'rgba(14, 165, 233, 0.85)',
                            'rgba(56, 189, 248, 0.85)',
                            'rgba(125, 211, 252, 0.85)'
                        ],
                        borderColor: [
                            '#0284c7',
                            '#0ea5e9',
                            '#38bdf8',
                            '#7dd3fc'
                        ],
                        borderWidth: 1,
                        borderRadius: 6,
                        maxBarThickness: 48
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: '#f1f5f9' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // 2. GRAFIK STATUS LAPORAN (Doughnut Chart)
        const ctxStatus = document.getElementById('chartStatus');
        if (ctxStatus) {
            new Chart(ctxStatus, {
                type: 'doughnut',
                data: {
                    labels: ['Menunggu', 'Diproses', 'Selesai'],
                    datasets: [{
                        data: dataStatusValues,
                        backgroundColor: [
                            '#d97706', // Menunggu (Amber)
                            '#0284c7', // Diproses (Biru)
                            '#059669'  // Selesai (Hijau)
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 16,
                                font: { weight: 600 }
                            }
                        }
                    }
                }
            });
        }

        // 3. GRAFIK TREN 12 BULAN TERAKHIR (Line Chart dengan Area Fill)
        const ctxTren = document.getElementById('chartTren');
        if (ctxTren) {
            new Chart(ctxTren, {
                type: 'line',
                data: {
                    labels: dataTrenLabels,
                    datasets: [{
                        label: 'Jumlah Pengaduan',
                        data: dataTrenValues,
                        borderColor: '#0284c7',
                        backgroundColor: 'rgba(2, 132, 199, 0.08)',
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#0284c7',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: '#f1f5f9' }
                        },
                        x: {
                            grid: { color: '#f8fafc' }
                        }
                    }
                }
            });
        }

        // 4. GRAFIK KECAMATAN (Horizontal Bar Chart)
        const ctxKecamatan = document.getElementById('chartKecamatan');
        if (ctxKecamatan && dataKecamatanLabels.length > 0) {
            new Chart(ctxKecamatan, {
                type: 'bar',
                data: {
                    labels: dataKecamatanLabels,
                    datasets: [{
                        label: 'Total Laporan',
                        data: dataKecamatanValues,
                        backgroundColor: 'rgba(15, 23, 42, 0.85)',
                        borderColor: '#0f172a',
                        borderWidth: 1,
                        borderRadius: 4,
                        maxBarThickness: 24
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: '#f1f5f9' }
                        },
                        y: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    }
});
</script>

<?php
// Muat kerangka Footer
require_once __DIR__ . '/includes/footer.php';
?>

