<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireAdmin();

$backupDir = BACKUP_DIR;
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0775, true);
}

$msg = '';

function exportDb(mysqli $conn): string
{
    $sql = "-- Backup Archivio Famiglia\n-- " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = [];
    $res = $conn->query("SHOW TABLES");

    if ($res) {
        while ($row = $res->fetch_array()) {
            $tables[] = $row[0];
        }
    }

    foreach ($tables as $table) {
        $safeTable = str_replace('`', '``', $table);

        $createRes = $conn->query("SHOW CREATE TABLE `$safeTable`");
        $create = $createRes ? $createRes->fetch_assoc() : null;

        if (!$create || empty($create['Create Table'])) {
            continue;
        }

        $sql .= "DROP TABLE IF EXISTS `$safeTable`;\n";
        $sql .= $create['Create Table'] . ";\n\n";

        $rows = $conn->query("SELECT * FROM `$safeTable`");

        if ($rows) {
            while ($r = $rows->fetch_assoc()) {
                $cols = array_map(
                    fn($c) => "`" . str_replace('`', '``', $c) . "`",
                    array_keys($r)
                );

                $vals = array_map(
                    fn($v) => $v === null ? "NULL" : "'" . $conn->real_escape_string((string)$v) . "'",
                    array_values($r)
                );

                $sql .= "INSERT INTO `$safeTable` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
            }
        }

        $sql .= "\n";
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    return $sql;
}

function rotateBackups(string $dir): void
{
    $db = glob($dir . "/db_*.sql") ?: [];
    $files = glob($dir . "/files_*.tar.gz") ?: [];

    usort($db, fn($a, $b) => filemtime($b) <=> filemtime($a));
    usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));

    foreach (array_slice($db, 2) as $f) {
        if (is_file($f)) {
            unlink($f);
        }
    }

    foreach (array_slice($files, 2) as $f) {
        if (is_file($f)) {
            unlink($f);
        }
    }
}

function restoreSql(mysqli $conn, string $file): bool
{
    $sql = file_get_contents($file);

    if (!$sql) {
        return false;
    }

    if ($conn->multi_query($sql)) {
        do {
            if ($res = $conn->store_result()) {
                $res->free();
            }
        } while ($conn->more_results() && $conn->next_result());

        return true;
    }

    return false;
}

function isAllowedBackupName(string $name): bool
{
    return preg_match('/^(db_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql|files_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.tar\.gz)$/', $name) === 1;
}

function safeBackupPath(string $dir, string $name): ?string
{
    $name = basename($name);

    if (!isAllowedBackupName($name)) {
        return null;
    }

    $path = $dir . '/' . $name;

    return is_file($path) ? $path : null;
}

/* SCARICA BACKUP */
if (isset($_GET['download'])) {
    $file = basename((string)$_GET['download']);
    $path = safeBackupPath($backupDir, $file);

    if (!$path) {
        http_response_code(404);
        exit('Backup non trovato');
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

/* CREA BACKUP */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_backup'])) {
    $date = date('Y-m-d_H-i-s');

    $dbFile = $backupDir . "/db_$date.sql";
    $filesArchive = $backupDir . "/files_$date.tar.gz";

    file_put_contents($dbFile, exportDb($conn));

    $storageDir = is_dir('/share/archivio') ? '/share/archivio' : UPLOAD_DIR;

    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0775, true);
    }

    exec(
        "tar -czf " . escapeshellarg($filesArchive) . " -C " . escapeshellarg($storageDir) . " . 2>&1",
        $out,
        $res
    );

    $msg = (is_file($dbFile) && is_file($filesArchive) && $res === 0)
        ? "Backup completato"
        : "Errore backup: " . implode(' ', $out ?? []);

    rotateBackups($backupDir);
}

/* CARICA BACKUP */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_backup']) && !empty($_FILES['backup_file']['name'])) {
    $name = basename((string)$_FILES['backup_file']['name']);

    if (
        preg_match('/^db_.*\.sql$/', $name) ||
        preg_match('/^files_.*\.tar\.gz$/', $name)
    ) {
        $dest = $backupDir . '/' . safeFilename($name);

        if (move_uploaded_file($_FILES['backup_file']['tmp_name'], $dest)) {
            chmod($dest, 0664);
            $msg = "Backup caricato";
            rotateBackups($backupDir);
        } else {
            $msg = "Errore caricamento backup";
        }
    } else {
        $msg = "Formato non valido. Usa file .sql oppure .tar.gz generati da Archivio Famiglia.";
    }
}

