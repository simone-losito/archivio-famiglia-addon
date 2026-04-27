<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireAdmin();

$msg = '';

$view = $_GET['view'] ?? 'card';
if (!in_array($view, ['card', 'list'], true)) {
    $view = 'card';
}

$imgDir = UPLOAD_DIR . '/categorie';
if (!is_dir($imgDir)) {
    mkdir($imgDir, 0775, true);
}

function saveCategoryImage(string $slug, string $field): ?string
{
    global $imgDir;

    if (
        empty($_FILES[$field]) ||
        ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    if (($_FILES[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return null;
    }

    $originalName = safeFilename((string)($_FILES[$field]['name'] ?? ''));
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        return null;
    }

    $tmp = (string)($_FILES[$field]['tmp_name'] ?? '');
    if (!is_uploaded_file($tmp)) {
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmp) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!is_string($mime) || !str_starts_with($mime, 'image/')) {
        return null;
    }

    $file = slugify($slug) . '_' . time() . '.' . $ext;
    $dest = $imgDir . '/' . $file;

    if (move_uploaded_file($tmp, $dest)) {
        chmod($dest, 0664);
        return 'uploads/categorie/' . $file;
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $nome = trim((string)($_POST['nome'] ?? ''));
    $slug = slugify($nome);

    if ($nome !== '' && $slug !== '') {
        $immagine = saveCategoryImage($slug, 'immagine');

        $stmt = $conn->prepare("INSERT INTO categorie (slug, nome, immagine) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $slug, $nome, $immagine);

        if ($stmt->execute()) {
            $dir = UPLOAD_DIR . '/' . $slug;
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            $msg = t('category_created_successfully');
        } else {
            $msg = t('category_already_exists');
        }
    } else {
        $msg = t('invalid_category_name');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $slug = safeCategory((string)($_POST['slug'] ?? ''));
    $nome = trim((string)($_POST['nome'] ?? ''));

    if ($slug !== '' && $nome !== '') {
        $newImage = saveCategoryImage($slug, 'immagine');

        if ($newImage) {
            $stmt = $conn->prepare("UPDATE categorie SET nome = ?, immagine = ? WHERE slug = ?");
            $stmt->bind_param("sss", $nome, $newImage, $slug);
        } else {
            $stmt = $conn->prepare("UPDATE categorie SET nome = ? WHERE slug = ?");
            $stmt->bind_param("ss", $nome, $slug);
        }

        $stmt->execute();
        $msg = t('category_updated');
    } else {
        $msg = t('invalid_category_data');
    }
}

if (isset($_GET['delete'])) {
    $slug = safeCategory((string)$_GET['delete']);

    if ($slug === 'altro') {
        $msg = t('other_category_cannot_be_deleted');
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS totale FROM documenti WHERE categoria = ?");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $count = (int)($stmt->get_result()->fetch_assoc()['totale'] ?? 0);

        if ($count > 0) {
            $msg = t('cannot_delete_category_with_documents');
        } else {
            $stmt = $conn->prepare("DELETE FROM categorie WHERE slug = ?");
            $stmt->bind_param("s", $slug);
            $stmt->execute();

            $msg = t('category_deleted');
        }
    }
}

$res = $conn->query("SELECT * FROM categorie ORDER BY nome ASC");

$lang = currentLanguage();
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>
<meta charset="UTF-8">
<title><?= h(t('categories')) ?> - <?= h(t('app_name')) ?></title>
<link rel="stylesheet" href="assets/css/archivio.css">
</head>
<body>

<div class="sidebar">
    <div class="logo">📁 <?= h(t('app_name')) ?></div>
    <div class="menu">
        <a href="<?= h(urlWithLang('index.php')) ?>">🏠 <?= h(t('home')) ?></a>
        <a href="<?= h(urlWithLang('categorie.php')) ?>" class="active">⚙️ <?= h(t('categories')) ?></a>
        <?php if (isAdmin()): ?>
            <a href="<?= h(urlWithLang('utenti.php')) ?>">👥 <?= h(t('users')) ?></a>
            <a href="<?= h(urlWithLang('backup.php')) ?>">💾 <?= h(t('backup')) ?></a>
        <?php endif; ?>
        <a href="<?= h(urlWithLang('info.php')) ?>">ℹ️ <?= h(t('info')) ?></a>
        <a href="logout.php">🚪 <?= h(t('logout')) ?></a>
    </div>
</div>

<div class="main">

    <div class="card">
        <div class="topbar">
            <div>
                <span class="badge"><?= h(t('archive_management')) ?></span>
                <h1><?= h(t('categories')) ?></h1>
                <p><?= h(t('categories_intro')) ?></p>
            </div>
            <div class="toolbar">
                <a class="btn btn-secondary" href="<?= h(urlWithLang('index.php')) ?>">← <?= h(t('home')) ?></a>
            </div>
        </div>

        <?php if ($msg): ?>
            <p class="success"><?= h($msg) ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2><?= h(t('new_category')) ?></h2>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="create_category" value="1">

            <label><?= h(t('category_name')) ?></label>
            <input type="text" name="nome" placeholder="<?= h(t('category_name_placeholder')) ?>" required>

            <label><?= h(t('category_image')) ?></label>
            <small><?= h(t('category_image_help')) ?></small>
            <input type="file" name="immagine" accept="image/*">

            <button><?= h(t('create_category')) ?></button>
        </form>
    </div>

    <div class="card">
        <div class="topbar">
            <div>
                <h2><?= h(t('existing_categories')) ?></h2>
                <p><?= h(t('other_category_protected_text')) ?></p>
            </div>
            <div class="toolbar">
                <a class="badge" href="<?= h(urlWithLang('categorie.php?view=card')) ?>">▦ <?= h(t('cards')) ?></a>
                <a class="badge" href="<?= h(urlWithLang('categorie.php?view=list')) ?>">☰ <?= h(t('list')) ?></a>
            </div>
        </div>

        <?php if (!$res || $res->num_rows === 0): ?>
            <p><?= h(t('no_categories')) ?></p>

        <?php elseif ($view === 'list'): ?>

            <?php while ($c = $res->fetch_assoc()): ?>
                <div class="file">
                    <div class="file-row">
                        <div>
                            <?php if (!empty($c['immagine'])): ?>
                                <img class="thumb" src="<?= h($c['immagine']) ?>" alt="<?= h($c['nome']) ?>">
                            <?php else: ?>
                                <div class="thumb-placeholder">—</div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <strong><?= h($c['nome']) ?></strong>
                            <br>
                            <small><?= h($c['slug']) ?></small>

                            <form class="inline-form" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="edit_category" value="1">
                                <input type="hidden" name="slug" value="<?= h($c['slug']) ?>">

                                <label><?= h(t('category_name')) ?></label>
                                <input type="text" name="nome" value="<?= h($c['nome']) ?>" required>

                                <label><?= h(t('category_image')) ?></label>
                                <small><?= h(t('replace_category_image_help')) ?></small>
                                <input type="file" name="immagine" accept="image/*">

                                <button><?= h(t('save_change')) ?></button>
                            </form>
                        </div>

                        <div class="actions">
                            <?php if ($c['slug'] !== 'altro'): ?>
                                <a class="btn btn-danger" href="<?= h(urlWithLang('categorie.php?delete=' . urlencode($c['slug']) . '&view=list')) ?>" onclick="return confirm('<?= h(t('confirm_delete_category')) ?>')">🗑️ <?= h(t('delete')) ?></a>
                            <?php else: ?>
                                <span class="badge"><?= h(t('protected_category')) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

        <?php else: ?>

            <div class="grid-cards">
                <?php while ($c = $res->fetch_assoc()): ?>
                    <div class="item-card">
                        <?php if (!empty($c['immagine'])): ?>
                            <img class="preview" src="<?= h($c['immagine']) ?>" alt="<?= h($c['nome']) ?>">
                        <?php else: ?>
                            <div class="no-img"><?= h(t('no_image')) ?></div>
                        <?php endif; ?>

                        <div>
                            <h3><?= h($c['nome']) ?></h3>
                            <small><?= h($c['slug']) ?></small>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="edit_category" value="1">
                            <input type="hidden" name="slug" value="<?= h($c['slug']) ?>">

                            <label><?= h(t('category_name')) ?></label>
                            <input type="text" name="nome" value="<?= h($c['nome']) ?>" required>

                            <label><?= h(t('category_image')) ?></label>
                            <small><?= h(t('change_image_only_if_needed')) ?></small>
                            <input type="file" name="immagine" accept="image/*">

                            <button><?= h(t('save')) ?></button>
                        </form>

                        <div class="actions">
                            <?php if ($c['slug'] !== 'altro'): ?>
                                <a class="btn btn-danger" href="<?= h(urlWithLang('categorie.php?delete=' . urlencode($c['slug']) . '&view=card')) ?>" onclick="return confirm('<?= h(t('confirm_delete_category')) ?>')">🗑️ <?= h(t('delete')) ?></a>
                            <?php else: ?>
                                <span class="badge"><?= h(t('protected_category')) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        <?php endif; ?>
    </div>

</div>

<script src="assets/js/theme.js"></script>
</body>
</html>
