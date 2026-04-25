<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function tableExists(mysqli $conn, string $table): bool {
    $table = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$table}'");
    return $res && $res->num_rows > 0;
}

function createDemoPdf(string $path): void {
    $pdf = "%PDF-1.4
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>
endobj
4 0 obj
<< /Length 214 >>
stream
BT
/F1 28 Tf
60 760 Td
(Archivio Famiglia) Tj
/F1 16 Tf
0 -45 Td
(Documento dimostrativo creato automaticamente.) Tj
0 -30 Td
(Questo PDF serve per testare tutte le funzioni.) Tj
0 -45 Td
(Buon utilizzo!) Tj
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
0000000515 00000 n 
trailer
<< /Size 6 /Root 1 0 R >>
startxref
585
%%EOF";

    file_put_contents($path, $pdf);
}

// Controllo installazione robusto
$installNeeded = false;

if (!tableExists($conn, 'utenti')) {
    $installNeeded = true;
} else {
    $res = $conn->query("SELECT COUNT(*) AS totale FROM utenti");
    $count = $res ? (int)($res->fetch_assoc()['totale'] ?? 0) : 0;

    if ($count === 0) {
        $installNeeded = true;
    }
}

if (!$installNeeded) {
    header("Location: login.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($username === '') {
        $error = 'Inserisci username';
    } elseif (strlen($password) < 4) {
        $error = 'Password troppo corta';
    } elseif ($password !== $password2) {
        $error = 'Le password non coincidono';
    } else {

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

        $defaults = [
            'cartelle_cliniche' => 'Cartelle cliniche',
            'referti' => 'Referti',
            'casa' => 'Casa',
            'auto' => 'Auto',
            'altro' => 'Altro'
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

        $titolo = 'Documento dimostrativo';
        $originale = 'documento_dimostrativo.pdf';
        $categoria = 'referti';
        $note = 'Creato automaticamente dal wizard iniziale.';
        $tags = 'demo, esempio, primo avvio';
        $today = date('Y-m-d');

        $stmt = $conn->prepare("
            INSERT INTO documenti
            (nome_archivio, nome_originale, titolo, categoria, note, tags, data_documento, preferito)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->bind_param("sssssss", $demoFile, $originale, $titolo, $categoria, $note, $tags, $today);
        $stmt->execute();

        header("Location: login.php?installed=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Installazione Archivio Famiglia</title>
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
</style>
</head>
<body>

<div class="login-wrap">
    <div class="card login-card">
        <span class="badge">Primo avvio</span>
        <h1>🚀 Archivio Famiglia</h1>
        <p>Crea il primo amministratore. Tabelle, categorie demo e PDF dimostrativo verranno creati automaticamente.</p>

        <?php if($error): ?>
            <p class="error"><?= h($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label>Username admin</label>
            <input name="username" placeholder="Username admin" required>

            <label>Password</label>
            <input type="password" name="password" placeholder="Password" required>

            <label>Conferma password</label>
            <input type="password" name="password2" placeholder="Conferma password" required>

            <button>Crea archivio</button>
        </form>
    </div>
</div>

<script src="assets/js/theme.js"></script>
</body>
</html>
