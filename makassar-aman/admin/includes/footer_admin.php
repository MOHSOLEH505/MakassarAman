<?php
// admin/includes/footer_admin.php - Footer Layout Panel Administrator
?>
    <!-- Footer Khusus Admin -->
    <footer style="margin-top: auto; padding: 20px 0; border-top: 1px solid var(--border-color); background: #ffffff; text-align: center; font-size: 12.5px; color: var(--text-muted);">
        <div class="container">
            &copy; <?= date('Y'); ?> <strong>MAKASSAR AMAN</strong> &bull; Panel Manajemen Pengaduan Infrastruktur Pemerintah Kota Makassar
        </div>
    </footer>
</div><!-- /.site-wrapper -->

<?php if (!empty($enable_leaflet)): ?>
<!-- Leaflet JavaScript -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<?php endif; ?>

<!-- Main JavaScript -->
<script src="../assets/js/script.js"></script>
</body>
</html>

