<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireAdmin();

$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0775, true);

$msg = '';

function exportDb(mysqli $conn): string {
    $sql = "-- Backup Archivio\n-- " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = [];
    $res = $conn->query("SHOW TABLES");
    while ($row = $res->fetch_array()) $tables[] = $row[0];

    foreach ($tables as $table) {
        $create = $conn->query("SHOW CREATE TABLE `$table`")->fetch_assoc();

        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $create['Create Table'] . ";\n\n";

        $rows = $conn->query("SELECT * FROM `$table`");

        while ($r = $rows->fetch_assoc()) {
            $cols = array_map(fn($c) => "`".$conn->real_escape_string($c)."`", array_keys($r));
            $vals = array_map(fn($v) => $v === null ? "NULL" : "'".$conn->real_escape_string($v)."'", array_values($r));
            $sql .= "INSERT INTO `$table` (".implode(',', $cols).") VALUES (".implode(',', $vals).");\n";
        }

        $sql .= "\n";
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql;
}

function rotateBackups(string $dir): void {
    $db = glob($dir."/db_*.sql") ?: [];
    $files = glob($dir."/files_*.tar.gz") ?: [];

    usort($db, fn($a,$b)=>filemtime($b)<=>filemtime($a));
    usort($files, fn($a,$b)=>filemtime($b)<=>filemtime($a));

    foreach(array_slice($db,2) as $f) if(is_file($f)) unlink($f);
    foreach(array_slice($files,2) as $f) if(is_file($f)) unlink($f);
}

function restoreSql(mysqli $conn, string $file): bool {
    $sql = file_get_contents($file);
    if (!$sql) return false;

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

/* CREA BACKUP */
if(isset($_POST['run_backup'])) {
    $date = date('Y-m-d_H-i-s');

    $dbFile = "$backupDir/db_$date.sql";
    $filesArchive = "$backupDir/files_$date.tar.gz";

    file_put_contents($dbFile, exportDb($conn));
    exec("tar -czf ".escapeshellarg($filesArchive)." -C /var/www/html uploads 2>&1", $out, $res);

    $msg = (file_exists($dbFile) && file_exists($filesArchive))
        ? "Backup completato"
        : "Errore backup";

    rotateBackups($backupDir);
}

/* CARICA BACKUP */
if(isset($_POST['upload_backup']) && !empty($_FILES['backup_file']['name'])) {
    $name = basename($_FILES['backup_file']['name']);

    if(str_ends_with($name,'.sql') || str_ends_with($name,'.tar.gz')){
        if(move_uploaded_file($_FILES['backup_file']['tmp_name'], "$backupDir/$name")){
            $msg = "Backup caricato";
            rotateBackups($backupDir);
        } else {
            $msg = "Errore caricamento backup";
        }
    } else {
        $msg = "Formato non valido";
    }
}

/* ELIMINA BACKUP */
if(isset($_GET['delete'])){
    $file = basename($_GET['delete']);
    $path = "$backupDir/$file";

    if(is_file($path)){
        unlink($path);
        $msg = "Backup eliminato";
    }
}

/* RIPRISTINA DATABASE */
if(isset($_POST['restore_db'])){
    $file = basename($_POST['restore_db']);
    $path = "$backupDir/$file";

    if(is_file($path) && str_ends_with($file,'.sql')){
        $msg = restoreSql($conn, $path)
            ? "Database ripristinato da $file"
            : "Errore ripristino database";
    }
}

/* RIPRISTINA FILE */
if(isset($_POST['restore_files'])){
    $file = basename($_POST['restore_files']);
    $path = "$backupDir/$file";

    if(is_file($path) && str_ends_with($file,'.tar.gz')){
        exec("tar -xzf ".escapeshellarg($path)." -C /var/www/html 2>&1", $out, $res);
        $msg = ($res === 0)
            ? "File ripristinati da $file"
            : "Errore ripristino file: ".implode(' ', $out);
    }
}

$list = glob($backupDir."/*") ?: [];
usort($list, fn($a,$b)=>filemtime($b)<=>filemtime($a));
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
        <a href="backup.php">💾 Backup</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<div class="main">

<div class="card">
    <div class="topbar">
        <div>
            <span class="badge">Sistema</span>
            <h1>Backup Archivio</h1>
            <p>Backup completo database + documenti. Rimangono solo gli ultimi 2 backup.</p>
        </div>
        <a href="index.php">← Home</a>
    </div>

    <?php if($msg): ?>
        <p class="success"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <form method="POST">
        <button name="run_backup">💾 Esegui backup</button>
    </form>
</div>

<div class="card">
    <h2>Carica backup</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="backup_file" required>
        <button name="upload_backup">⬆️ Carica backup</button>
    </form>
</div>

<div class="card">
    <h2>Backup disponibili</h2>

    <?php if(empty($list)): ?>
        <p>Nessun backup</p>
    <?php else: ?>
        <?php foreach($list as $f): ?>
            <?php $name = basename($f); ?>
            <div class="file">
                <strong><?= htmlspecialchars($name) ?></strong><br>
                <small><?= date('d/m/Y H:i', filemtime($f)) ?></small><br>
                <small><?= round(filesize($f)/1024/1024,2) ?> MB</small>

                <br><br>

                <a href="backups/<?= urlencode($name) ?>">⬇️ Scarica</a>

                <?php if(str_ends_with($name,'.sql')): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="restore_db" value="<?= htmlspecialchars($name) ?>">
                        <button onclick="return confirm('Ripristinare il database da questo backup?')">♻️ Ripristina DB</button>
                    </form>
                <?php endif; ?>

                <?php if(str_ends_with($name,'.tar.gz')): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="restore_files" value="<?= htmlspecialchars($name) ?>">
                        <button onclick="return confirm('Ripristinare i file da questo backup?')">♻️ Ripristina file</button>
                    </form>
                <?php endif; ?>

                <a href="backup.php?delete=<?= urlencode($name) ?>" onclick="return confirm('Eliminare backup?')">🗑️ Elimina</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</div>
<script src="assets/js/theme.js"></script>
</body>
</html>
