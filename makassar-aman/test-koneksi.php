<?php
// test-koneksi.php - File Pengujian Koneksi Database MAKASSAR AMAN
// File ini dibuat sementara untuk memverifikasi koneksi PHP Native ke MySQL Laragon

require_once __DIR__ . '/config/database.php';

// Jika sampai di baris ini, berarti PDO berhasil terhubung tanpa melempar Exception
$status_koneksi = true;
$kategori_list = [];
$tabel_list = [];

try {
    // 1. Ambil daftar tabel yang tersedia di database makassar_aman
    $stmtTabel = $pdo->query("SHOW TABLES");
    $tabel_list = $stmtTabel->fetchAll(PDO::FETCH_COLUMN);

    // 2. Ambil data dari tabel kategori untuk memastikan hak akses baca query
    $stmtKategori = $pdo->query("SELECT id_kategori, nama_kategori, deskripsi FROM kategori ORDER BY id_kategori ASC");
    $kategori_list = $stmtKategori->fetchAll();
} catch (PDOException $e) {
    $status_koneksi = false;
    error_log("Test Query Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Koneksi Database - MAKASSAR AMAN</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            margin: 0;
            padding: 32px 16px;
            display: flex;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 680px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 32px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .header {
            margin-bottom: 24px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 16px;
        }

        .title {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .subtitle {
            font-size: 14px;
            color: #64748b;
        }

        .alert-success {
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 10px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .alert-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #10b981;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }

        .alert-text h4 {
            margin: 0 0 2px 0;
            font-size: 15px;
            color: #065f46;
        }

        .alert-text p {
            margin: 0;
            font-size: 13px;
            color: #047857;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            margin: 20px 0 12px 0;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .info-table th, .info-table td {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            width: 35%;
        }

        .badge-pill {
            display: inline-block;
            background: #e2e8f0;
            color: #334155;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            margin: 2px 4px 2px 0;
        }

        .category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .category-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 8px;
        }

        .category-item strong {
            display: block;
            font-size: 14px;
            color: #0284c7;
            margin-bottom: 4px;
        }

        .category-item span {
            font-size: 13px;
            color: #64748b;
        }

        .footer-note {
            margin-top: 24px;
            padding: 12px 16px;
            border-left: 4px solid #0284c7;
            background: #f0f9ff;
            font-size: 12px;
            color: #0369a1;
            border-radius: 0 8px 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">Uji Koneksi Database PHP Native</h1>
            <p class="subtitle">Aplikasi MAKASSAR AMAN &bull; Lingkungan Lokal Laragon</p>
        </div>

        <div class="alert-success">
            <div class="alert-icon">&#10003;</div>
            <div class="alert-text">
                <h4>Koneksi Berhasil!</h4>
                <p>PHP Native berhasil terhubung ke database <strong>makassar_aman</strong> melalui PDO.</p>
            </div>
        </div>

        <div class="section-title">Detail Konfigurasi Koneksi</div>
        <table class="info-table">
            <tr>
                <th>Driver & Mekanisme</th>
                <td>PHP Data Objects (PDO MySQL)</td>
            </tr>
            <tr>
                <th>Host & Port</th>
                <td>localhost : 3306</td>
            </tr>
            <tr>
                <th>Nama Database</th>
                <td><strong>makassar_aman</strong></td>
            </tr>
            <tr>
                <th>Charset</th>
                <td>utf8mb4</td>
            </tr>
            <tr>
                <th>Error Mode</th>
                <td>PDO::ERRMODE_EXCEPTION (Aktif)</td>
            </tr>
            <tr>
                <th>Tabel Terdeteksi</th>
                <td>
                    <?php if (!empty($tabel_list)): ?>
                        <?php foreach ($tabel_list as $tbl): ?>
                            <span class="badge-pill"><?= htmlspecialchars($tbl); ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <em style="color: #94a3b8;">Belum ada tabel atau gagal dimuat.</em>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <div class="section-title">Verifikasi Data Awal (Tabel Kategori)</div>
        <?php if (!empty($kategori_list)): ?>
            <ul class="category-list">
                <?php foreach ($kategori_list as $kat): ?>
                    <li class="category-item">
                        <strong>#<?= htmlspecialchars($kat['id_kategori']); ?> - <?= htmlspecialchars($kat['nama_kategori']); ?></strong>
                        <span><?= htmlspecialchars($kat['deskripsi'] ?? 'Tidak ada deskripsi.'); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="font-size: 13px; color: #64748b;">Tabel kategori belum memiliki data.</p>
        <?php endif; ?>

        <div class="footer-note">
            <strong>Catatan Pengembang:</strong> File <code>test-koneksi.php</code> ini bersifat sementara untuk tahap pengujian konektivitas. File ini dapat dihapus atau dinonaktifkan ketika seluruh modul aplikasi selesai dibangun.
        </div>
    </div>
</body>
</html>

