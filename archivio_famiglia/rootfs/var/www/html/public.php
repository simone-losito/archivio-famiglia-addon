<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

$token = trim((string)($_GET['t'] ?? ''));

if ($token === '' || !preg_match('/^[a-f0-9]{48}$/', $token)) {
    http_response_code(400);
    exit(t('invalid_link'));
}

$stmt = $conn->prepare("SELECT * FROM share_links WHERE token = ? AND expires_at > NOW() LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$link = $stmt->get_result()->fetch_assoc();

if (!$link) {
    http_response_code(404);
    exit(t('expired_or_invalid_link'));
}

$category = safeCategory((string)$link['categoria']);
$file = safeFilename((string)$link['nome_archivio']);

$path = UPLOAD_DIR . '/' . $category . '/' . $file;

if (!is_file($path)) {
    http_response_code(404);
    exit(t('file_not_found'));
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

$types = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
];

header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: inline; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($path));

readfile($path);
exit;
