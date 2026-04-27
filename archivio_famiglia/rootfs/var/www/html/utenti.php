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

$fotoDir = UPLOAD_DIR . '/utenti';
if (!is_dir($fotoDir)) {
    mkdir($fotoDir, 0775, true);
}

function saveUserPhoto(int $userId, string $field): ?string
{
    global $fotoDir;

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

    $file = 'utente_' . $userId . '_' . time() . '.' . $ext;
    $dest = $fotoDir . '/' . $file;

    if (move_uploaded_file($tmp, $dest)) {
        chmod($dest, 0664);
        return 'uploads/utenti/' . $file;
    }

    return null;
}

/* CREA UTENTE */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_user'])) {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $ruolo = (string)($_POST['ruolo'] ?? 'user');

    if (!in_array($ruolo, ['admin', 'user'], true)) {
        $ruolo = 'user';
    }

    if ($username === '') {
        $msg = t('enter_username');
    } elseif (strlen($password) < 4) {
        $msg = t('password_too_short');
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO utenti (username, password_hash, ruolo, attivo) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $username, $hash, $ruolo);

        if ($stmt->execute()) {
            $newId = (int)$conn->insert_id;
            $foto = saveUserPhoto($newId, 'foto');

            if ($foto) {
                $stmt = $conn->prepare("UPDATE utenti SET foto = ? WHERE id = ?");
                $stmt->bind_param("si", $foto, $newId);
                $stmt->execute();
            }

            $msg = t('user_created_successfully');
        } else {
            $msg = t('username_already_exists');
        }
    }
}

/* CAMBIA PASSWORD */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_pass'])) {
    $id = (int)($_POST['id'] ?? 0);
    $password = (string)($_POST['password'] ?? '');

    if ($id > 0 && strlen($password) >= 4) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE utenti SET password_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $id);
        $stmt->execute();

        $msg = t('password_updated');
    } else {
        $msg = t('invalid_or_short_password');
    }
}

/* CAMBIA RUOLO */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $id = (int)($_POST['id'] ?? 0);
    $ruolo = (string)($_POST['ruolo'] ?? 'user');

    if (!in_array($ruolo, ['admin', 'user'], true)) {
        $ruolo = 'user';
    }

    $adminCountRes = $conn->query("SELECT COUNT(*) AS totale FROM utenti WHERE ruolo='admin' AND attivo = 1");
    $adminCount = $adminCountRes ? (int)($adminCountRes->fetch_assoc()['totale'] ?? 0) : 0;

    $stmt = $conn->prepare("SELECT ruolo FROM utenti WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $oldUser = $stmt->get_result()->fetch_assoc();

    if ($oldUser && $oldUser['ruolo'] === 'admin' && $ruolo === 'user' && $adminCount <= 1) {
        $msg = t('cannot_remove_last_admin_role');
    } else {
        $stmt = $conn->prepare("UPDATE utenti SET ruolo = ? WHERE id = ?");
        $stmt->bind_param("si", $ruolo, $id);
        $stmt->execute();

        if ($id === (int)($_SESSION['user_id'] ?? 0)) {
            $_SESSION['ruolo'] = $ruolo;
        }

        $msg = t('role_updated');
    }
}

/* CAMBIA FOTO */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_photo'])) {
    $id = (int)($_POST['id'] ?? 0);
    $foto = saveUserPhoto($id, 'foto');

    if ($id > 0 && $foto) {
        $stmt = $conn->prepare("UPDATE utenti SET foto = ? WHERE id = ?");
        $stmt->bind_param("si", $foto, $id);
        $stmt->execute();

        $msg = t('user_photo_updated');
    } else {
        $msg = t('no_valid_photo_uploaded');
    }
}

