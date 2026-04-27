<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';
require_once __DIR__ . '/core/ocr.php';

requireLogin();

function uploadRedirect(string $message): void
{
    header("Location: " . urlWithLang('index.php?msg=' . urlencode($message)));
    exit;
}

// (codice invariato sopra...)

if (!move_uploaded_file($tmpPath, $dest)) {
    uploadRedirect(t('upload_save_error'));
}

chmod($dest, 0664);

// OCR SOLO SU IMMAGINI
$ocrText = '';
if (in_array($ext, ['jpg','jpeg','png','webp'], true)) {
    ensureOcrColumns();
    $ocrText = runOcrOnImage($dest);
}

$stmt = $conn->prepare("\n    INSERT INTO documenti\n    (nome_archivio, nome_originale, titolo, categoria, note, tags, data_documento, ocr_text)\n    VALUES (?, ?, ?, ?, ?, ?, ?, ?)\n");

if (!$stmt) {
    @unlink($dest);
    uploadRedirect(t('upload_save_error'));
}

$stmt->bind_param("ssssssss", $nomeArchivio, $nomeOriginale, $titolo, $category, $note, $tags, $dataDocumento, $ocrText);

if (!$stmt->execute()) {
    @unlink($dest);
    uploadRedirect(t('upload_save_error'));
}

$stmt->close();

$msg = ($uploadField === 'file_foto')
    ? t('photo_document_uploaded') . ': ' . $titolo
    : t('file_uploaded') . ': ' . $titolo;

uploadRedirect($msg);
