<?php
// status.php - Pengalihan (Redirect) ke cek-status.php
$query_string = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: cek-status.php" . $query_string, true, 301);
exit;

