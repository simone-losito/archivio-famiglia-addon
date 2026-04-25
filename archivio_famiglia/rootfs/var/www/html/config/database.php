<?php
// config/database.php

$optionsFile = '/data/options.json';

$dbHost = 'core-mariadb';
$dbName = 'homeassistant';
$dbUser = 'homeassistant';
$dbPass = '';

if (is_file($optionsFile)) {
    $options = json_decode(file_get_contents($optionsFile), true);

    if (is_array($options)) {
        $dbHost = $options['db_host'] ?? $dbHost;
        $dbName = $options['db_name'] ?? $dbName;
        $dbUser = $options['db_user'] ?? $dbUser;
        $dbPass = $options['db_pass'] ?? $dbPass;
    }
}

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName, 3306);

if ($conn->connect_error) {
    die("Errore connessione database: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
