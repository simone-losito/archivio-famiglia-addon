<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireLogin();

$category = safeCategory($_GET['category'] ?? '');
$file = basename($_GET['file'] ?? '');
$path = UPLOAD_DIR . '/' . $category . '/' . $file;

if (!is_file($path)) {
    exit('File non trovato');
}

$msg = '';
$publicUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_public_link'])) {
    $days = (int)($_POST['days'] ?? 1);
    if (!in_array($days, [1, 7, 30], true)) {
        $days = 1;
    }

    $token = bin2hex(random_bytes(24));
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$days days"));

    $stmt = $conn->prepare("
        INSERT INTO share_links (token, categoria, nome_archivio, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("ssss", $token, $category, $file, $expiresAt);
    $stmt->execute();

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

    $publicUrl = $scheme . '://' . $host . $base . '/public.php?t=' . urlencode($token);
    $msg = "Link pubblico creato. Scade tra $days giorno/i.";
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$pdfUrl = "uploads/" . rawurlencode($category) . "/" . rawurlencode($file);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Visualizza documento</title>
<link rel="stylesheet" href="assets/css/archivio.css">
<style>
.preview-box{
    background:rgba(2,6,23,.6);
    border:1px solid var(--line);
    border-radius:22px;
    padding:16px;
    min-height:500px;
}
.preview-box iframe,
.preview-box object,
.preview-box embed{
    width:100%;
    height:80vh;
    border:0;
    border-radius:14px;
    background:white;
}
.preview-box img{
    max-width:100%;
    max-height:80vh;
    display:block;
    margin:auto;
    border-radius:14px;
}
.share-box{
    background:rgba(34,211,238,.08);
    border:1px solid rgba(34,211,238,.28);
    border-radius:18px;
    padding:16px;
    margin-top:14px;
}
.share-url{
    width:100%;
    margin-top:10px;
}
</style>
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
        <a href="info.php">ℹ️ Info</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<div class="main">

    <div class="card">
        <div class="topbar">
            <div>
                <span class="badge">Anteprima</span>
                <h1><?= h($file) ?></h1>
                <p>Categoria: <?= h($category) ?></p>
            </div>

            <div class="toolbar">
                <a class="btn btn-secondary" href="download.php?category=<?= urlencode($category) ?>&file=<?= urlencode($file) ?>">⬇️ Scarica</a>
                <a class="btn btn-secondary" href="index.php?categoria=<?= urlencode($category) ?>">📂 Categoria</a>
                <a class="btn btn-secondary" href="index.php">← Home</a>
            </div>
        </div>

        <?php if($msg): ?>
            <p class="success"><?= h($msg) ?></p>
        <?php endif; ?>

        <div class="share-box">
            <h2>Condivisione temporanea</h2>
            <p>Crea un link pubblico solo per questo file. Chi riceve il link non vede il resto dell’archivio.</p>

            <form method="POST">
                <input type="hidden" name="create_public_link" value="1">

                <label>Durata link</label>
                <select name="days">
                    <option value="1">1 giorno</option>
                    <option value="7">7 giorni</option>
                    <option value="30">30 giorni</option>
                </select>

                <button>🔗 Crea link pubblico</button>
            </form>

            <?php if($publicUrl): ?>
                <input class="share-url" id="publicUrl" value="<?= h($publicUrl) ?>" readonly>

                <div class="toolbar">
                    <button class="btn btn-secondary" type="button" onclick="copyPublicLink()">📋 Copia link</button>
                    <button class="btn btn-secondary" type="button" onclick="sharePublicLink()">📤 Condividi</button>
                    <a class="btn btn-secondary" href="<?= h($publicUrl) ?>" target="_blank">👁️ Prova link</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="preview-box">
            <?php if (in_array($ext, ['jpg','jpeg','png','gif','webp'], true)): ?>

                <img src="uploads/<?= h($category) ?>/<?= h($file) ?>">

            <?php elseif ($ext === 'pdf'): ?>

                <object data="<?= h($pdfUrl) ?>" type="application/pdf">
                    <embed src="<?= h($pdfUrl) ?>" type="application/pdf">
                        <div style="text-align:center;padding:80px 20px;">
                            <h2>📄 Anteprima PDF non disponibile</h2>
                            <p>Il browser non riesce a mostrare il PDF dentro la pagina.</p>
                            <br>
                            <a class="btn" href="<?= h($pdfUrl) ?>" target="_blank">👁️ Apri PDF</a>
                            <a class="btn btn-secondary" href="download.php?category=<?= urlencode($category) ?>&file=<?= urlencode($file) ?>">⬇️ Scarica PDF</a>
                        </div>
                    </embed>
                </object>

            <?php else: ?>

                <div style="text-align:center;padding:80px 20px;">
                    <h2>Anteprima non disponibile</h2>
                    <p>Questo tipo di file può essere scaricato ma non visualizzato direttamente.</p>
                    <br>
                    <a class="btn" href="download.php?category=<?= urlencode($category) ?>&file=<?= urlencode($file) ?>">⬇️ Scarica file</a>
                </div>

            <?php endif; ?>
        </div>
    </div>

</div>

<script>
function copyText(text, message) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function(){
            alert(message);
        }).catch(function(){
            prompt("Copia manualmente:", text);
        });
    } else {
        prompt("Copia manualmente:", text);
    }
}

function copyPublicLink() {
    const input = document.getElementById('publicUrl');
    if (!input) return;
    copyText(input.value, "Link pubblico copiato!");
}

function sharePublicLink() {
    const input = document.getElementById('publicUrl');
    if (!input) return;

    const url = input.value;
    const title = "Documento condiviso";

    if (navigator.share) {
        navigator.share({title:title, url:url}).catch(function(){});
    } else {
        window.open("https://wa.me/?text=" + encodeURIComponent(title + " " + url), "_blank");
    }
}
</script>

<script src="assets/js/theme.js"></script>
</body>
</html>
