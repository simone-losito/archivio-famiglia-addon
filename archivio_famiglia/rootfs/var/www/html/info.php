<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireLogin();

function getAddonVersion(): string
{
    $file = '/etc/archivio_famiglia_config.yaml';

    if (!is_file($file) || !is_readable($file)) {
        return APP_VERSION;
    }

    $content = file_get_contents($file);

    if (preg_match('/^version:\s*[\"\']?([^\"\']+)[\"\']?/m', (string)$content, $m)) {
        return trim($m[1]);
    }

    return APP_VERSION;
}

function folderSize(string $dir): int
{
    $size = 0;

    if (!is_dir($dir)) {
        return 0;
    }

    try {
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        ) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
    } catch (Throwable $e) {
        return 0;
    }

    return $size;
}

$addonVersion = getAddonVersion();

$resDoc = $conn->query("SELECT COUNT(*) AS tot FROM documenti");
$totDocumenti = $resDoc ? (int)($resDoc->fetch_assoc()['tot'] ?? 0) : 0;

$resUser = $conn->query("SELECT COUNT(*) AS tot FROM utenti");
$totUtenti = $resUser ? (int)($resUser->fetch_assoc()['tot'] ?? 0) : 0;

$uploadDir = is_dir('/share/archivio') ? '/share/archivio' : UPLOAD_DIR;
$spazio = formatBytes(folderSize($uploadDir));

$localLogo = __DIR__ . '/assets/logo.png';
$logoSrc = is_file($localLogo)
    ? 'assets/logo.png'
    : 'https://raw.githubusercontent.com/simone-losito/archivio-famiglia-addon/main/assets/logo.png';

$repoUrl = 'https://github.com/simone-losito/archivio-famiglia-addon';
$changelogUrl = $repoUrl . '/blob/main/archivio_famiglia/CHANGELOG.md';

$lang = currentLanguage();
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>
<meta charset="UTF-8">
<title><?= h(t('info')) ?> - <?= h(t('app_name')) ?></title>
<link rel="stylesheet" href="assets/css/archivio.css">

<style>
.wrap{
    max-width:900px;
    margin:40px auto;
    padding:0 18px;
}

.top-pill-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:20px;
}

.pill-home{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 16px;
    border-radius:999px;
    background:linear-gradient(135deg, rgba(34,211,238,.2), rgba(168,85,247,.2));
    border:1px solid rgba(34,211,238,.4);
    color:var(--text);
    font-weight:600;
    text-decoration:none;
    transition:.2s;
}

.pill-home:hover{
    background:linear-gradient(135deg, rgba(34,211,238,.35), rgba(168,85,247,.35));
    transform:translateY(-1px);
    text-decoration:none;
}

.center{
    text-align:center;
}

.logo-img{
    width:140px;
    max-height:140px;
    object-fit:contain;
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

.link-pill{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:12px 18px;
    border-radius:999px;
    border:1px solid var(--line);
    background:rgba(34,211,238,.10);
    color:var(--text);
    text-decoration:none;
    font-weight:700;
}

.link-pill:hover{
    text-decoration:none;
    border-color:rgba(34,211,238,.45);
}
</style>
</head>

<body>

<div class="wrap">

    <div class="top-pill-bar">
        <a href="<?= h(urlWithLang('index.php')) ?>" class="pill-home">🏠 <?= h(t('back_to_home')) ?></a>
        <?= languageSwitchHtml() ?>
    </div>

    <div class="center">
        <img src="<?= h($logoSrc) ?>" class="logo-img" alt="<?= h(t('app_name')) ?>">

        <h1><?= h(t('app_name')) ?></h1>
        <p class="big"><?= h(t('app_subtitle')) ?></p>
        <p class="author-line"><?= h(t('app_legacy_name')) ?></p>

        <p><b><?= h(t('version')) ?>:</b> <?= h($addonVersion) ?></p>
        <p class="author-line">by <b>SimoncinoProjects</b> / Simone Losito</p>
    </div>

    <div class="info-box">
        <h2>📊 <?= h(t('archive_statistics')) ?></h2>

        <div class="stats">
            <div class="stat">
                📄 <?= h(t('documents')) ?>
                <b><?= (int)$totDocumenti ?></b>
            </div>

            <div class="stat">
                👥 <?= h(t('users')) ?>
                <b><?= (int)$totUtenti ?></b>
            </div>

            <div class="stat">
                💾 <?= h(t('used_space')) ?>
                <b><?= h($spazio) ?></b>
            </div>
        </div>
    </div>

    <div class="info-box">
        <h2>👨‍💻 <?= h(t('author')) ?></h2>
        <p><b>SimoncinoProjects</b></p>
        <p><?= h(t('created_and_maintained_by')) ?> <b>Simone Losito</b>.</p>
    </div>

    <div class="info-box center">
        <h2>🔗 Repository</h2>
        <a class="link-pill" href="<?= h($repoUrl) ?>" target="_blank" rel="noopener">
            <?= h(t('go_to_github_project')) ?>
        </a>
    </div>

    <div class="info-box center">
        <h2>📜 Changelog</h2>
        <a class="link-pill" href="<?= h($changelogUrl) ?>" target="_blank" rel="noopener">
            <?= h(t('view_version_history')) ?>
        </a>
    </div>

    <div class="info-box center">
        <h2>☕ <?= h(t('support_project')) ?></h2>
        <p><?= h(t('support_project_text')) ?></p>
        <p>
            <a href="https://www.paypal.com/paypalme/simoncinoprojects" target="_blank" rel="noopener">
                <img src="https://img.shields.io/badge/Support%20the%20project-PayPal-blue?style=for-the-badge&logo=paypal" alt="<?= h(t('support_project')) ?>">
            </a>
        </p>
    </div>

    <div class="info-box">
        <h2>⚙️ <?= h(t('technical_info')) ?></h2>
        <p>PHP 8.2 + Apache</p>
        <p><?= h(t('database')) ?>: MariaDB add-on Home Assistant</p>
        <p><?= h(t('document_storage')) ?>: /share/archivio</p>
        <p><?= h(t('persistent_upload')) ?>: /var/www/html/uploads</p>
        <p><?= h(t('home_assistant_addon_port')) ?>: 8091</p>
    </div>

</div>

<script src="assets/js/theme.js"></script>
</body>
</html>
