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
    exit('Documento non trovato');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newCategory = $_POST['categoria'] ?? $doc['categoria'];
    if (!isset($categories[$newCategory])) {
        $newCategory = $doc['categoria'];
    }

    $note = trim($_POST['note'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $dataDocumento = trim($_POST['data_documento'] ?? '');
    if ($dataDocumento === '') $dataDocumento = null;

    if ($newCategory !== $doc['categoria']) {
        $oldPath = UPLOAD_DIR . '/' . $doc['categoria'] . '/' . $doc['nome_archivio'];
        $newDir = UPLOAD_DIR . '/' . $newCategory;
        if (!is_dir($newDir)) mkdir($newDir, 0775, true);
        $newPath = $newDir . '/' . $doc['nome_archivio'];

        if (is_file($oldPath)) {
            rename($oldPath, $newPath);
        }
    }

    $stmt = $conn->prepare("UPDATE documenti SET categoria = ?, note = ?, tags = ?, data_documento = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $newCategory, $note, $tags, $dataDocumento, $id);
    $stmt->execute();

    header("Location: index.php?msg=" . urlencode("Documento aggiornato"));
    exit;
}

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
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
        <?php if(isAdmin()): ?>
            <a href="utenti.php">👥 Utenti</a>
            <a href="backup.php">💾 Backup</a>
        <?php endif; ?>
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
            <a class="btn btn-secondary" href="index.php">← Home</a>
        </div>
    </div>

    <div class="card">
        <form method="POST">
            <label>Categoria</label>
            <select name="categoria">
                <?php foreach($categories as $key => $label): ?>
                    <option value="<?= h($key) ?>" <?= $doc['categoria'] === $key ? 'selected' : '' ?>>
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

            <button>Salva modifiche</button>
        </form>
    </div>
</div>

<script src="assets/js/theme.js"></script>
</body>
</html>
