<?php
// lapor.php - Pengalihan (Redirect) ke buat-laporan.php
$query_string = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: buat-laporan.php" . $query_string, true, 301);
exit;

