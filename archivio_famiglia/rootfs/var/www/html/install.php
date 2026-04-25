<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function tableExists(mysqli $conn, string $table): bool {
    $stmt = $conn->prepare("SHOW TABLES LIKE ?");
    $stmt->bind_param("s", $table);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
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

//
// 🔒 CONTROLLO INSTALLAZIONE ROBUSTO
//
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

//
// 🚀 INSTALLAZIONE
//
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($username === '') {
        $error = 'Inserisci username';
    } elseif (strlen($password) < 4) {
        $error = 'Password troppo corta';
    } elseif ($password !== $password2) {
        $error = 'Le password non coincidono';
    } else {

        // Tabelle
        $conn->query("
            CREATE TABLE IF NOT EXISTS utenti (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(80) UNIQUE,
                password_hash VARCHAR(255),
                ruolo VARCHAR(30) DEFAULT 'user',
                attivo TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB CHARSET=utf8mb4;
        ");

        $conn->query("
            CREATE TABLE IF NOT EXISTS categorie (
                id INT AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(100) UNIQUE,
                nome VARCHAR(150)
            ) ENGINE=InnoDB CHARSET=utf8mb4;
        ");

        $conn->query("
            CREATE TABLE IF NOT EXISTS documenti (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome_archivio VARCHAR(255),
                nome_originale VARCHAR(255),
                titolo VARCHAR(255),
                categoria VARCHAR(100),
                note TEXT,
                tags VARCHAR(255),
                data_documento DATE,
                preferito TINYINT(1) DEFAULT 0,
                data_upload DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB CHARSET=utf8mb4;
        ");

        $conn->query("
            CREATE TABLE IF NOT EXISTS share_links (
                id INT AUTO_INCREMENT PRIMARY KEY,
                token VARCHAR(80) UNIQUE,
                categoria VARCHAR(100),
                nome_archivio VARCHAR(255),
                expires_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB CHARSET=utf8mb4;
        ");

        // Categorie demo
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

        // Admin
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO utenti (username, password_hash, ruolo) VALUES (?, ?, 'admin')");
        $stmt->bind_param("ss", $username, $hash);
        $stmt->execute();

        // PDF demo
        $demoDir = UPLOAD_DIR . '/referti';
        if (!is_dir($demoDir)) mkdir($demoDir, 0775, true);

        $demoFile = 'DOC-0001.pdf';
        $demoPath = $demoDir . '/' . $demoFile;

        if (!file_exists($demoPath)) {
            createDemoPdf($demoPath);
        }

        $stmt = $conn->prepare("
            INSERT INTO documenti
            (nome_archivio, nome_originale, titolo, categoria, note, tags, data_documento, preferito)
            VALUES (?, ?, 'Documento dimostrativo', 'referti', 'Creato automaticamente', 'demo', ?, 1)
        ");
        $today = date('Y-m-d');
        $stmt->bind_param("sss", $demoFile, $demoFile, $today);
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
<title>Installazione</title>
<link rel="stylesheet" href="assets/css/archivio.css">
</head>
<body>

<div class="login-wrap">
    <div class="card login-card">
        <h1>🚀 Primo avvio</h1>

        <?php if($error): ?>
            <p class="error"><?= h($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <input name="username" placeholder="Username admin" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="password2" placeholder="Conferma password" required>
            <button>Crea archivio</button>
        </form>
    </div>
</div>

<script src="assets/js/theme.js"></script>
</body>
</html>
