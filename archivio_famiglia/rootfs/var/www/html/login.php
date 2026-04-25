<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$error = '';

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
    }

    $error = "Credenziali errate";
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Login</title>
<link rel="stylesheet" href="assets/css/archivio.css">
</head>
<body>
<div class="main" style="width:100%;">
  <div class="card" style="max-width:420px;margin:80px auto;">
    <h1>Login</h1>
    <?php if($error): ?><p class="success"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="POST">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button>Entra</button>
    </form>
  </div>
</div>
<script src="assets/js/theme.js"></script>
</body>
</html>
