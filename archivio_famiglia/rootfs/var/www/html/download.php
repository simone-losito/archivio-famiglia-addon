<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireLogin();

$category = safeCategory($_GET['category'] ?? '');
$file = basename($_GET['file'] ?? '');
$path = UPLOAD_DIR . '/' . $category . '/' . $file;

if (!is_file($path)) {
    http_response_code(404);
    exit(t('file_not_found'));
}

/* Pulizia output buffer (evita file corrotti) */
if (ob_get_level()) {
    ob_end_clean();
}

/* Nome file sicuro (supporto UTF-8) */
$filename = basename($file);
$filenameAscii = preg_replace('/[^A-Za-z0-9\-\._]/', '_', $filename);

/* Headers robusti */
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filenameAscii . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

/* Output file */
readfile($path);
exit;