/* ATTIVA / DISATTIVA */
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];

    if ($id === (int)($_SESSION['user_id'] ?? 0)) {
        $msg = t('cannot_disable_current_user');
    } else {
        $stmt = $conn->prepare("SELECT ruolo, attivo FROM utenti WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        $adminCountRes = $conn->query("SELECT COUNT(*) AS totale FROM utenti WHERE ruolo='admin' AND attivo = 1");
        $adminCount = $adminCountRes ? (int)($adminCountRes->fetch_assoc()['totale'] ?? 0) : 0;

        if ($user && $user['ruolo'] === 'admin' && (int)$user['attivo'] === 1 && $adminCount <= 1) {
            $msg = t('cannot_disable_last_admin');
        } else {
            $stmt = $conn->prepare("UPDATE utenti SET attivo = IF(attivo=1,0,1) WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $msg = t('user_status_updated');
        }
    }
}

/* ELIMINA */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    if ($id === (int)($_SESSION['user_id'] ?? 0)) {
        $msg = t('cannot_delete_current_user');
    } else {
        $stmt = $conn->prepare("SELECT username, ruolo, attivo FROM utenti WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        $adminCountRes = $conn->query("SELECT COUNT(*) AS totale FROM utenti WHERE ruolo='admin' AND attivo = 1");
        $adminCount = $adminCountRes ? (int)($adminCountRes->fetch_assoc()['totale'] ?? 0) : 0;

        if ($user && $user['ruolo'] === 'admin' && (int)$user['attivo'] === 1 && $adminCount <= 1) {
            $msg = t('cannot_delete_last_admin');
        } else {
            $stmt = $conn->prepare("DELETE FROM utenti WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $msg = t('user_deleted');
        }
    }
}

$res = $conn->query("SELECT * FROM utenti ORDER BY username ASC");

$lang = currentLanguage();
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>
<meta charset="UTF-8">
<title><?= h(t('users')) ?> - <?= h(t('app_name')) ?></title>
<link rel="stylesheet" href="assets/css/archivio.css">
</head>
<body>

<div class="sidebar">
    <div class="logo">📁 <?= h(t('app_name')) ?></div>
    <div class="menu">
        <a href="<?= h(urlWithLang('index.php')) ?>">🏠 <?= h(t('home')) ?></a>
        <a href="<?= h(urlWithLang('categorie.php')) ?>">⚙️ <?= h(t('categories')) ?></a>
        <a href="<?= h(urlWithLang('utenti.php')) ?>" class="active">👥 <?= h(t('users')) ?></a>
        <a href="<?= h(urlWithLang('backup.php')) ?>">💾 <?= h(t('backup')) ?></a>
        <a href="<?= h(urlWithLang('info.php')) ?>">ℹ️ <?= h(t('info')) ?></a>
        <a href="logout.php">🚪 <?= h(t('logout')) ?></a>
    </div>
</div>

<div class="main">

    <div class="card">
        <div class="topbar">
            <div>
                <span class="badge"><?= h(t('administration')) ?></span>
                <h1><?= h(t('users')) ?></h1>
                <p><?= h(t('users_intro')) ?></p>
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
        <h2><?= h(t('new_user')) ?></h2>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="new_user" value="1">

            <label><?= h(t('username')) ?></label>
            <input name="username" placeholder="<?= h(t('username_placeholder')) ?>" required>

            <label><?= h(t('initial_password')) ?></label>
            <input name="password" placeholder="<?= h(t('password')) ?>" required type="password">

            <label><?= h(t('role')) ?></label>
            <select name="ruolo">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>

            <label><?= h(t('user_photo')) ?></label>
            <small><?= h(t('user_photo_help')) ?></small>
            <input type="file" name="foto" accept="image/*">

            <button><?= h(t('create_user')) ?></button>
        </form>
    </div>

    <div class="card">
        <div class="topbar">
            <div>
                <h2><?= h(t('existing_users')) ?></h2>
                <p><?= h(t('users_protection_text')) ?></p>
            </div>

            <div class="toolbar">
                <a class="badge" href="<?= h(urlWithLang('utenti.php?view=card')) ?>">▦ <?= h(t('cards')) ?></a>
                <a class="badge" href="<?= h(urlWithLang('utenti.php?view=list')) ?>">☰ <?= h(t('list')) ?></a>
            </div>
        </div>

        <?php if (!$res || $res->num_rows === 0): ?>

            <p><?= h(t('no_users')) ?></p>

        <?php elseif ($view === 'list'): ?>

            <?php while ($u = $res->fetch_assoc()): ?>
                <?php
                    $isCurrent = (int)$u['id'] === (int)($_SESSION['user_id'] ?? 0);
                    $isActive = (int)$u['attivo'] === 1;
                ?>
                <div class="file">
                    <div class="file-row">
                        <div>
                            <?php if (!empty($u['foto'])): ?>
                                <img class="thumb" src="<?= h($u['foto']) ?>" alt="<?= h($u['username']) ?>">
                            <?php else: ?>
                                <div class="thumb-placeholder">👤</div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <strong><?= h($u['username']) ?></strong>
                            <?php if ($isCurrent): ?>
                                <span class="badge"><?= h(t('you')) ?></span>
                            <?php endif; ?>
                            <br>
                            <small><?= h(t('role')) ?>: <?= h($u['ruolo']) ?></small>
                            <br>
                            <small><?= h(t('status')) ?>: <?= $isActive ? '🟢 ' . h(t('active')) : '🔴 ' . h(t('inactive')) ?></small>
                            <br>
                            <small><?= h(t('created')) ?>: <?= h($u['created_at'] ?? '') ?></small>

                            <form class="inline-form" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="change_photo" value="1">
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                                <label><?= h(t('user_photo')) ?></label>
                                <small><?= h(t('replace_user_photo_help')) ?></small>
                                <input type="file" name="foto" accept="image/*">

                                <button><?= h(t('update_photo')) ?></button>
                            </form>

                            <form class="inline-form" method="POST">
                                <input type="hidden" name="change_pass" value="1">
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                                <label><?= h(t('new_password')) ?></label>
                                <input name="password" placeholder="<?= h(t('minimum_4_chars')) ?>" type="password">

                                <button><?= h(t('change_password')) ?></button>
                            </form>

                            <form class="inline-form" method="POST">
                                <input type="hidden" name="change_role" value="1">
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                                <label><?= h(t('role')) ?></label>
                                <select name="ruolo">
                                    <option value="user" <?= $u['ruolo'] === 'user' ? 'selected' : '' ?>>User</option>
                                    <option value="admin" <?= $u['ruolo'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>

                                <button><?= h(t('save_role')) ?></button>
                            </form>
                        </div>

                        <div class="actions">
                            <?php if (!$isCurrent): ?>
                                <a class="btn btn-secondary" href="<?= h(urlWithLang('utenti.php?toggle=' . (int)$u['id'] . '&view=list')) ?>">
                                    <?= $isActive ? '🔴 ' . h(t('disable')) : '🟢 ' . h(t('enable')) ?>
                                </a>

                                <a class="btn btn-danger" href="<?= h(urlWithLang('utenti.php?delete=' . (int)$u['id'] . '&view=list')) ?>" onclick="return confirm('<?= h(t('confirm_delete_user')) ?>')">
                                    🗑️ <?= h(t('delete')) ?>
                                </a>
                            <?php else: ?>
                                <span class="badge"><?= h(t('current_user')) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

        <?php else: ?>

            <div class="grid-cards">
                <?php while ($u = $res->fetch_assoc()): ?>
                    <?php
                        $isCurrent = (int)$u['id'] === (int)($_SESSION['user_id'] ?? 0);
                        $isActive = (int)$u['attivo'] === 1;
                    ?>

                    <div class="item-card">
                        <?php if (!empty($u['foto'])): ?>
                            <img class="preview" src="<?= h($u['foto']) ?>" alt="<?= h($u['username']) ?>">
                        <?php else: ?>
                            <div class="no-img" style="font-size:42px;">👤</div>
                        <?php endif; ?>

                        <div>
                            <h3><?= h($u['username']) ?></h3>

                            <?php if ($isCurrent): ?>
                                <span class="badge"><?= h(t('current_user')) ?></span>
                            <?php endif; ?>

                            <br>
                            <small><?= h(t('role')) ?>: <?= h($u['ruolo']) ?></small>
                            <br>
                            <small><?= h(t('status')) ?>: <?= $isActive ? '🟢 ' . h(t('active')) : '🔴 ' . h(t('inactive')) ?></small>
                            <br>
                            <small><?= h(t('created')) ?>: <?= h($u['created_at'] ?? '') ?></small>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="change_photo" value="1">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                            <label><?= h(t('user_photo')) ?></label>
                            <small><?= h(t('change_image_only_if_needed')) ?></small>
                            <input type="file" name="foto" accept="image/*">

                            <button><?= h(t('update_photo')) ?></button>
                        </form>

                        <form method="POST">
                            <input type="hidden" name="change_pass" value="1">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                            <label><?= h(t('new_password')) ?></label>
                            <input name="password" placeholder="<?= h(t('minimum_4_chars')) ?>" type="password">

                            <button><?= h(t('change_password')) ?></button>
                        </form>

                        <form method="POST">
                            <input type="hidden" name="change_role" value="1">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                            <label><?= h(t('role')) ?></label>
                            <select name="ruolo">
                                <option value="user" <?= $u['ruolo'] === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= $u['ruolo'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>

                            <button><?= h(t('save_role')) ?></button>
                        </form>

                        <div class="actions">
                            <?php if (!$isCurrent): ?>
                                <a class="btn btn-secondary" href="<?= h(urlWithLang('utenti.php?toggle=' . (int)$u['id'] . '&view=card')) ?>">
                                    <?= $isActive ? '🔴 ' . h(t('disable')) : '🟢 ' . h(t('enable')) ?>
                                </a>

                                <a class="btn btn-danger" href="<?= h(urlWithLang('utenti.php?delete=' . (int)$u['id'] . '&view=card')) ?>" onclick="return confirm('<?= h(t('confirm_delete_user')) ?>')">
                                    🗑️ <?= h(t('delete')) ?>
                                </a>
                            <?php else: ?>
                                <span class="badge"><?= h(t('protected')) ?></span>
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
