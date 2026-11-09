<?php
// buat-laporan.php - Formulir Pelaporan Permasalahan Infrastruktur Kota Makassar
$page_title = "Buat Laporan - MAKASSAR AMAN";
$active_page = "buat-laporan";
$enable_leaflet = true;
$base_url = "./";

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Pastikan token CSRF tersedia
$csrf_token = generateCsrfToken();

// Daftar 15 Kecamatan Resmi di Kota Makassar
$kecamatan_makassar = [
    'Biringkanaya',
    'Bontoala',
    'Kepulauan Sangkarrang',
    'Makassar',
    'Mamajang',
    'Manggala',
    'Mariso',
    'Panakkukang',
    'Rappocini',
    'Tallo',
    'Tamalanrea',
    'Tamalate',
    'Ujung Pandang',
    'Ujung Tanah',
    'Wajo'
];

// 1. Ambil daftar kategori dari database menggunakan PDO (Tanpa Hardcode)
$kategori_list = [];
try {
    $stmtKat = $pdo->query("SELECT id_kategori, nama_kategori, deskripsi FROM kategori ORDER BY id_kategori ASC");
    $kategori_list = $stmtKat->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching kategori in buat-laporan: " . $e->getMessage());
    $kategori_list = [];
}

// Inisialisasi variabel state form
$errors = [];
$success = false;
$created_report = null;

// Tangkap nilai input untuk mempertahankan isian jika validasi gagal
$id_kategori = isset($_POST['id_kategori']) ? trim($_POST['id_kategori']) : (isset($_GET['kategori']) ? trim($_GET['kategori']) : '');
$deskripsi   = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
$alamat      = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';
$kecamatan   = isset($_POST['kecamatan']) ? trim($_POST['kecamatan']) : '';
$latitude    = isset($_POST['latitude']) ? trim($_POST['latitude']) : '';
$longitude   = isset($_POST['longitude']) ? trim($_POST['longitude']) : '';

