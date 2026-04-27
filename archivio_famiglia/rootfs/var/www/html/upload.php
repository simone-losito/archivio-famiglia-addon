<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireLogin();

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
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
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

    return is_string($mime) ? $mime : '';
}

$uploadField = null;

if (hasUploadedFile('file_foto')) {
    $uploadField = 'file_foto';
} elseif (hasUploadedFile('file')) {
    $uploadField = 'file';
}

if ($uploadField === null) {
    header("Location: " . urlWithLang('index.php?msg=' . urlencode(t('no_file_or_photo_selected'))));
    exit;
}

$file = $_FILES[$uploadField];

$errorCode = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($errorCode !== UPLOAD_ERR_OK) {
    header("Location: " . urlWithLang('index.php?msg=' . urlencode(uploadErrorMessage($errorCode))));
    exit;
}

$fileSize = (int)($file['size'] ?? 0);
if ($fileSize <= 0) {
    header("Location: " . urlWithLang('index.php?msg=' . urlencode(t('empty_or_invalid_file'))));
    exit;
}

if ($fileSize > MAX_UPLOAD_SIZE) {
    header("Location: " . urlWithLang('index.php?msg=' . urlencode(t('upload_error_too_large'))));
    exit;
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
    header("Location: " . urlWithLang('index.php?msg=' . urlencode(t('file_without_extension_not_allowed'))));
    exit;
}

if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
    header("Location: " . urlWithLang('index.php?msg=' . urlencode(t('file_extension_not_allowed') . ': ' . $ext)));
    exit;
}

$tmpPath = (string)($file['tmp_name'] ?? '');
$mime = detectMime($tmpPath);

if ($mime !== '' && !in_array($mime, ALLOWED_MIME_TYPES, true)) {
    header("Location: " . urlWithLang('index.php?msg=' . urlencode(t('file_type_not_allowed') . ': ' . $mime)));
    exit;
}

if ($uploadField === 'file_foto' && !str_starts_with($mime, 'image/')) {
    header("Location: " . urlWithLang('index.php?msg=' . urlencode(t('photo_must_be_valid_image'))));
    exit;
}

$titolo = trim((string)($_POST['titolo'] ?? ''));

if ($titolo === '') {
    $titolo = pathinfo($nomeOriginale, PATHINFO_FILENAME);
}

$stmt = $conn->prepare("SELECT id FROM documenti WHERE titolo = ? LIMIT 1");
$stmt->bind_param("s", $titolo);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();

if ($exists) {
    header("Location: " . urlWithLang('index.php?msg=' . urlencode(t('document_name_already_exists'))));
    exit;
}

$dir = UPLOAD_DIR . '/' . $category;
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$nomeArchivio = nextDocName($category, $ext);
$dest = $dir . '/' . $nomeArchivio;

$note = trim((string)($_POST['note'] ?? ''));
$tags = trim((string)($_POST['tags'] ?? ''));
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
    header("Location: " . urlWithLang('index.php?msg=' . urlencode(t('upload_save_error'))));
    exit;
}

chmod($dest, 0664);

$stmt = $conn->prepare("
    INSERT INTO documenti
    (nome_archivio, nome_originale, titolo, categoria, note, tags, data_documento)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("sssssss", $nomeArchivio, $nomeOriginale, $titolo, $category, $note, $tags, $dataDocumento);
$stmt->execute();

$msg = ($uploadField === 'file_foto')
    ? t('photo_document_uploaded') . ': ' . $titolo
    : t('file_uploaded') . ': ' . $titolo;

header("Location: " . urlWithLang('index.php?msg=' . urlencode($msg)));
exit;
