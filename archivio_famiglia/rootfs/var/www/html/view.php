<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireLogin();

$category = safeCategory($_GET['category'] ?? '');
$file = basename($_GET['file'] ?? '');
$path = UPLOAD_DIR . '/' . $category . '/' . $file;

if (!is_file($path)) {
    exit(t('file_not_found'));
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
    $msg = t('public_link_created') . ' ' . $days . ' ' . t('days_short') . '.';
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$fileUrl = 'uploads/' . rawurlencode($category) . '/' . rawurlencode($file);
$downloadUrl = urlWithLang('download.php?category=' . urlencode($category) . '&file=' . urlencode($file));

$lang = currentLanguage();
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>
<meta charset="UTF-8">
<title><?= h(t('preview')) ?> - <?= h(t('app_name')) ?></title>
<link rel="stylesheet" href="assets/css/archivio.css">
<style>
.preview-box{
    background:rgba(2,6,23,.6);
    border:1px solid var(--line);
    border-radius:22px;
    padding:16px;
    min-height:500px;
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
.pdf-toolbar{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:center;
    justify-content:center;
    margin-bottom:16px;
}
.pdf-viewer{
    display:flex;
    flex-direction:column;
    gap:18px;
    align-items:center;
}
.pdf-page{
    width:100%;
    max-width:1100px;
    background:white;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 10px 35px rgba(0,0,0,.25);
}
.pdf-page canvas{
    width:100%;
    height:auto;
    display:block;
}
.pdf-loading{
    text-align:center;
    padding:80px 20px;
    color:var(--muted);
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="logo">📁 <?= h(t('app_name')) ?></div>
    <div class="menu">
        <a href="<?= h(urlWithLang('index.php')) ?>">🏠 <?= h(t('home')) ?></a>
        <a href="<?= h(urlWithLang('categorie.php')) ?>">⚙️ <?= h(t('categories')) ?></a>
        <?php if(isAdmin()): ?>
            <a href="<?= h(urlWithLang('utenti.php')) ?>">👥 <?= h(t('users')) ?></a>
            <a href="<?= h(urlWithLang('backup.php')) ?>">💾 <?= h(t('backup')) ?></a>
        <?php endif; ?>
        <a href="<?= h(urlWithLang('info.php')) ?>">ℹ️ <?= h(t('info')) ?></a>
        <a href="logout.php">🚪 <?= h(t('logout')) ?></a>
    </div>

    <?= languageSwitchHtml() ?>
</div>

<div class="main">

    <div class="card">
        <div class="topbar">
            <div>
                <span class="badge"><?= h(t('preview')) ?></span>
                <h1><?= h($file) ?></h1>
                <p><?= h(t('category')) ?>: <?= h($category) ?></p>
            </div>

            <div class="toolbar">
                <a class="btn btn-secondary" href="<?= h($downloadUrl) ?>">⬇️ <?= h(t('download')) ?></a>
                <a class="btn btn-secondary" href="<?= h(urlWithLang('index.php?categoria=' . urlencode($category))) ?>">📂 <?= h(t('category')) ?></a>
                <a class="btn btn-secondary" href="<?= h(urlWithLang('index.php')) ?>">← <?= h(t('home')) ?></a>
            </div>
        </div>

        <?php if($msg): ?>
            <p class="success"><?= h($msg) ?></p>
        <?php endif; ?>

        <div class="share-box">
            <h2><?= h(t('temporary_sharing')) ?></h2>
            <p><?= h(t('temporary_sharing_text')) ?></p>

            <form method="POST">
                <input type="hidden" name="create_public_link" value="1">

                <label><?= h(t('link_duration')) ?></label>
                <select name="days">
                    <option value="1">1 <?= h(t('day')) ?></option>
                    <option value="7">7 <?= h(t('days')) ?></option>
                    <option value="30">30 <?= h(t('days')) ?></option>
                </select>

                <button>🔗 <?= h(t('create_public_link')) ?></button>
            </form>

            <?php if($publicUrl): ?>
                <input class="share-url" id="publicUrl" value="<?= h($publicUrl) ?>" readonly>

                <div class="toolbar">
                    <button class="btn btn-secondary" type="button" onclick="copyPublicLink()">📋 <?= h(t('copy_link')) ?></button>
                    <button class="btn btn-secondary" type="button" onclick="sharePublicLink()">📤 <?= h(t('share')) ?></button>
                    <a class="btn btn-secondary" href="<?= h($publicUrl) ?>" target="_blank">👁️ <?= h(t('test_link')) ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="preview-box">
            <?php if (in_array($ext, ['jpg','jpeg','png','gif','webp'], true)): ?>

                <img src="<?= h($fileUrl) ?>" alt="<?= h($file) ?>">

            <?php elseif ($ext === 'pdf'): ?>

                <div class="pdf-toolbar">
                    <a class="btn btn-secondary" href="<?= h($fileUrl) ?>" target="_blank">↗️ <?= h(t('open_pdf')) ?></a>
                    <a class="btn btn-secondary" href="<?= h($downloadUrl) ?>">⬇️ <?= h(t('download_pdf')) ?></a>
                </div>

                <div id="pdfLoading" class="pdf-loading"><?= h(t('pdf_preview_loading')) ?></div>
                <div id="pdfViewer" class="pdf-viewer"></div>

            <?php else: ?>

                <div style="text-align:center;padding:80px 20px;">
                    <h2><?= h(t('preview_not_available')) ?></h2>
                    <p><?= h(t('preview_not_available_text')) ?></p>
                    <br>
                    <a class="btn" href="<?= h($downloadUrl) ?>">⬇️ <?= h(t('download_file')) ?></a>
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
            prompt(<?= json_encode(t('copy_manually')) ?>, text);
        });
    } else {
        prompt(<?= json_encode(t('copy_manually')) ?>, text);
    }
}