// 2. Pemrosesan Data saat Form Disubmit (Metode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A. Verifikasi CSRF Token
    $post_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verifyCsrfToken($post_token)) {
        $errors[] = "Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.";
    }

    // B. Validasi Kategori
    $valid_kategori = false;
    $selected_kategori_name = '';
    foreach ($kategori_list as $kat) {
        if ((string)$kat['id_kategori'] === (string)$id_kategori) {
            $valid_kategori = true;
            $selected_kategori_name = $kat['nama_kategori'];
            break;
        }
    }
    if (!$valid_kategori) {
        $errors[] = "Silakan pilih kategori permasalahan yang valid.";
    }

    // C. Validasi Deskripsi
    if (empty($deskripsi)) {
        $errors[] = "Deskripsi permasalahan wajib diisi.";
    } elseif (mb_strlen($deskripsi) < 10) {
        $errors[] = "Deskripsi terlalu singkat. Jelaskan kondisi permasalahan minimal 10 karakter.";
    } elseif (mb_strlen($deskripsi) > 2000) {
        $errors[] = "Deskripsi maksimal 2.000 karakter.";
    }

    // D. Validasi Alamat & Kecamatan
    if (empty($alamat)) {
        $errors[] = "Alamat atau patokan lokasi wajib diisi.";
    } elseif (mb_strlen($alamat) < 5) {
        $errors[] = "Alamat terlalu singkat. Berikan nama jalan atau patokan yang jelas.";
    }

    if (empty($kecamatan) || !in_array($kecamatan, $kecamatan_makassar, true)) {
        $errors[] = "Silakan pilih kecamatan di Kota Makassar yang sesuai.";
    }

    // E. Validasi Koordinat Spasial (Latitude & Longitude)
    $lat_val = filter_var($latitude, FILTER_VALIDATE_FLOAT);
    $lng_val = filter_var($longitude, FILTER_VALIDATE_FLOAT);

    if ($lat_val === false || $lng_val === false || ($lat_val == 0 && $lng_val == 0)) {
        $errors[] = "Titik lokasi pada peta wajib ditentukan. Klik peta untuk menandai lokasi.";
    } else {
        // Validasi geografis Kota Makassar dan sekitarnya (Lat: -5.35 s/d -5.00, Lng: 119.30 s/d 119.65)
        if ($lat_val < -5.40 || $lat_val > -4.95 || $lng_val < 119.20 || $lng_val > 119.70) {
            $errors[] = "Titik koordinat berada di luar wilayah Kota Makassar. Pastikan penanda berada di area Makassar.";
        }
    }

    // F. Validasi dan Penanganan Unggah Foto Bukti
    $saved_filename = null;
    $upload_dir = __DIR__ . '/assets/uploads/laporan/';

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file_error = $_FILES['foto']['error'];
        $file_size  = $_FILES['foto']['size'];
        $file_tmp   = $_FILES['foto']['tmp_name'];
        $file_name  = $_FILES['foto']['name'];

        if ($file_error !== UPLOAD_ERR_OK) {
            $errors[] = "Gagal mengunggah foto. Silakan coba pilih file kembali.";
        } elseif ($file_size > 5 * 1024 * 1024) { // Maksimal 5 MB
            $errors[] = "Ukuran file foto melebihi batas maksimal (5 MB).";
        } else {
            // Validasi MIME Type secara ketat di sisi server
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($file_tmp);
            $allowed_mimes = [
                'image/jpeg' => 'jpg',
                'image/pjpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            if (!array_key_exists($mime_type, $allowed_mimes)) {
                $errors[] = "Format file tidak didukung. Harap gunakan foto dengan format JPG, JPEG, PNG, atau WEBP.";
            } else {
                // Buat nama file yang aman, acak, dan unik
                $extension = $allowed_mimes[$mime_type];
                $random_suffix = bin2hex(random_bytes(6));
                $saved_filename = 'lap_' . date('Ymd_His') . '_' . $random_suffix . '.' . $extension;

                // Pastikan direktori tujuan tersedia
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $target_path = $upload_dir . $saved_filename;
                if (!move_uploaded_file($file_tmp, $target_path)) {
                    $errors[] = "Gagal memindahkan file foto ke direktori penyimpanan.";
                    $saved_filename = null;
                }
            }
        }
    }

    // G. Simpan ke Database Menggunakan Transaksi Database
    if (empty($errors)) {
        // Buat kode laporan unik (format: MKS-YYYYMMDD-XXXX)
        $kode_laporan = generateKodeLaporan();

        // Pastikan kode benar-benar unik
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM laporan WHERE kode_laporan = ?");
        $stmtCheck->execute([$kode_laporan]);
        while ($stmtCheck->fetchColumn() > 0) {
            $kode_laporan = generateKodeLaporan();
            $stmtCheck->execute([$kode_laporan]);
        }

        // Mulai transaksi database
        $pdo->beginTransaction();
        try {
            // 1. Masukkan ke tabel laporan
            $sqlLaporan = "INSERT INTO laporan (kode_laporan, id_kategori, deskripsi, foto, alamat, kecamatan, latitude, longitude, status, tanggal_laporan)
                           VALUES (:kode, :id_kat, :deskripsi, :foto, :alamat, :kecamatan, :lat, :lng, 'Menunggu', NOW())";
            $stmtInsert = $pdo->prepare($sqlLaporan);
            $stmtInsert->execute([
                ':kode'      => $kode_laporan,
                ':id_kat'    => (int)$id_kategori,
                ':deskripsi' => $deskripsi,
                ':foto'      => $saved_filename,
                ':alamat'    => $alamat,
                ':kecamatan' => $kecamatan,
                ':lat'       => $lat_val,
                ':lng'       => $lng_val
            ]);

            $id_laporan_baru = (int)$pdo->lastInsertId();

            // 2. Masukkan riwayat status awal ke tabel riwayat_status
            $sqlRiwayat = "INSERT INTO riwayat_status (id_laporan, id_admin, status, tanggapan, tanggal_perubahan)
                           VALUES (:id_laporan, NULL, 'Menunggu', :tanggapan, NOW())";
            $stmtRiwayat = $pdo->prepare($sqlRiwayat);
            $stmtRiwayat->execute([
                ':id_laporan' => $id_laporan_baru,
                ':tanggapan'  => 'Laporan baru diajukan oleh masyarakat dan sedang menunggu verifikasi petugas.'
            ]);

            // Komit transaksi
            $pdo->commit();
            $success = true;

            $created_report = [
                'kode'      => $kode_laporan,
                'kategori'  => $selected_kategori_name,
                'alamat'    => $alamat,
                'kecamatan' => $kecamatan,
                'status'    => 'Menunggu',
                'tanggal'   => formatTanggalIndonesia(date('Y-m-d H:i:s'))
            ];

            // Reset isian form setelah berhasil
            $id_kategori = '';
            $deskripsi   = '';
            $alamat      = '';
            $kecamatan   = '';
            $latitude    = '';
            $longitude   = '';
        } catch (PDOException $e) {
            // Batalkan transaksi jika terjadi galat
            $pdo->rollBack();
            error_log("Error saving report transaction: " . $e->getMessage());
            $errors[] = "Terjadi gangguan sistem saat menyimpan data laporan. Silakan coba kembali dalam beberapa saat.";

            // Hapus file foto yang sudah terunggah jika penyimpanan DB gagal
            if ($saved_filename && file_exists($upload_dir . $saved_filename)) {
                @unlink($upload_dir . $saved_filename);
            }
        }
    }
}

