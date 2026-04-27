<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireLogin();

/* Parametri */
$id = (int)($_GET['id'] ?? 0);
$back = (string)($_GET['back'] ?? 'index.php');

/* Sicurezza minima sul redirect (evita link esterni) */
if (!str_starts_with($back, 'index.php') && !str_starts_with($back, 'view.php')) {
    $back = 'index.php';
}

/* Toggle preferito */
if ($id > 0) {
    $stmt = $conn->prepare("
        UPDATE documenti 
        SET preferito = IF(preferito = 1, 0, 1) 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

/* Mantiene lingua */
$back = urlWithLang($back);

/* Redirect */
header("Location: " . $back);
exit;