/* ELIMINA BACKUP */
if (isset($_GET['delete'])) {
    $file = basename((string)$_GET['delete']);
    $path = safeBackupPath($backupDir, $file);

    if ($path) {
        unlink($path);
        $msg = "Backup eliminato";
    } else {
        $msg = "Backup non trovato";
    }
}

/* RIPRISTINA DATABASE */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_db'])) {
    $file = basename((string)$_POST['restore_db']);
    $path = safeBackupPath($backupDir, $file);

    if ($path && str_ends_with($file, '.sql')) {
        $msg = restoreSql($conn, $path)
            ? "Database ripristinato da $file"
            : "Errore ripristino database";
    } else {
        $msg = "Backup database non valido";
    }
}

/* RIPRISTINA FILE */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_files'])) {
    $file = basename((string)$_POST['restore_files']);
    $path = safeBackupPath($backupDir, $file);

    if ($path && str_ends_with($file, '.tar.gz')) {
        $storageDir = is_dir('/share/archivio') ? '/share/archivio' : UPLOAD_DIR;

        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }

        exec(
            "tar -xzf " . escapeshellarg($path) . " -C " . escapeshellarg($storageDir) . " 2>&1",
            $out,
            $res
        );

        $msg = ($res === 0)
            ? "File ripristinati da $file"
            : "Errore ripristino file: " . implode(' ', $out ?? []);
    } else {
        $msg = "Backup file non valido";
    }
}

$list = glob($backupDir . "/*") ?: [];
$list = array_filter($list, fn($f) => is_file($f) && isAllowedBackupName(basename($f)));
usort($list, fn($a, $b) => filemtime($b) <=> filemtime($a));
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Backup</title>
<link rel="stylesheet" href="assets/css/archivio.css">
</head>
<body>

<div class="sidebar">
    <div class="logo">📁 Archivio</div>
    <div class="menu">
        <a href="index.php">🏠 Home</a>
        <a href="categorie.php">⚙️ Categorie</a>
        <a href="utenti.php">👥 Utenti</a>
        <a href="backup.php" class="active">💾 Backup</a>
        <a href="info.php">ℹ️ Info</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<div class="main">

    <div class="card">
        <div class="topbar">
            <div>
                <span class="badge">Sistema</span>
                <h1>Backup Archivio</h1>
                <p>Backup completo database + documenti. Rimangono solo gli ultimi 2 backup per tipo.</p>
            </div>
            <a class="btn btn-secondary" href="index.php">← Home</a>
        </div>

        <?php if ($msg): ?>
            <p class="success"><?= h($msg) ?></p>
        <?php endif; ?>

        <form method="POST">
            <button name="run_backup">💾 Esegui backup</button>
        </form>
    </div>

    <div class="card">
        <h2>Carica backup</h2>
        <p>Puoi caricare backup SQL o archivio file generati da Archivio Famiglia.</p>

        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="backup_file" required>
            <button name="upload_backup">⬆️ Carica backup</button>
        </form>
    </div>

    <div class="card">
        <h2>Backup disponibili</h2>

        <?php if (empty($list)): ?>
            <p>Nessun backup</p>
        <?php else: ?>
            <?php foreach ($list as $f): ?>
                <?php $name = basename($f); ?>

                <div class="file">
                    <strong><?= h($name) ?></strong><br>
                    <small><?= h(date('d/m/Y H:i', filemtime($f))) ?></small><br>
                    <small><?= h(formatBytes((int)filesize($f))) ?></small>

                    <br><br>

                    <div class="toolbar">
                        <a class="btn btn-secondary" href="backup.php?download=<?= urlencode($name) ?>">⬇️ Scarica</a>

                        <?php if (str_ends_with($name, '.sql')): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="restore_db" value="<?= h($name) ?>">
                                <button onclick="return confirm('Ripristinare il database da questo backup?')">♻️ Ripristina DB</button>
                            </form>
                        <?php endif; ?>

                        <?php if (str_ends_with($name, '.tar.gz')): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="restore_files" value="<?= h($name) ?>">
                                <button onclick="return confirm('Ripristinare i file da questo backup?')">♻️ Ripristina file</button>
                            </form>
                        <?php endif; ?>

                        <a class="btn btn-danger" href="backup.php?delete=<?= urlencode($name) ?>" onclick="return confirm('Eliminare backup?')">🗑️ Elimina</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script src="assets/js/theme.js"></script>
</body>
</html>
