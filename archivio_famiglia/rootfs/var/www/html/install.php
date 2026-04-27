<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

function tableExists(mysqli $conn, string $table): bool
{
    $table = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$table}'");
    return $res && $res->num_rows > 0;
}

function createDemoPdf(string $path): void
{
    $pdf = "%PDF-1.4
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >>
endobj
4 0 obj
<< /Length 204 >>
stream
BT
/F1 28 Tf
60 760 Td
(FamilyDocs) Tj
/F1 16 Tf
0 -45 Td
(Document Manager for Home Assistant) Tj
0 -30 Td
(Demo document created automatically.) Tj
0 -45 Td
(Enjoy!) Tj
ET
endstream
endobj
5 0 obj
<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>
endobj
xref
0 6
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000251 00000 n 
0000000505 00000 n 
trailer
<< /Size 6 /Root 1 0 R >>
startxref
575
%%EOF";

    file_put_contents($path, $pdf);
}

function ensureTables(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS utenti (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(80) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            ruolo VARCHAR(30) NOT NULL DEFAULT 'user',
            attivo TINYINT(1) NOT NULL DEFAULT 1,
            foto VARCHAR(255) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS categorie (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(100) NOT NULL UNIQUE,
            nome VARCHAR(150) NOT NULL,
            immagine VARCHAR(255) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS documenti (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome_archivio VARCHAR(255) NOT NULL,
            nome_originale VARCHAR(255) NOT NULL,
            titolo VARCHAR(255) NULL,
            categoria VARCHAR(100) NULL,
            note TEXT NULL,
            tags VARCHAR(255) NULL,
            data_documento DATE NULL,
            preferito TINYINT(1) NOT NULL DEFAULT 0,
            data_upload DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS share_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(80) NOT NULL UNIQUE,
            categoria VARCHAR(100) NOT NULL,
            nome_archivio VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

function installNeeded(mysqli $conn): bool
{
    if (!tableExists($conn, 'utenti')) {
        return true;
    }

    $res = $conn->query("SELECT COUNT(*) AS totale FROM utenti");
    $count = $res ? (int)($res->fetch_assoc()['totale'] ?? 0) : 0;

    return $count === 0;
}

if (!installNeeded($conn)) {
    header("Location: " . urlWithLang('login.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($username === '') {
        $error = t('enter_username');
    } elseif (strlen($password) < 4) {
        $error = t('password_too_short');
    } elseif ($password !== $password2) {
        $error = t('passwords_do_not_match');
    } else {
        ensureTables($conn);

        $defaults = [
            'cartelle_cliniche' => 'Cartelle cliniche',
            'referti' => 'Referti',
            'casa' => 'Casa',
            'auto' => 'Auto',
            'altro' => 'Altro',
        ];

        foreach ($defaults as $slug => $nome) {
            $stmt = $conn->prepare("INSERT IGNORE INTO categorie (slug, nome) VALUES (?, ?)");
            $stmt->bind_param("ss", $slug, $nome);
            $stmt->execute();

            $dir = UPLOAD_DIR . '/' . $slug;
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO utenti (username, password_hash, ruolo, attivo) VALUES (?, ?, 'admin', 1)");
        $stmt->bind_param("ss", $username, $hash);
        $stmt->execute();

        $demoDir = UPLOAD_DIR . '/referti';
        if (!is_dir($demoDir)) {
            mkdir($demoDir, 0775, true);
        }

        $demoFile = 'DOC-0001.pdf';
        $demoPath = $demoDir . '/' . $demoFile;

        if (!is_file($demoPath)) {
            createDemoPdf($demoPath);
        }

        $titolo = t('demo_document_title');
        $originale = 'documento_dimostrativo.pdf';
        $categoria = 'referti';
        $note = t('demo_document_note');
        $tags = 'demo, example, first setup';
        $today = date('Y-m-d');

        $stmt = $conn->prepare("
            INSERT INTO documenti
            (nome_archivio, nome_originale, titolo, categoria, note, tags, data_documento, preferito)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->bind_param("sssssss", $demoFile, $originale, $titolo, $categoria, $note, $tags, $today);
        $stmt->execute();

        header("Location: " . urlWithLang('login.php?installed=1'));
        exit;
    }
}

$lang = currentLanguage();
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>
<meta charset="UTF-8">
<title><?= h(t('first_setup')) ?> - <?= h(t('app_name')) ?></title>
<link rel="stylesheet" href="assets/css/archivio.css">
<style>
.login-wrap{
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:24px;
}
.login-card{
    width:100%;
    max-width:520px;
}
.language-switch{
    display:flex;
    gap:8px;
    margin-bottom:16px;
    justify-content:flex-end;
}
.language-switch a{
    font-size:13px;
    text-decoration:none;
    padding:6px 10px;
    border-radius:999px;
    border:1px solid var(--border);
    color:var(--text-muted);
}
.language-switch a.active{
    color:var(--text);
    background:var(--accent-soft);
    border-color:var(--accent);
}
</style>
</head>
<body>

<div class="login-wrap">
    <div class="card login-card">

        <div class="language-switch">
            <a href="?lang=it" class="<?= $lang === 'it' ? 'active' : '' ?>">IT</a>
            <a href="?lang=en" class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>
        </div>

        <span class="badge"><?= h(t('first_setup')) ?></span>
        <h1>🚀 <?= h(t('app_name')) ?></h1>
        <p><?= h(t('first_setup_description')) ?></p>

        <?php if ($error): ?>
            <p class="error"><?= h($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label><?= h(t('admin_username')) ?></label>
            <input name="username" placeholder="<?= h(t('admin_username')) ?>" required>

            <label><?= h(t('password')) ?></label>
            <input type="password" name="password" placeholder="<?= h(t('password')) ?>" required>

            <label><?= h(t('confirm_password')) ?></label>
            <input type="password" name="password2" placeholder="<?= h(t('confirm_password')) ?>" required>

            <button><?= h(t('create_archive')) ?></button>
        </form>
    </div>
</div>

<script src="assets/js/theme.js"></script>
</body>
</html>
