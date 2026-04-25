<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireLogin();

$category = safeCategory($_GET['category'] ?? '');
$file = basename($_GET['file'] ?? '');
$path = UPLOAD_DIR . '/' . $category . '/' . $file;

if (!is_file($path)) exit('File non trovato');

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
