<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireLogin();

function uploadRedirect(string $message): void
{
    header("Location: " . urlWithLang('index.php?msg=' . urlencode($message)));
    exit;
}

function uploadErrorMessage(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE => t('upload_error_too_large'),
        UPLOAD_ERR_PARTIAL => t('upload_error_partial'),
        UPLOAD_ERR_NO_FILE => t('upload_error_no_file'),
        UPLOAD_ERR_NO_TMP_DIR => t('upload_error_no_tmp_dir'),
        UPLOAD_ERR_CANT_WRITE => t('upload_error_cant_write'),
        UPLOAD_ERR_EXTENSION => t('upload_error_extension'),
        default => t('upload_error_unknown'),
    };
}

function hasUploadedFile(string $field): bool
{
    return isset($_FILES[$field]) && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

function nextDocName(string $category, string $ext): string
{
    $category = safeCategory($category);
    $ext = strtolower(trim($ext));

    $dir = UPLOAD_DIR . '/' . $category;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        uploadRedirect(t('upload_save_error'));
    }

    $max = 0;
    $files = scandir($dir);

    if (is_array($files)) {
        foreach (array_diff($files, ['.', '..']) as $f) {
            if (preg_match('/^DOC-(\d+)/', $f, $m)) {
                $max = max($max, (int)$m[1]);
            }
        }
    }

    return 'DOC-' . str_pad($max + 1, 4, '0', STR_PAD_LEFT) . ($ext !== '' ? '.' . $ext : '');
}

function detectMime(string $tmpPath): string
{
    if (!is_file($tmpPath)) {
        return '';
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if (!$finfo) {
        return '';
    }

    $mime = finfo_file($finfo, $tmpPath);
    finfo_close($finfo);

    return is_string($mime) ? strtolower($mime) : '';
}

function mimeMatchesExtension(string $mime, string $ext): bool
{
    if ($mime === '') {
        return true;
    }

    $map = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'txt'  => ['text/plain', 'text/x-php'],
        'doc'  => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls'  => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    ];

    return isset($map[$ext]) && in_array($mime, $map[$ext], true);
}

function cleanText(string $value, int $maxLength): string
{
    $value = trim($value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLength);
    }

    return substr($value, 0, $maxLength);
}

$uploadField = null;

if (hasUploadedFile('file_foto')) {
    $uploadField = 'file_foto';
} elseif (hasUploadedFile('file')) {
    $uploadField = 'file';
}

if ($uploadField === null) {
    uploadRedirect(t('no_file_or_photo_selected'));
}

$file = $_FILES[$uploadField];

$errorCode = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($errorCode !== UPLOAD_ERR_OK) {
    uploadRedirect(uploadErrorMessage($errorCode));
}

$tmpPath = (string)($file['tmp_name'] ?? '');
if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
    uploadRedirect(t('empty_or_invalid_file'));
}

$fileSize = (int)($file['size'] ?? 0);
if ($fileSize <= 0) {
    uploadRedirect(t('empty_or_invalid_file'));
}

if ($fileSize > MAX_UPLOAD_SIZE) {
    uploadRedirect(t('upload_error_too_large'));
}

$categories = getCategories();
$category = safeCategory((string)($_POST['category'] ?? 'altro'));

if (!isset($categories[$category])) {
    $category = isset($categories['altro']) ? 'altro' : safeCategory($category);
}

$nomeOriginale = safeFilename((string)($file['name'] ?? ''));

if ($nomeOriginale === 'file') {
    $nomeOriginale = $uploadField === 'file_foto' ? 'foto_documento.jpg' : 'documento';
}

$ext = strtolower(pathinfo($nomeOriginale, PATHINFO_EXTENSION));

if ($uploadField === 'file_foto' && $ext === '') {
    $ext = 'jpg';
    $nomeOriginale .= '.jpg';
}

if ($ext === '') {
    uploadRedirect(t('file_without_extension_not_allowed'));
}

if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
    uploadRedirect(t('file_extension_not_allowed') . ': ' . $ext);
}

$mime = detectMime($tmpPath);

if ($mime !== '' && !in_array($mime, ALLOWED_MIME_TYPES, true)) {
    uploadRedirect(t('file_type_not_allowed') . ': ' . $mime);
}

if (!mimeMatchesExtension($mime, $ext)) {
    uploadRedirect(t('file_type_not_allowed') . ': ' . $mime);
}

if ($uploadField === 'file_foto' && ($mime === '' || !str_starts_with($mime, 'image/'))) {
    uploadRedirect(t('photo_must_be_valid_image'));
}

$titolo = cleanText((string)($_POST['titolo'] ?? ''), 180);

if ($titolo === '') {
    $titolo = cleanText(pathinfo($nomeOriginale, PATHINFO_FILENAME), 180);
}

if ($titolo === '') {
    $titolo = t('document');
}

$stmt = $conn->prepare("SELECT id FROM documenti WHERE titolo = ? LIMIT 1");
if (!$stmt) {
    uploadRedirect(t('upload_save_error'));
}
$stmt->bind_param("s", $titolo);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($exists) {
    uploadRedirect(t('document_name_already_exists'));
}

$dir = UPLOAD_DIR . '/' . $category;
if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    uploadRedirect(t('upload_save_error'));
}

if (!is_writable($dir)) {
    uploadRedirect(t('upload_save_error'));
}

$nomeArchivio = nextDocName($category, $ext);
$dest = $dir . '/' . $nomeArchivio;

if (file_exists($dest)) {
    uploadRedirect(t('upload_save_error'));
}

$note = cleanText((string)($_POST['note'] ?? ''), 2000);
$tags = cleanText((string)($_POST['tags'] ?? ''), 500);
$dataDocumento = trim((string)($_POST['data_documento'] ?? ''));

if ($dataDocumento !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataDocumento)) {
    $dataDocumento = '';
}

if ($dataDocumento === '') {
    $dataDocumento = null;
}

if ($uploadField === 'file_foto') {
    $tags = trim($tags . ($tags !== '' ? ', ' : '') . 'foto, smartphone');
    $note = trim($note . ($note !== '' ? "\n" : '') . t('smartphone_photo_note'));
}

if (!move_uploaded_file($tmpPath, $dest)) {
    uploadRedirect(t('upload_save_error'));
}

chmod($dest, 0664);

$stmt = $conn->prepare("
    INSERT INTO documenti
    (nome_archivio, nome_originale, titolo, categoria, note, tags, data_documento)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    @unlink($dest);
    uploadRedirect(t('upload_save_error'));
}

$stmt->bind_param("sssssss", $nomeArchivio, $nomeOriginale, $titolo, $category, $note, $tags, $dataDocumento);

if (!$stmt->execute()) {
    @unlink($dest);
    uploadRedirect(t('upload_save_error'));
}

$stmt->close();

$msg = ($uploadField === 'file_foto')
    ? t('photo_document_uploaded') . ': ' . $titolo
    : t('file_uploaded') . ': ' . $titolo;

uploadRedirect($msg);