// Muat kerangka Header dan Navbar
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Header Halaman Formulir Buat Laporan -->
<section class="form-page-header">
    <div class="container">
        <div class="form-page-title">
            <h1>Formulir Pengaduan Infrastruktur</h1>
            <p>Laporkan jalan rusak, lampu penerangan padam, genangan air, atau drainase tersumbat di sekitar Anda untuk segera ditindaklanjuti Pemerintah Kota Makassar.</p>
        </div>
    </div>
</section>

<div class="container">
    <div class="report-form-container">

        <?php if ($success && $created_report): ?>
            <!-- Tampilan Notifikasi Berhasil -->
            <div class="success-card">
                <div class="success-icon-badge">&#10003;</div>
                <h2 class="success-title">Laporan Anda Berhasil Terkirim!</h2>
                <p class="success-desc">
                    Terima kasih atas kepedulian Anda terhadap fasilitas Kota Makassar. Pengaduan Anda telah masuk ke dalam sistem dengan status <strong>Menunggu Verifikasi</strong>.
                </p>

                <!-- Box Kode Laporan -->
                <div class="ticket-box">
                    <div class="ticket-label">Simpan Kode Tiket Pengaduan Anda</div>
                    <div class="ticket-code-wrap">
                        <span class="ticket-code" id="ticketCode"><?= htmlspecialchars($created_report['kode']); ?></span>
                        <button type="button" class="btn-copy" id="btnCopyCode" onclick="copyTicketCode()">
                            Salin Kode
                        </button>
                    </div>
                </div>

                <div class="success-actions">
                    <a href="cek-status.php?kode=<?= urlencode($created_report['kode']); ?>" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <span>Cek Status Laporan Ini</span>
                    </a>
                    <a href="peta.php" class="btn btn-outline-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                            <line x1="8" y1="2" x2="8" y2="18"></line>
                            <line x1="16" y1="6" x2="16" y2="22"></line>
                        </svg>
                        <span>Lihat di Peta Laporan</span>
                    </a>
                    <a href="buat-laporan.php" class="btn btn-outline-admin">
                        + Buat Laporan Lain
                    </a>
                </div>
            </div>

        <?php else: ?>

            <!-- Tampilan Pesan Galat jika Validasi Gagal -->
            <?php if (!empty($errors)): ?>
                <div class="form-alert form-alert-danger">
                    <div style="font-weight: bold; font-size: 18px; line-height: 1;">&times;</div>
                    <div>
                        <strong>Pengajuan laporan belum dapat diproses:</strong>
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Kartu Formulir Pelaporan -->
            <div class="report-card">
                <form action="buat-laporan.php" method="POST" enctype="multipart/form-data" id="reportForm" novalidate>
                    <!-- CSRF Token Keamanan -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">

                    <!-- 1. Kategori & Informasi Permasalahan -->
                    <div class="form-section-title">
                        <span class="form-section-badge">1</span>
                        <span>Informasi Permasalahan Infrastruktur</span>
                    </div>

                    <!-- Pilihan Kategori -->
                    <div class="form-group">
                        <label for="id_kategori" class="form-label">
                            Kategori Permasalahan <span class="req">*</span>
                        </label>
                        <select name="id_kategori" id="id_kategori" class="form-control" required>
                            <option value="">-- Pilih Kategori Permasalahan --</option>
                            <?php foreach ($kategori_list as $kat): ?>
                                <option value="<?= (int)$kat['id_kategori']; ?>" <?= ((string)$id_kategori === (string)$kat['id_kategori']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($kat['nama_kategori']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-hint">Pilih jenis kendala yang paling sesuai dengan kondisi yang Anda temukan.</span>
                    </div>

                    <!-- Deskripsi Masalah -->
                    <div class="form-group">
                        <label for="deskripsi" class="form-label">
                            Deskripsi Permasalahan <span class="req">*</span>
                        </label>
                        <textarea name="deskripsi" id="deskripsi" class="form-control" placeholder="Jelaskan secara rinci kondisi kerusakan, tingkat keparahan, atau dampak terhadap pengguna jalan..." required><?= htmlspecialchars($deskripsi); ?></textarea>
                        <span class="form-hint">Contoh: Terdapat lubang jalan berdiameter &plusmn;50 cm sedalam 15 cm di dekat pertigaan jalan yang membahayakan pengendara motor saat malam hari.</span>
                    </div>

                    <!-- Unggah Foto Bukti -->
                    <div class="form-group">
                        <label class="form-label">
                            Foto Bukti Permasalahan (Opsional)
                        </label>
                        <div class="file-upload-box" id="dropZone">
                            <input type="file" name="foto" id="fotoInput" class="upload-hidden-input" accept="image/jpeg,image/png,image/webp">
                            <div class="upload-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                            </div>
                            <div class="upload-text-main" id="uploadTextMain">Pilih atau Seret Foto ke Sini</div>
                            <div class="upload-text-sub">Format yang didukung: JPG, JPEG, PNG, WEBP &bull; Maksimal 5 MB</div>
                        </div>
                        <div class="image-preview-wrapper" id="previewWrapper">
                            <img id="imagePreview" src="" alt="Pratinjau Foto">
                            <span id="fileNameBadge" style="display: block; font-size: 11.5px; color: var(--text-muted); margin-top: 4px;"></span>
                        </div>
                    </div>

                    <!-- 2. Lokasi Geografis & Titik Peta -->
                    <div class="form-section-title" style="margin-top: 36px;">
                        <span class="form-section-badge">2</span>
                        <span>Lokasi Permasalahan di Kota Makassar</span>
                    </div>

                    <div class="form-row">
                        <!-- Pilihan Kecamatan -->
                        <div class="form-group">
                            <label for="kecamatan" class="form-label">
                                Kecamatan <span class="req">*</span>
                            </label>
                            <select name="kecamatan" id="kecamatan" class="form-control" required>
                                <option value="">-- Pilih Kecamatan --</option>
                                <?php foreach ($kecamatan_makassar as $kec): ?>
                                    <option value="<?= htmlspecialchars($kec); ?>" <?= ($kecamatan === $kec) ? 'selected' : ''; ?>>
                                        Kec. <?= htmlspecialchars($kec); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Input Alamat / Patokan -->
                        <div class="form-group">
                            <label for="alamat" class="form-label">
                                Alamat / Nama Jalan / Patokan <span class="req">*</span>
                            </label>
                            <input type="text" name="alamat" id="alamat" class="form-control" placeholder="Contoh: Jl. A.P. Pettarani No. 12 (Depan Telkom)" value="<?= htmlspecialchars($alamat); ?>" required>
                        </div>
                    </div>

                    <!-- Pemilih Titik Koordinat Peta Interaktif -->
                    <div class="form-group">
                        <label class="form-label">
                            Tandai Titik Lokasi pada Peta <span class="req">*</span>
                        </label>
                        <span class="form-hint" style="margin-bottom: 8px;">
                            Klik pada peta atau geser penanda merah untuk menentukan titik lokasi kerusakan secara akurat.
                        </span>

                        <div class="picker-map-wrapper">
                            <div id="pickerMap"></div>
                            <div class="picker-toolbar">
                                <div class="coords-display">
                                    <span>Lat: <strong id="latDisplay"><?= !empty($latitude) ? htmlspecialchars($latitude) : '-5.147665'; ?></strong></span>
                                    <span>Lng: <strong id="lngDisplay"><?= !empty($longitude) ? htmlspecialchars($longitude) : '119.432732'; ?></strong></span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnCurrentLocation">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polygon points="12 8 8 12 12 16 16 12 12 8"></polygon>
                                    </svg>
                                    <span>Gunakan Lokasi Saya</span>
                                </button>
                            </div>
                        </div>

                        <!-- Input Tersembunyi Koordinat -->
                        <input type="hidden" name="latitude" id="latitude" value="<?= htmlspecialchars($latitude); ?>">
                        <input type="hidden" name="longitude" id="longitude" value="<?= htmlspecialchars($longitude); ?>">
                    </div>

                    <!-- Tombol Submit Kirim Laporan -->
                    <div style="margin-top: 36px; display: flex; align-items: center; justify-content: flex-end; gap: 14px; border-top: 1px solid var(--border-color); padding-top: 24px;">
                        <a href="index.php" class="btn btn-outline-admin">Batal</a>
                        <button type="submit" class="btn btn-primary btn-lg" id="btnSubmitReport">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                            <span>Kirim Laporan Pengaduan</span>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Skrip JavaScript Pemilih Peta & Interaktivitas Form -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Inisialisasi Peta Leaflet untuk Pemilih Titik Koordinat
    const DEFAULT_LAT = <?= !empty($latitude) ? (float)$latitude : -5.147665; ?>;
    const DEFAULT_LNG = <?= !empty($longitude) ? (float)$longitude : 119.432732; ?>;
    const hasInitialCoords = <?= !empty($latitude) && !empty($longitude) ? 'true' : 'false'; ?>;

    const pickerMapEl = document.getElementById('pickerMap');
    if (pickerMapEl) {
        const pickerMap = L.map('pickerMap', {
            center: [DEFAULT_LAT, DEFAULT_LNG],
            zoom: hasInitialCoords ? 15 : 13,
            scrollWheelZoom: true
        });

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors &bull; MAKASSAR AMAN'
        }).addTo(pickerMap);

        // Marker draggable kustom
        const pinIcon = L.divIcon({
            className: 'custom-pin-container',
            html: `<div class="custom-pin" style="background: #ef4444;">
                       <div class="custom-pin-inner">&#8226;</div>
                   </div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 32]
        });

        let marker = null;

        function updateCoordinates(lat, lng) {
            const formattedLat = parseFloat(lat).toFixed(8);
            const formattedLng = parseFloat(lng).toFixed(8);

            document.getElementById('latitude').value = formattedLat;
            document.getElementById('longitude').value = formattedLng;
            document.getElementById('latDisplay').textContent = formattedLat;
            document.getElementById('lngDisplay').textContent = formattedLng;
        }

        function setMarkerLocation(lat, lng) {
            if (!marker) {
                marker = L.marker([lat, lng], {
                    icon: pinIcon,
                    draggable: true
                }).addTo(pickerMap);

                marker.on('dragend', function (event) {
                    const position = event.target.getLatLng();
                    updateCoordinates(position.lat, position.lng);
                });
            } else {
                marker.setLatLng([lat, lng]);
            }
            updateCoordinates(lat, lng);
        }

        // Jika sudah ada koordinat (misal dari submit sebelumnya), pasang marker
        if (hasInitialCoords) {
            setMarkerLocation(DEFAULT_LAT, DEFAULT_LNG);
        }

        // Klik pada peta untuk memilih titik lokasi
        pickerMap.on('click', function (e) {
            setMarkerLocation(e.latlng.lat, e.latlng.lng);
        });

        // Tombol Gunakan Lokasi Saya Saat Ini (Geolocation API)
        const btnCurrentLoc = document.getElementById('btnCurrentLocation');
        if (btnCurrentLoc && navigator.geolocation) {
            btnCurrentLoc.addEventListener('click', function () {
                btnCurrentLoc.disabled = true;
                btnCurrentLoc.innerHTML = `<span>Mendeteksi Lokasi...</span>`;

                navigator.geolocation.getCurrentPosition(
                    function (pos) {
                        const curLat = pos.coords.latitude;
                        const curLng = pos.coords.longitude;

                        pickerMap.setView([curLat, curLng], 16);
                        setMarkerLocation(curLat, curLng);

                        btnCurrentLoc.disabled = false;
                        btnCurrentLoc.innerHTML = `<span>Lokasi Ditemukan</span>`;
                        setTimeout(() => {
                            btnCurrentLoc.innerHTML = `<span>Gunakan Lokasi Saya</span>`;
                        }, 2500);
                    },
                    function (err) {
                        btnCurrentLoc.disabled = false;
                        btnCurrentLoc.innerHTML = `<span>Gunakan Lokasi Saya</span>`;
                        alert('Tidak dapat mendeteksi lokasi otomatis. Silakan klik langsung pada peta untuk menandai titik.');
                    },
                    { enableHighAccuracy: true, timeout: 8000 }
                );
            });
        }
    }

    // 2. Pratinjau Foto Bukti Upload
    const fotoInput = document.getElementById('fotoInput');
    const imagePreview = document.getElementById('imagePreview');
    const previewWrapper = document.getElementById('previewWrapper');
    const uploadTextMain = document.getElementById('uploadTextMain');
    const fileNameBadge = document.getElementById('fileNameBadge');

    if (fotoInput) {
        fotoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                // Periksa batas ukuran (5 MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Ukuran file foto melebihi 5 MB. Silakan pilih foto dengan ukuran lebih kecil.');
                    this.value = '';
                    previewWrapper.style.display = 'none';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                    previewWrapper.style.display = 'block';
                    uploadTextMain.textContent = 'Ganti Foto Terpilih';
                    fileNameBadge.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
                };
                reader.readAsDataURL(file);
            } else {
                previewWrapper.style.display = 'none';
                uploadTextMain.textContent = 'Pilih atau Seret Foto ke Sini';
            }
        });
    }

    // 3. Validasi Form Sebelum Kirim
    const reportForm = document.getElementById('reportForm');
    if (reportForm) {
        reportForm.addEventListener('submit', function (e) {
            const latVal = document.getElementById('latitude').value;
            const lngVal = document.getElementById('longitude').value;

            if (!latVal || !lngVal) {
                e.preventDefault();
                alert('Harap tentukan titik lokasi permasalahan dengan mengklik pada peta!');
                document.getElementById('pickerMap').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

            const btnSubmit = document.getElementById('btnSubmitReport');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = `<span>Mengirim Laporan...</span>`;
        });
    }
});

// Fungsi Salin Kode Laporan ke Clipboard
function copyTicketCode() {
    const codeEl = document.getElementById('ticketCode');
    const btnCopy = document.getElementById('btnCopyCode');
    if (codeEl) {
        const text = codeEl.textContent.trim();
        navigator.clipboard.writeText(text).then(function () {
            btnCopy.textContent = 'Tersalin!';
            btnCopy.style.background = '#15803d';
            btnCopy.style.color = '#ffffff';
            setTimeout(function () {
                btnCopy.textContent = 'Salin Kode';
                btnCopy.style.background = '#ffffff';
                btnCopy.style.color = '#15803d';
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

