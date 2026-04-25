<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireLogin();

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

if (empty($_FILES['file']['name'])) {
    header("Location: index.php?msg=" . urlencode("Nessun file selezionato"));
    exit;
}

$categories = getCategories();
$category = $_POST['category'] ?? 'altro';
if (!isset($categories[$category])) $category = 'altro';

$titolo = trim($_POST['titolo'] ?? '');
$nomeOriginale = $_FILES['file']['name'];

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

$ext = pathinfo($nomeOriginale, PATHINFO_EXTENSION);
$nomeArchivio = nextDocName($category, $ext);
$dest = $dir . '/' . $nomeArchivio;

$note = trim($_POST['note'] ?? '');
$tags = trim($_POST['tags'] ?? '');
$dataDocumento = trim($_POST['data_documento'] ?? '');
if ($dataDocumento === '') $dataDocumento = null;

if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
    $stmt = $conn->prepare("
        INSERT INTO documenti
        (nome_archivio, nome_originale, titolo, categoria, note, tags, data_documento)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssssss", $nomeArchivio, $nomeOriginale, $titolo, $category, $note, $tags, $dataDocumento);
    $stmt->execute();

    header("Location: index.php?msg=" . urlencode("File caricato: $titolo"));
    exit;
}

header("Location: index.php?msg=" . urlencode("Errore upload"));
exit;
