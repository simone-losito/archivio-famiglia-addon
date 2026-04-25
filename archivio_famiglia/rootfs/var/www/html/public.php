<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$token = trim($_GET['t'] ?? '');

if ($token === '') {
    exit('Link non valido');
}

$stmt = $conn->prepare("SELECT * FROM share_links WHERE token = ? AND expires_at > NOW() LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$link = $stmt->get_result()->fetch_assoc();

if (!$link) {
    exit('Link scaduto o non valido');
}

$category = basename($link['categoria']);
$file = basename($link['nome_archivio']);
$path = UPLOAD_DIR . '/' . $category . '/' . $file;

if (!is_file($path)) {
    exit('File non trovato');
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$types = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp'
];

header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: inline; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($path));

readfile($path);
exit;
