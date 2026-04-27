<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

$telemetryFile = __DIR__ . '/core/telemetry.php';
if (is_file($telemetryFile)) {
    require_once $telemetryFile;
    if (function_exists('archivio_send_telemetry_once')) {
        archivio_send_telemetry_once('login_page_open');
    }
}

function tableExists(mysqli $conn, string $table): bool
{
    $table = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$table}'");
    return $res && $res->num_rows > 0;
}

$installNeeded = false;

if (!tableExists($conn, 'utenti')) {
    $installNeeded = true;
} else {
    $res = $conn->query("SELECT COUNT(*) AS totale FROM utenti");
    $count = $res ? (int)($res->fetch_assoc()['totale'] ?? 0) : 0;
    $installNeeded = ($count === 0);
}

if ($installNeeded) {
    header("Location: " . urlWithLang('install.php'));
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
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$utente['id'];
        $_SESSION['username'] = $utente['username'];
        $_SESSION['ruolo'] = $utente['ruolo'];

        header("Location: " . urlWithLang('index.php'));
        exit;
    }

    $error = t('invalid_credentials');
}

$lang = currentLanguage();
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>
<meta charset="UTF-8">
<title><?= h(t('login')) ?> - <?= h(t('app_name')) ?></title>
<link rel="stylesheet" href="assets/css/archivio.css">
<style>
.login-wrap{
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:24px;
    width:100%;
}

.login-card{
    width:100%;
    max-width:460px;
}

.login-card .language-switch{
    justify-content:flex-end;
    margin-top:0;
    margin-bottom:16px;
}

.login-subtitle{
    margin-top:-8px;
    color:var(--muted);
}
</style>
</head>
<body>

<div class="login-wrap">
    <div class="card login-card">

        <?= languageSwitchHtml() ?>

        <span class="badge"><?= h(t('app_name')) ?></span>
        <h1><?= h(t('login')) ?></h1>
        <p class="login-subtitle"><?= h(t('app_subtitle')) ?></p>

        <?php if ($installed): ?>
            <p class="success"><?= h(t('install_completed_login')) ?></p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error"><?= h($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <?php if ($lang !== 'it'): ?>
                <input type="hidden" name="lang" value="<?= h($lang) ?>">
            <?php endif; ?>

            <label><?= h(t('username')) ?></label>
            <input name="username" placeholder="<?= h(t('username')) ?>" required>

            <label><?= h(t('password')) ?></label>
            <input name="password" placeholder="<?= h(t('password')) ?>" required type="password">

            <button><?= h(t('sign_in')) ?></button>
        </form>
    </div>
</div>

<script src="assets/js/theme.js"></script>
</body>
</html>
