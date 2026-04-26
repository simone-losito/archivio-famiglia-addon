<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireLogin();

function uploadErrorMessage(int $code): string {
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File troppo grande. Riduci la foto o aumenta il limite upload.',
        UPLOAD_ERR_PARTIAL => 'Upload incompleto. Riprova.',
        UPLOAD_ERR_NO_FILE => 'Nessun file selezionato.',
        UPLOAD_ERR_NO_TMP_DIR => 'Cartella temporanea mancante.',
        UPLOAD_ERR_CANT_WRITE => 'Impossibile scrivere il file su disco.',
        UPLOAD_ERR_EXTENSION => 'Upload bloccato da estensione PHP.',
        default => 'Errore upload sconosciuto.'
    };
}

function nextDocName(string $category, string $ext): string {
    $dir = UPLOAD_DIR . '/' . $category;
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    $max = 0;
    foreach (array_diff(scandir($dir), ['.','..']) as $f) {
        if (preg_match('/DOC-(\d+)/', $f, $m)) {
            $max = max($max, (int)$m[1]);
        }
    }

    return 'DOC-' . str_pad($max + 1, 4, '0', STR_PAD_LEFT) . ($ext ? '.' . strtolower($ext) : '');
}

function hasUploadedFile(string $field): bool {
    return isset($_FILES[$field]) && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
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

$errorCode = (int)($_FILES[$uploadField]['error'] ?? UPLOAD_ERR_NO_FILE);
if ($errorCode !== UPLOAD_ERR_OK) {
    header("Location: index.php?msg=" . urlencode(uploadErrorMessage($errorCode)));
    exit;
}

$categories = getCategories();
$category = $_POST['category'] ?? 'altro';
if (!isset($categories[$category])) $category = 'altro';

$nomeOriginale = basename($_FILES[$uploadField]['name'] ?? '');
$ext = strtolower(pathinfo($nomeOriginale, PATHINFO_EXTENSION));

if ($uploadField === 'file_foto') {
    if ($ext === '') {
        $ext = 'jpg';
    }

    if ($nomeOriginale === '') {
        $nomeOriginale = 'foto_documento.' . $ext;
    }
}

$titolo = trim($_POST['titolo'] ?? '');

if ($titolo === '') {
    $titolo = pathinfo($nomeOriginale, PATHINFO_FILENAME);
}

/* BLOCCO DOPPIONI PER NOME DOCUMENTO */
$stmt = $conn->prepare("SELECT id FROM documenti WHERE titolo = ? LIMIT 1");
$stmt->bind_param("s", $titolo);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();

if ($exists) {
    header("Location: index.php?msg=" . urlencode("Errore: esiste già un documento con questo nome"));
    exit;
}

$dir = UPLOAD_DIR . '/' . $category;
if (!is_dir($dir)) mkdir($dir, 0775, true);

$nomeArchivio = nextDocName($category, $ext);
$dest = $dir . '/' . $nomeArchivio;

$note = trim($_POST['note'] ?? '');
$tags = trim($_POST['tags'] ?? '');
$dataDocumento = trim($_POST['data_documento'] ?? '');
if ($dataDocumento === '') $dataDocumento = null;

if ($uploadField === 'file_foto') {
    $tags = trim($tags . ($tags !== '' ? ', ' : '') . 'foto, smartphone');
    $note = trim($note . ($note !== '' ? "\n" : '') . 'Documento acquisito tramite fotocamera smartphone.');
}

if (move_uploaded_file($_FILES[$uploadField]['tmp_name'], $dest)) {
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
}

header("Location: index.php?msg=" . urlencode("Errore upload: impossibile salvare il file"));
exit;
