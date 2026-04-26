<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
$categories = getCategories();

$stmt = $conn->prepare("SELECT * FROM documenti WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();

if (!$doc) {
    http_response_code(404);
    exit('Documento non trovato');
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titolo = trim((string)($_POST['titolo'] ?? ''));
    $newCategory = safeCategory((string)($_POST['categoria'] ?? $doc['categoria']));

    if ($titolo === '') {
        $titolo = (string)($doc['titolo'] ?: $doc['nome_originale']);
    }

    if (!isset($categories[$newCategory])) {
        $newCategory = safeCategory((string)$doc['categoria']);
    }

    $note = trim((string)($_POST['note'] ?? ''));
    $tags = trim((string)($_POST['tags'] ?? ''));
    $dataDocumento = trim((string)($_POST['data_documento'] ?? ''));

    if ($dataDocumento !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataDocumento)) {
        $dataDocumento = '';
    }

    if ($dataDocumento === '') {
        $dataDocumento = null;
    }

    $oldCategory = safeCategory((string)$doc['categoria']);
    $nomeArchivio = safeFilename((string)$doc['nome_archivio']);

    if ($newCategory !== $oldCategory) {
        $oldPath = UPLOAD_DIR . '/' . $oldCategory . '/' . $nomeArchivio;
        $newDir = UPLOAD_DIR . '/' . $newCategory;

        if (!is_dir($newDir)) {
            mkdir($newDir, 0775, true);
        }

        $newPath = $newDir . '/' . $nomeArchivio;

        if (is_file($oldPath) && !is_file($newPath)) {
            rename($oldPath, $newPath);
        }
    }

    $stmt = $conn->prepare("
        UPDATE documenti
        SET titolo = ?, categoria = ?, note = ?, tags = ?, data_documento = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssssi", $titolo, $newCategory, $note, $tags, $dataDocumento, $id);
    $stmt->execute();

    header("Location: view.php?category=" . urlencode($newCategory) . "&file=" . urlencode($nomeArchivio));
    exit;
}

$currentCategory = safeCategory((string)$doc['categoria']);
$nomeArchivio = safeFilename((string)$doc['nome_archivio']);
$viewUrl = "view.php?category=" . urlencode($currentCategory) . "&file=" . urlencode($nomeArchivio);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Modifica documento</title>
<link rel="stylesheet" href="assets/css/archivio.css">
</head>
<body>

<div class="sidebar">
    <div class="logo">📁 Archivio</div>
    <div class="menu">
        <a href="index.php">🏠 Home</a>
        <a href="categorie.php">⚙️ Categorie</a>
        <?php if (isAdmin()): ?>
            <a href="utenti.php">👥 Utenti</a>
            <a href="backup.php">💾 Backup</a>
        <?php endif; ?>
        <a href="info.php">ℹ️ Info</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<div class="main">
    <div class="card">
        <div class="topbar">
            <div>
                <span class="badge">Documento</span>
                <h1>Modifica documento</h1>
                <p><?= h($doc['nome_originale']) ?></p>
            </div>
            <div class="toolbar">
                <a class="btn btn-secondary" href="<?= h($viewUrl) ?>">👁️ Visualizza</a>
                <a class="btn btn-secondary" href="index.php">← Home</a>
            </div>
        </div>
    </div>

    <div class="card">
        <form method="POST">
            <label>Nome documento</label>
            <input type="text" name="titolo" value="<?= h($doc['titolo'] ?: $doc['nome_originale']) ?>" required>

            <label>Categoria</label>
            <select name="categoria">
                <?php foreach ($categories as $key => $label): ?>
                    <option value="<?= h($key) ?>" <?= $currentCategory === $key ? 'selected' : '' ?>>
                        <?= h($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Data pratica</label>
            <input type="date" name="data_documento" value="<?= h($doc['data_documento'] ?? '') ?>">

            <label>Tag</label>
            <input type="text" name="tags" value="<?= h($doc['tags'] ?? '') ?>">

            <label>Note</label>
            <textarea name="note" style="min-height:130px;"><?= h($doc['note'] ?? '') ?></textarea>

            <div class="toolbar">
                <button>Salva modifiche</button>
                <a class="btn btn-secondary" href="<?= h($viewUrl) ?>">Annulla</a>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/theme.js"></script>
</body>
</html>
