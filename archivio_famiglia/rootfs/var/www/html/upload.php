<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireLogin();

function uploadErrorMessage(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE => 'File troppo grande. Limite massimo: 50 MB.',
        UPLOAD_ERR_PARTIAL => 'Upload incompleto. Riprova.',
        UPLOAD_ERR_NO_FILE => 'Nessun file selezionato.',
        UPLOAD_ERR_NO_TMP_DIR => 'Cartella temporanea mancante.',
        UPLOAD_ERR_CANT_WRITE => 'Impossibile scrivere il file su disco.',
        UPLOAD_ERR_EXTENSION => 'Upload bloccato da estensione PHP.',
        default => 'Errore upload sconosciuto.',
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
    header("Location: index.php?msg=" . urlencode("Nessun file o foto selezionata"));
    exit;
}

$file = $_FILES[$uploadField];

$errorCode = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($errorCode !== UPLOAD_ERR_OK) {
    header("Location: index.php?msg=" . urlencode(uploadErrorMessage($errorCode)));
    exit;
}

$fileSize = (int)($file['size'] ?? 0);
if ($fileSize <= 0) {
    header("Location: index.php?msg=" . urlencode("File vuoto o non valido"));
    exit;
}

if ($fileSize > MAX_UPLOAD_SIZE) {
    header("Location: index.php?msg=" . urlencode("File troppo grande. Limite massimo: 50 MB."));
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
    header("Location: index.php?msg=" . urlencode("File senza estensione non consentito"));
    exit;
}

if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
    header("Location: index.php?msg=" . urlencode("Estensione file non consentita: " . $ext));
    exit;
}

$tmpPath = (string)($file['tmp_name'] ?? '');
$mime = detectMime($tmpPath);

if ($mime !== '' && !in_array($mime, ALLOWED_MIME_TYPES, true)) {
    header("Location: index.php?msg=" . urlencode("Tipo file non consentito: " . $mime));
    exit;
}

if ($uploadField === 'file_foto' && !str_starts_with($mime, 'image/')) {
    header("Location: index.php?msg=" . urlencode("La foto deve essere un'immagine valida"));
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
    header("Location: index.php?msg=" . urlencode("Errore: esiste già un documento con questo nome"));
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
    $note = trim($note . ($note !== '' ? "\n" : '') . 'Documento acquisito tramite fotocamera smartphone.');
}

if (!move_uploaded_file($tmpPath, $dest)) {
    header("Location: index.php?msg=" . urlencode("Errore upload: impossibile salvare il file"));
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
    ? "Foto documento caricata: $titolo"
    : "File caricato: $titolo";

header("Location: index.php?msg=" . urlencode($msg));
exit;
