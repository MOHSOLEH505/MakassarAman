<?php
// includes/footer.php - Footer Layout Utama MAKASSAR AMAN
if (!isset($base_url)) {
    $base_url = "./";
}
?>
    <!-- Footer Aplikasi -->
    <footer class="footer">
        <div class="container">
            <div class="footer-top">
                <!-- Kolom 1: Identitas & Deskripsi -->
                <div class="footer-col footer-col-about">
                    <div class="footer-brand">
                        <div class="brand-logo footer-logo">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L3 7V12C3 17.52 6.84 22.74 12 24C17.16 22.74 21 17.52 21 12V7L12 2Z" fill="#38bdf8"/>
                                <path d="M10 15.5L6.5 12L7.91 10.59L10 12.67L16.09 6.58L17.5 8L10 15.5Z" fill="#0f172a"/>
                            </svg>
                        </div>
                        <span class="footer-brand-title">MAKASSAR<span>AMAN</span></span>
                    </div>
                    <p class="footer-desc">
                        Sistem Informasi Pemetaan dan Pelaporan Permasalahan Infrastruktur Berbasis Web di Kota Makassar. Wadah aspirasi warga untuk mewujudkan infrastruktur kota yang aman, nyaman, dan terawat.
                    </p>
                    <div class="footer-badge">
                        <span>Portal Pelayanan Publik &bull; Pemerintah Kota Makassar</span>
                    </div>
                </div>

                <!-- Kolom 2: Navigasi Cepat -->
                <div class="footer-col">
                    <h4 class="footer-heading">Navigasi Halaman</h4>
                    <ul class="footer-nav-list">
                        <li><a href="<?= $base_url; ?>index.php">Beranda Utama</a></li>
                        <li><a href="<?= $base_url; ?>peta.php">Peta Sebaran Laporan</a></li>
                        <li><a href="<?= $base_url; ?>buat-laporan.php">Buat Laporan Baru</a></li>
                        <li><a href="<?= $base_url; ?>cek-status.php">Cek Status Pengaduan</a></li>
                        <li><a href="<?= $base_url; ?>statistik.php">Statistik Infrastruktur</a></li>
                    </ul>
                </div>

                <!-- Kolom 3: Kategori Layanan -->
                <div class="footer-col">
                    <h4 class="footer-heading">Kategori Pengaduan</h4>
                    <ul class="footer-nav-list">
                        <li><a href="<?= $base_url; ?>buat-laporan.php?kategori=1">Jalan Rusak & Berlubang</a></li>
                        <li><a href="<?= $base_url; ?>buat-laporan.php?kategori=2">Lampu Jalan Mati (PJU)</a></li>
                        <li><a href="<?= $base_url; ?>buat-laporan.php?kategori=3">Genangan Air & Banjir</a></li>
                        <li><a href="<?= $base_url; ?>buat-laporan.php?kategori=4">Saluran Drainase Tersumbat</a></li>
                    </ul>
                </div>

                <!-- Kolom 4: Akses Khusus -->
                <div class="footer-col">
                    <h4 class="footer-heading">Akses Petugas</h4>
                    <p class="footer-meta-text">Hanya untuk administrator dan dinas terkait pengelola laporan.</p>
                    <a href="<?= $base_url; ?>admin/login.php" class="btn btn-sm btn-footer-admin">
                        Masuk Panel Admin &rarr;
                    </a>
                </div>
            </div>

            <!-- Footer Bottom / Hak Cipta -->
            <div class="footer-bottom">
                <p>&copy; <?= date('Y'); ?> <strong>MAKASSAR AMAN</strong>. Sistem Informasi Pelayanan Infrastruktur Kota Makassar.</p>
                <p class="footer-tech">Dibangun dengan PHP Native &bull; MySQL &bull; Laragon</p>
            </div>
        </div>
    </footer>
</div><!-- /.site-wrapper -->

<?php if (!empty($enable_leaflet)): ?>
<!-- Leaflet JavaScript -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<?php endif; ?>

<?php if (!empty($enable_chartjs)): ?>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<?php endif; ?>

<!-- Main JavaScript -->
<script src="<?= $base_url; ?>assets/js/script.js"></script>
</body>
</html>

