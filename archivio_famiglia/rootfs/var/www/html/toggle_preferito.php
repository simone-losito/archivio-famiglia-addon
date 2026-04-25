<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
$back = $_GET['back'] ?? 'index.php';

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE documenti SET preferito = IF(preferito=1,0,1) WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: " . $back);
exit;
