<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/telemetry.php';
archivio_send_telemetry_once('login_page_open');

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function tableExists(mysqli $conn, string $table): bool {
    $table = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$table}'");
    return $res && $res->num_rows > 0;
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

if ($installNeeded) {
    header("Location: install.php");
    exit;
}

$error = '';
$installed = isset($_GET['installed']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM utenti WHERE username = ? AND attivo = 1 LIMIT 1");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $utente = $stmt->get_result()->fetch_assoc();

    if ($utente && password_verify($pass, $utente['password_hash'])) {
        $_SESSION['user_id'] = (int)$utente['id'];
        $_SESSION['username'] = $utente['username'];
        $_SESSION['ruolo'] = $utente['ruolo'];

        header("Location: index.php");
        exit;
    } else {
        $error = "Credenziali errate";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Login Archivio</title>
<link rel="stylesheet" href="assets/css/archivio.css">
<style>
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
.login-card{width:100%;max-width:460px;}
</style>
</head>
<body>

<div class="login-wrap">
    <div class="card login-card">
        <span class="badge">Archivio Famiglia</span>
        <h1>Login</h1>

        <?php if($installed): ?>
            <p class="success">Installazione completata. Accedi con l’utente amministratore appena creato.</p>
        <?php endif; ?>

        <?php if($error): ?>
            <p class="error"><?= h($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label>Username</label>
            <input name="username" placeholder="Username" required>

            <label>Password</label>
            <input name="password" placeholder="Password" required type="password">

            <button>Entra</button>
        </form>
    </div>
</div>

<script src="assets/js/theme.js"></script>
</body>
</html>
