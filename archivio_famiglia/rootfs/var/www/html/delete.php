<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireLogin();

/* Parametri */
$category = safeCategory($_GET['category'] ?? '');
$file = basename($_GET['file'] ?? '');

/* Validazione base */
if ($category === '' || $file === '') {
    header("Location: " . urlWithLang("index.php?msg=" . urlencode(t('invalid_request'))));
    exit;
}

$path = UPLOAD_DIR . '/' . $category . '/' . $file;

/* Cancella file fisico */
if (is_file($path)) {
    @unlink($path);
}

/* Cancella record DB */
$stmt = $conn->prepare("
    DELETE FROM documenti 
    WHERE categoria = ? AND nome_archivio = ?
");
$stmt->bind_param("ss", $category, $file);
$stmt->execute();

/* Redirect sicuro */
header("Location: " . urlWithLang("index.php?msg=" . urlencode(t('document_deleted'))));
exit;
