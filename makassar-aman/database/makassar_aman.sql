-- ====================================================================
-- SISTEM INFORMASI MAKASSAR AMAN
-- Skrip Inisialisasi Database dan Tabel
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ====================================================================

-- 1. Pembuatan Basis Data
CREATE DATABASE IF NOT EXISTS `makassar_aman`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `makassar_aman`;

-- --------------------------------------------------------------------
-- 2. Tabel Admin
-- Fungsi: Menyimpan kredensial dan data otentikasi administrator sistem.
-- Password disimpan dalam bentuk hash aman menggunakan password_hash() PHP.
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin` (
    `id_admin` INT AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 3. Tabel Kategori
-- Fungsi: Menyimpan master data kategori permasalahan infrastruktur
-- yang dapat dilaporkan oleh warga.
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kategori` (
    `id_kategori` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kategori` VARCHAR(100) NOT NULL,
    `deskripsi` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 4. Tabel Laporan
-- Fungsi: Menyimpan informasi pelaporan infrastruktur dari warga,
-- termasuk data lokasi spasial (latitude & longitude), alamat,
-- foto bukti, kode tiket unik pelaporan, dan status terkini.
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `laporan` (
    `id_laporan` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_laporan` VARCHAR(50) NOT NULL UNIQUE,
    `id_kategori` INT NOT NULL,
    `deskripsi` TEXT NOT NULL,
    `foto` VARCHAR(255) DEFAULT NULL,
    `alamat` TEXT NOT NULL,
    `kecamatan` VARCHAR(100) NOT NULL,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `status` ENUM('Menunggu', 'Diproses', 'Selesai') NOT NULL DEFAULT 'Menunggu',
    `tanggal_laporan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_laporan_kategori`
        FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 5. Tabel Riwayat Status
-- Fungsi: Menyimpan log jejak perubahan status laporan dari waktu ke
-- waktu beserta catatan/tanggapan dari admin yang menangani.
-- Kolom id_admin boleh bernilai NULL (misalnya saat status awal 'Menunggu'
-- pertama kali terbuat secara otomatis oleh sistem).
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `riwayat_status` (
    `id_riwayat` INT AUTO_INCREMENT PRIMARY KEY,
    `id_laporan` INT NOT NULL,
    `id_admin` INT DEFAULT NULL,
    `status` ENUM('Menunggu', 'Diproses', 'Selesai') NOT NULL,
    `tanggapan` TEXT DEFAULT NULL,
    `tanggal_perubahan` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_riwayat_laporan`
        FOREIGN KEY (`id_laporan`) REFERENCES `laporan` (`id_laporan`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT `fk_riwayat_admin`
        FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 6. Data Awal Kategori Permasalahan Infrastruktur
-- Memasukkan 4 kategori utama yang ditentukan ke dalam tabel kategori.
-- --------------------------------------------------------------------
INSERT INTO `kategori` (`nama_kategori`, `deskripsi`) VALUES
('Jalan Rusak', 'Laporan mengenai jalan berlubang, aspal retak, amblas, atau kerusakan jalan lainnya yang membahayakan pengguna jalan.'),
('Lampu Jalan Mati', 'Laporan mengenai fasilitas Penerangan Jalan Umum (PJU) yang padam, rusak, berkedip, atau tiang lampu roboh.'),
('Genangan Air', 'Laporan genangan air di ruas jalan atau pemukiman yang mengganggu lalu lintas akibat drainase tidak memadai saat hujan.'),
('Saluran Tersumbat', 'Laporan saluran air, gorong-gorong, atau selokan yang tersumbat oleh sampah, sedimen lumpur, atau material bangunan.');

