<?php
// includes/header.php - Header Layout Utama MAKASSAR AMAN
if (!isset($page_title)) {
    $page_title = "MAKASSAR AMAN - Sistem Informasi Pemetaan & Pelaporan Infrastruktur Kota Makassar";
}
if (!isset($base_url)) {
    $base_url = "./";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MAKASSAR AMAN - Sistem Informasi Pemetaan dan Pelaporan Permasalahan Infrastruktur Berbasis Web di Kota Makassar. Layanan E-Government tanggap infrastruktur publik.">
    <title><?= htmlspecialchars($page_title); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= $base_url; ?>assets/images/favicon.svg">
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <?php if (!empty($enable_leaflet)): ?>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <?php endif; ?>

    <!-- Stylesheet Utama (Pure CSS) -->
    <link rel="stylesheet" href="<?= $base_url; ?>assets/css/style.css">
</head>
<body>
<div class="site-wrapper">
