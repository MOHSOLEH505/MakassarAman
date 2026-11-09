<?php
// config/database.php - Konfigurasi Koneksi Database MySQL (Laragon) menggunakan PDO

$db_host = 'localhost';
$db_port = 3306;
$db_name = 'makassar_aman';
$db_user = 'root';
$db_pass = ''; // Konfigurasi default Laragon menggunakan password kosong
$charset = 'utf8mb4';

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Menangani error menggunakan exception
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Hasil query otomatis berbentuk associative array
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Menggunakan native prepared statements
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Catat pesan error teknis ke log internal server tanpa mengeksposnya ke pengguna umum
    error_log("Database Connection Error: " . $e->getMessage());

    // Tampilkan pesan kesalahan yang aman dan ramah pengguna
    http_response_code(500);
    die("
    <!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Koneksi Database Gagal - MAKASSAR AMAN</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                background-color: #f8fafc;
                color: #1e293b;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                padding: 24px;
                box-sizing: border-box;
            }
            .card {
                max-width: 480px;
                width: 100%;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 32px 24px;
                text-align: center;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            }
            .icon {
                width: 48px;
                height: 48px;
                border-radius: 50%;
                background: #fee2e2;
                color: #ef4444;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                font-weight: bold;
                margin: 0 auto 16px;
            }
            h1 {
                font-size: 20px;
                font-weight: 700;
                color: #0f172a;
                margin-bottom: 8px;
            }
            p {
                font-size: 14px;
                color: #64748b;
                line-height: 1.6;
                margin: 0;
            }
        </style>
    </head>
    <body>
        <div class='card'>
            <div class='icon'>!</div>
            <h1>Gagal Terhubung ke Basis Data</h1>
            <p>Terjadi kendala saat menghubungi server database. Pastikan server MySQL pada Laragon dalam status <strong>Start</strong> dan coba muat ulang halaman.</p>
        </div>
    </body>
    </html>
    ");
}
