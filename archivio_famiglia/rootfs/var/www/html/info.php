<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function getAddonVersion(): string {
    $file = '/etc/archivio_famiglia_config.yaml';

    if (!is_file($file)) {
        return 'unknown';
    }

    $content = file_get_contents($file);

    if (preg_match('/^version:\s*[\"\']?([^\"\']+)[\"\']?/m', $content, $m)) {
        return trim($m[1]);
    }

    return 'unknown';
}

function folderSize($dir): int {
    $size = 0;
    if (!is_dir($dir)) return 0;

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $file) {
        $size += $file->getSize();
    }

    return $size;
}

function formatSize($bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

$addonVersion = getAddonVersion();

$resDoc = $conn->query("SELECT COUNT(*) AS tot FROM documenti");
$totDocumenti = $resDoc ? (int)$resDoc->fetch_assoc()['tot'] : 0;

$resUser = $conn->query("SELECT COUNT(*) AS tot FROM utenti");
$totUtenti = $resUser ? (int)$resUser->fetch_assoc()['tot'] : 0;

$uploadDir = is_dir('/share/archivio') ? '/share/archivio' : (__DIR__ . '/uploads');
$spazio = formatSize(folderSize($uploadDir));
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Info Archivio</title>
<link rel="stylesheet" href="assets/css/archivio.css">
<style>
.wrap{
    max-width:900px;
    margin:40px auto;
}
.center{
    text-align:center;
}
.logo-img{
    width:140px;
    margin:0 auto 20px;
    display:block;
}
.info-box{
    margin-top:20px;
    padding:20px;
    border-radius:18px;
    border:1px solid var(--line);
    background:rgba(2,6,23,.35);
}
.stats{
    display:flex;
    gap:20px;
    justify-content:space-between;
    flex-wrap:wrap;
}
.stat{
    flex:1;
    min-width:180px;
    padding:16px;
    border-radius:16px;
    background:rgba(15,23,42,.6);
    border:1px solid var(--line);
    text-align:center;
}
.stat b{
    display:block;
    font-size:28px;
    margin-top:8px;
}
.big{
    font-size:18px;
    margin-top:10px;
}
.author-line{
    color:var(--muted);
    margin-top:6px;
}
</style>
</head>
<body>

<div class="wrap">

    <div class="center">
        <img src="assets/logo.png" class="logo-img" alt="Archivio Famiglia">
        <h1>Archivio Famiglia</h1>
        <p class="big">Gestione documentale familiare integrata in Home Assistant</p>
        <p><b>Versione:</b> <?= h($addonVersion) ?></p>
        <p class="author-line">by <b>SimoncinoProjects</b> / Simone Losito</p>
    </div>

    <div class="info-box">
        <h2>📊 Statistiche archivio</h2>

        <div class="stats">
            <div class="stat">
                📄 Documenti
                <b><?= (int)$totDocumenti ?></b>
            </div>

            <div class="stat">
                👥 Utenti
                <b><?= (int)$totUtenti ?></b>
            </div>

            <div class="stat">
                💾 Spazio utilizzato
                <b><?= h($spazio) ?></b>
            </div>
        </div>
    </div>

    <div class="info-box">
        <h2>👨‍💻 Autore</h2>
        <p><b>SimoncinoProjects</b></p>
        <p>Creato e mantenuto da <b>Simone Losito</b>.</p>
    </div>

    <div class="info-box center">
        <h2>🔗 Repository</h2>
        <p>
            <a href="https://github.com/simone-losito/archivio-famiglia-addon" target="_blank">
                Vai al progetto su GitHub
            </a>
        </p>
    </div>

    <div class="info-box center">
        <h2>📜 Changelog</h2>
        <p>
            <a href="https://github.com/simone-losito/archivio-famiglia-addon/blob/main/CHANGELOG.md" target="_blank">
                Vedi storico versioni
            </a>
        </p>
    </div>

    <div class="info-box center">
        <h2>☕ Supporta il progetto</h2>
        <p>Se ti è utile puoi offrire un caffè allo sviluppo.</p>
        <p>
            <a href="https://www.paypal.com/paypalme/simoncinoprojects" target="_blank">
                <img src="https://img.shields.io/badge/Supporta%20il%20progetto-PayPal-blue?style=for-the-badge&logo=paypal" alt="Supporta con PayPal">
            </a>
        </p>
    </div>

    <div class="info-box">
        <h2>⚙️ Info tecniche</h2>
        <p>PHP 8 + Apache</p>
        <p>Database: MariaDB</p>
        <p>Storage: /share/archivio</p>
        <p>Addon Home Assistant</p>
    </div>

</div>

<script src="assets/js/theme.js"></script>
</body>
</html>
