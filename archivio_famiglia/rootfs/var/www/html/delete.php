<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireLogin();

$category = safeCategory($_GET['category'] ?? '');
$file = basename($_GET['file'] ?? '');
$path = UPLOAD_DIR . '/' . $category . '/' . $file;

if (is_file($path)) {
    unlink($path);
}

$stmt = $conn->prepare("DELETE FROM documenti WHERE categoria = ? AND nome_archivio = ?");
$stmt->bind_param("ss", $category, $file);
$stmt->execute();

header("Location: " . urlWithLang("index.php?msg=" . urlencode(t('document_deleted'))));
exit;
