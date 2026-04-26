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
        $msg = "Inserisci username";
    } elseif (strlen($password) < 4) {
        $msg = "Password troppo corta";
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

            $msg = "Utente creato correttamente";
        } else {
            $msg = "Username già esistente";
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

        $msg = "Password aggiornata";
    } else {
        $msg = "Password non valida o troppo corta";
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
        $msg = "Non puoi togliere il ruolo admin all'ultimo admin attivo";
    } else {
        $stmt = $conn->prepare("UPDATE utenti SET ruolo = ? WHERE id = ?");
        $stmt->bind_param("si", $ruolo, $id);
        $stmt->execute();

        if ($id === (int)($_SESSION['user_id'] ?? 0)) {
            $_SESSION['ruolo'] = $ruolo;
        }

        $msg = "Ruolo aggiornato";
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

        $msg = "Foto utente aggiornata";
    } else {
        $msg = "Nessuna foto valida caricata";
    }
}

/* ATTIVA / DISATTIVA */
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];

    if ($id === (int)($_SESSION['user_id'] ?? 0)) {
        $msg = "Non puoi disattivare l'utente con cui sei collegato";
    } else {
        $stmt = $conn->prepare("SELECT ruolo, attivo FROM utenti WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        $adminCountRes = $conn->query("SELECT COUNT(*) AS totale FROM utenti WHERE ruolo='admin' AND attivo = 1");
        $adminCount = $adminCountRes ? (int)($adminCountRes->fetch_assoc()['totale'] ?? 0) : 0;

        if ($user && $user['ruolo'] === 'admin' && (int)$user['attivo'] === 1 && $adminCount <= 1) {
            $msg = "Non puoi disattivare l'ultimo admin attivo";
        } else {
            $stmt = $conn->prepare("UPDATE utenti SET attivo = IF(attivo=1,0,1) WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $msg = "Stato utente aggiornato";
        }
    }
}

/* ELIMINA */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    if ($id === (int)($_SESSION['user_id'] ?? 0)) {
        $msg = "Non puoi eliminare l'utente con cui sei collegato";
    } else {
        $stmt = $conn->prepare("SELECT username, ruolo, attivo FROM utenti WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        $adminCountRes = $conn->query("SELECT COUNT(*) AS totale FROM utenti WHERE ruolo='admin' AND attivo = 1");
        $adminCount = $adminCountRes ? (int)($adminCountRes->fetch_assoc()['totale'] ?? 0) : 0;

        if ($user && $user['ruolo'] === 'admin' && (int)$user['attivo'] === 1 && $adminCount <= 1) {
            $msg = "Non puoi eliminare l'ultimo admin attivo";
        } else {
            $stmt = $conn->prepare("DELETE FROM utenti WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $msg = "Utente eliminato";
        }
    }
}

$res = $conn->query("SELECT * FROM utenti ORDER BY username ASC");
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Utenti</title>
<link rel="stylesheet" href="assets/css/archivio.css">
</head>
<body>

<div class="sidebar">
    <div class="logo">📁 Archivio</div>
    <div class="menu">
        <a href="index.php">🏠 Home</a>
        <a href="categorie.php">⚙️ Categorie</a>
        <a href="utenti.php" class="active">👥 Utenti</a>
        <a href="backup.php">💾 Backup</a>
        <a href="info.php">ℹ️ Info</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<div class="main">

    <div class="card">
        <div class="topbar">
            <div>
                <span class="badge">Amministrazione</span>
                <h1>Utenti</h1>
                <p>Crea utenti, carica foto, cambia password, modifica ruolo e gestisci accessi.</p>
            </div>

            <div class="toolbar">
                <a class="btn btn-secondary" href="index.php">← Home</a>
            </div>
        </div>

        <?php if ($msg): ?>
            <p class="success"><?= h($msg) ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Nuovo utente</h2>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="new_user" value="1">

            <label>Username</label>
            <input name="username" placeholder="Nome utente" required>

            <label>Password iniziale</label>
            <input name="password" placeholder="Password" required type="password">

            <label>Ruolo</label>
            <select name="ruolo">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>

            <label>Foto utente</label>
            <small>Carica una foto o immagine profilo JPG, PNG, WEBP o GIF.</small>
            <input type="file" name="foto" accept="image/*">

            <button>Crea utente</button>
        </form>
    </div>

    <div class="card">
        <div class="topbar">
            <div>
                <h2>Utenti esistenti</h2>
                <p>L’ultimo admin attivo e l’utente collegato sono protetti.</p>
            </div>

            <div class="toolbar">
                <a class="badge" href="utenti.php?view=card">▦ Card</a>
                <a class="badge" href="utenti.php?view=list">☰ Elenco</a>
            </div>
        </div>

        <?php if (!$res || $res->num_rows === 0): ?>

            <p>Nessun utente presente</p>

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
                                <span class="badge">Tu</span>
                            <?php endif; ?>
                            <br>
                            <small>Ruolo: <?= h($u['ruolo']) ?></small>
                            <br>
                            <small>Stato: <?= $isActive ? '🟢 Attivo' : '🔴 Disattivo' ?></small>
                            <br>
                            <small>Creato: <?= h($u['created_at'] ?? '') ?></small>

                            <form class="inline-form" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="change_photo" value="1">
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                                <label>Foto utente</label>
                                <small>Scegli una nuova immagine solo se vuoi sostituire quella attuale.</small>
                                <input type="file" name="foto" accept="image/*">

                                <button>Aggiorna foto</button>
                            </form>

                            <form class="inline-form" method="POST">
                                <input type="hidden" name="change_pass" value="1">
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                                <label>Nuova password</label>
                                <input name="password" placeholder="Minimo 4 caratteri" type="password">

                                <button>Cambia password</button>
                            </form>

                            <form class="inline-form" method="POST">
                                <input type="hidden" name="change_role" value="1">
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                                <label>Ruolo</label>
                                <select name="ruolo">
                                    <option value="user" <?= $u['ruolo'] === 'user' ? 'selected' : '' ?>>User</option>
                                    <option value="admin" <?= $u['ruolo'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>

                                <button>Salva ruolo</button>
                            </form>
                        </div>

                        <div class="actions">
                            <?php if (!$isCurrent): ?>
                                <a class="btn btn-secondary" href="utenti.php?toggle=<?= (int)$u['id'] ?>&view=list">
                                    <?= $isActive ? '🔴 Disattiva' : '🟢 Attiva' ?>
                                </a>

                                <a class="btn btn-danger" href="utenti.php?delete=<?= (int)$u['id'] ?>&view=list" onclick="return confirm('Eliminare questo utente?')">
                                    🗑️ Elimina
                                </a>
                            <?php else: ?>
                                <span class="badge">Utente collegato</span>
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
                                <span class="badge">Utente collegato</span>
                            <?php endif; ?>

                            <br>
                            <small>Ruolo: <?= h($u['ruolo']) ?></small>
                            <br>
                            <small>Stato: <?= $isActive ? '🟢 Attivo' : '🔴 Disattivo' ?></small>
                            <br>
                            <small>Creato: <?= h($u['created_at'] ?? '') ?></small>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="change_photo" value="1">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                            <label>Foto utente</label>
                            <small>Scegli file solo se vuoi cambiare immagine.</small>
                            <input type="file" name="foto" accept="image/*">

                            <button>Aggiorna foto</button>
                        </form>

                        <form method="POST">
                            <input type="hidden" name="change_pass" value="1">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                            <label>Nuova password</label>
                            <input name="password" placeholder="Minimo 4 caratteri" type="password">

                            <button>Cambia password</button>
                        </form>

                        <form method="POST">
                            <input type="hidden" name="change_role" value="1">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                            <label>Ruolo</label>
                            <select name="ruolo">
                                <option value="user" <?= $u['ruolo'] === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= $u['ruolo'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>

                            <button>Salva ruolo</button>
                        </form>

                        <div class="actions">
                            <?php if (!$isCurrent): ?>
                                <a class="btn btn-secondary" href="utenti.php?toggle=<?= (int)$u['id'] ?>&view=card">
                                    <?= $isActive ? '🔴 Disattiva' : '🟢 Attiva' ?>
                                </a>

                                <a class="btn btn-danger" href="utenti.php?delete=<?= (int)$u['id'] ?>&view=card" onclick="return confirm('Eliminare questo utente?')">
                                    🗑️ Elimina
                                </a>
                            <?php else: ?>
                                <span class="badge">Protetto</span>
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
