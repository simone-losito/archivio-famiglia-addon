<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireAdmin();

$msg = '';
$view = $_GET['view'] ?? 'card';
if (!in_array($view, ['card', 'list'], true)) $view = 'card';

$imgDir = __DIR__ . '/uploads/categorie';
if (!is_dir($imgDir)) mkdir($imgDir, 0775, true);

function saveCategoryImage(string $slug, string $field): ?string
{
    global $imgDir;
    if (empty($_FILES[$field]['name'])) return null;

    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) return null;

    $file = $slug . '_' . time() . '.' . $ext;
    $dest = $imgDir . '/' . $file;

    if (move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
        return 'uploads/categorie/' . $file;
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $nome = trim($_POST['nome'] ?? '');
    $slug = slugify($nome);

    if ($nome !== '' && $slug !== '') {
        $immagine = saveCategoryImage($slug, 'immagine');

        $stmt = $conn->prepare("INSERT INTO categorie (slug, nome, immagine) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $slug, $nome, $immagine);

        if ($stmt->execute()) {
            $dir = UPLOAD_DIR . '/' . $slug;
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            $msg = "Categoria creata correttamente";
        } else {
            $msg = "Categoria già esistente";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $slug = trim($_POST['slug'] ?? '');
    $nome = trim($_POST['nome'] ?? '');

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
        $msg = "Categoria aggiornata";
    }
}

if (isset($_GET['delete'])) {
    $slug = trim($_GET['delete']);

    if ($slug === 'altro') {
        $msg = "La categoria Altro non può essere eliminata";
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS totale FROM documenti WHERE categoria = ?");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $count = (int)($stmt->get_result()->fetch_assoc()['totale'] ?? 0);

        if ($count > 0) {
            $msg = "Non puoi eliminare una categoria che contiene documenti";
        } else {
            $stmt = $conn->prepare("DELETE FROM categorie WHERE slug = ?");
            $stmt->bind_param("s", $slug);
            $stmt->execute();
            $msg = "Categoria eliminata";
        }
    }
}

$res = $conn->query("SELECT * FROM categorie ORDER BY nome ASC");
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Categorie</title>
<link rel="stylesheet" href="assets/css/archivio.css">
</head>
<body>

<div class="sidebar">
    <div class="logo">📁 Archivio</div>
    <div class="menu">
        <a href="index.php">🏠 Home</a>
        <a href="categorie.php" class="active">⚙️ Categorie</a>
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
                <span class="badge">Gestione archivio</span>
                <h1>Categorie</h1>
                <p>Crea categorie, modifica nome, carica immagine e visualizza anteprima.</p>
            </div>
            <div class="toolbar">
                <a class="btn btn-secondary" href="index.php">← Home</a>
            </div>
        </div>

        <?php if($msg): ?>
            <p class="success"><?= htmlspecialchars($msg) ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Nuova categoria</h2>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="create_category" value="1">

            <label>Nome categoria</label>
            <input type="text" name="nome" placeholder="Esempio: Banca, Scuola, Assicurazioni..." required>

            <label>Immagine categoria</label>
            <small>Carica un’immagine JPG, PNG, WEBP o GIF che identifichi la categoria.</small>
            <input type="file" name="immagine" accept="image/*">

            <button>Crea categoria</button>
        </form>
    </div>

    <div class="card">
        <div class="topbar">
            <div>
                <h2>Categorie esistenti</h2>
                <p>La categoria “Altro” è protetta e non può essere eliminata.</p>
            </div>
            <div class="toolbar">
                <a class="badge" href="categorie.php?view=card">▦ Card</a>
                <a class="badge" href="categorie.php?view=list">☰ Elenco</a>
            </div>
        </div>

        <?php if(!$res || $res->num_rows === 0): ?>
            <p>Nessuna categoria presente</p>

        <?php elseif($view === 'list'): ?>

            <?php while($c = $res->fetch_assoc()): ?>
                <div class="file">
                    <div class="file-row">
                        <div>
                            <?php if(!empty($c['immagine'])): ?>
                                <img class="thumb" src="<?= htmlspecialchars($c['immagine']) ?>">
                            <?php else: ?>
                                <div class="thumb-placeholder">—</div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <strong><?= htmlspecialchars($c['nome']) ?></strong>
                            <br>
                            <small><?= htmlspecialchars($c['slug']) ?></small>

                            <form class="inline-form" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="edit_category" value="1">
                                <input type="hidden" name="slug" value="<?= htmlspecialchars($c['slug']) ?>">

                                <label>Nome categoria</label>
                                <input type="text" name="nome" value="<?= htmlspecialchars($c['nome']) ?>" required>

                                <label>Immagine categoria</label>
                                <small>Scegli una nuova immagine solo se vuoi sostituire quella attuale.</small>
                                <input type="file" name="immagine" accept="image/*">

                                <button>Salva modifica</button>
                            </form>
                        </div>

                        <div class="actions">
                            <?php if($c['slug'] !== 'altro'): ?>
                                <a class="btn btn-danger" href="categorie.php?delete=<?= urlencode($c['slug']) ?>&view=list" onclick="return confirm('Eliminare questa categoria?')">🗑️ Elimina</a>
                            <?php else: ?>
                                <span class="badge">Categoria protetta</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

        <?php else: ?>

            <div class="grid-cards">
                <?php while($c = $res->fetch_assoc()): ?>
                    <div class="item-card">
                        <?php if(!empty($c['immagine'])): ?>
                            <img class="preview" src="<?= htmlspecialchars($c['immagine']) ?>">
                        <?php else: ?>
                            <div class="no-img">Nessuna immagine</div>
                        <?php endif; ?>

                        <div>
                            <h3><?= htmlspecialchars($c['nome']) ?></h3>
                            <small><?= htmlspecialchars($c['slug']) ?></small>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="edit_category" value="1">
                            <input type="hidden" name="slug" value="<?= htmlspecialchars($c['slug']) ?>">

                            <label>Nome categoria</label>
                            <input type="text" name="nome" value="<?= htmlspecialchars($c['nome']) ?>" required>

                            <label>Immagine categoria</label>
                            <small>Scegli file solo se vuoi cambiare immagine.</small>
                            <input type="file" name="immagine" accept="image/*">

                            <button>Salva</button>
                        </form>

                        <div class="actions">
                            <?php if($c['slug'] !== 'altro'): ?>
                                <a class="btn btn-danger" href="categorie.php?delete=<?= urlencode($c['slug']) ?>&view=card" onclick="return confirm('Eliminare questa categoria?')">🗑️ Elimina</a>
                            <?php else: ?>
                                <span class="badge">Categoria protetta</span>
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
