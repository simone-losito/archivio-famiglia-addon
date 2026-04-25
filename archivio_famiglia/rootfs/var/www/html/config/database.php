<?php
$DB_HOST = 'homeassistant';
$DB_NAME = 'homeassistant';
$DB_USER = 'homeassistant';
$DB_PASS = 'la_tua_password';
$DB_PORT = 3306;

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
if ($conn->connect_error) {
    die("Errore DB: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
