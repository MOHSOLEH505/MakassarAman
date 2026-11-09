<?php
// admin/login.php - Halaman Masuk (Login) Panel Administrator MAKASSAR AMAN
$page_title = "Login Administrator - MAKASSAR AMAN";

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Jika admin sudah login, alihkan langsung ke dashboard admin
if (isAdminLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error_message = '';
$success_message = '';
$initial_seeded = false;

// Periksa apakah tabel admin masih kosong (Self-Healing Initial Seed)
try {
    $stmtCheck = $pdo->query("SELECT COUNT(*) FROM admin");
    $admin_count = (int)$stmtCheck->fetchColumn();

    if ($admin_count === 0) {
        // Otomatis buat akun administrator awal jika tabel masih kosong
        $default_user = 'admin';
        $default_pass = password_hash('admin123', PASSWORD_BCRYPT);
        $default_name = 'Administrator Makassar Aman';

        $stmtSeed = $pdo->prepare("INSERT INTO admin (username, password, nama, created_at) VALUES (?, ?, ?, NOW())");
        $stmtSeed->execute([$default_user, $default_pass, $default_name]);
        $initial_seeded = true;
    }
} catch (PDOException $e) {
    error_log("Error checking admin table: " . $e->getMessage());
}

// Cek apakah ada pesan logout dari parameter URL
if (isset($_GET['msg']) && $_GET['msg'] === 'logout') {
    $success_message = "Anda telah berhasil keluar dari sistem.";
}

// Tangani proses autentikasi (Metode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verifyCsrfToken($token)) {
        $error_message = "Sesi keamanan kedaluwarsa. Silakan muat ulang halaman.";
    } else {
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($username) || empty($password)) {
            $error_message = "Harap masukkan username dan password Anda.";
        } else {
            try {
                // Cari data admin berdasarkan username dengan prepared statement
                $stmt = $pdo->prepare("SELECT id_admin, nama, username, password FROM admin WHERE username = :username LIMIT 1");
                $stmt->execute([':username' => $username]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password'])) {
                    // Autentikasi berhasil: buat sesi admin yang aman
                    session_regenerate_id(true);
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id']        = (int)$admin['id_admin'];
                    $_SESSION['admin_nama']      = $admin['nama'];
                    $_SESSION['admin_username']  = $admin['username'];

                    header("Location: index.php");
                    exit;
                } else {
                    $error_message = "Username atau password yang Anda masukkan salah.";
                }
            } catch (PDOException $e) {
                error_log("Error during admin login: " . $e->getMessage());
                $error_message = "Terjadi gangguan sistem saat proses autentikasi.";
            }
        }
    }
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title); ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background-color: var(--bg-page);">
    <div class="admin-login-wrapper">
        <div class="admin-login-card">
            <!-- Header Logo & Judul -->
            <div class="admin-login-header">
                <div class="brand-logo" style="margin: 0 auto; width: 50px; height: 50px;">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L3 7V12C3 17.52 6.84 22.74 12 24C17.16 22.74 21 17.52 21 12V7L12 2Z" fill="currentColor"/>
                        <path d="M10 15.5L6.5 12L7.91 10.59L10 12.67L16.09 6.58L17.5 8L10 15.5Z" fill="#0f172a"/>
                    </svg>
                </div>
                <h1 class="admin-login-title">MAKASSAR<span>AMAN</span></h1>
                <p class="admin-login-subtitle">Masuk ke Panel Administrator Pengaduan</p>
            </div>

            <!-- Notifikasi Akun Awal Tersedia -->
            <?php if ($initial_seeded): ?>
                <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-sm); padding: 12px 14px; font-size: 12.5px; color: #166534; margin-bottom: 20px;">
                    <strong>Akun Awal Telah Dibuat:</strong><br>
                    Username: <code>admin</code> &bull; Password: <code>admin123</code>
                </div>
            <?php endif; ?>

            <!-- Notifikasi Pesan Sukses Logout -->
            <?php if (!empty($success_message)): ?>
                <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-sm); padding: 12px 14px; font-size: 13px; color: #166534; margin-bottom: 20px;">
                    <?= htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- Notifikasi Pesan Galat -->
            <?php if (!empty($error_message)): ?>
                <div class="form-alert form-alert-danger" style="margin-bottom: 20px; padding: 10px 14px; font-size: 13px;">
                    <?= htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Formulir Login -->
            <form action="login.php" method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">

                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username admin..." value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required autofocus>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password..." required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; height: 44px; font-size: 14.5px;">
                    Masuk ke Dashboard Admin &rarr;
                </button>
            </form>

            <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color); text-align: center;">
                <a href="../index.php" style="font-size: 13px; color: var(--text-muted); text-decoration: none;">
                    &larr; Kembali ke Beranda Publik
                </a>
            </div>
        </div>
    </div>
</body>
</html>