function copyPublicLink() {
    const input = document.getElementById('publicUrl');
    if (!input) return;
    copyText(input.value, <?= json_encode(t('public_link_copied')) ?>);
}

function sharePublicLink() {
    const input = document.getElementById('publicUrl');
    if (!input) return;

    const url = input.value;
    const title = <?= json_encode(t('shared_document')) ?>;

    if (navigator.share) {
        navigator.share({title:title, url:url}).catch(function(){});
    } else {
        window.open("https://wa.me/?text=" + encodeURIComponent(title + " " + url), "_blank");
    }
}
</script>

<?php if ($ext === 'pdf'): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
let pdfDoc = null;
const pdfScale = 1.25;
const pdfUrl = <?= json_encode($fileUrl) ?>;

pdfjsLib.GlobalWorkerOptions.workerSrc = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";

async function renderPdf() {
    const viewer = document.getElementById('pdfViewer');
    const loading = document.getElementById('pdfLoading');

    viewer.innerHTML = '';

    try {
        if (!pdfDoc) {
            pdfDoc = await pdfjsLib.getDocument(pdfUrl).promise;
        }

        loading.style.display = 'none';

        for (let pageNum = 1; pageNum <= pdfDoc.numPages; pageNum++) {
            const page = await pdfDoc.getPage(pageNum);
            const viewport = page.getViewport({scale: pdfScale});

            const pageWrap = document.createElement('div');
            pageWrap.className = 'pdf-page';

            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');

            canvas.width = viewport.width;
            canvas.height = viewport.height;

            pageWrap.appendChild(canvas);
            viewer.appendChild(pageWrap);

            await page.render({
                canvasContext: context,
                viewport: viewport
            }).promise;
        }
    } catch (e) {
        loading.innerHTML = <?= json_encode(t('pdf_preview_not_available')) ?>;
        console.error(e);
    }
}

renderPdf();
</script>
<?php endif; ?>

<script src="assets/js/theme.js"></script>
</body>
</html>
