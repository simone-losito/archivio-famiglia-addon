<?php
// config/database.php

// Percorso file opzioni Home Assistant
$optionsFile = __DIR__ . '/addon_options.json';

// Valori di default (fallback)
$dbHost = 'core-mariadb';
$dbName = 'homeassistant';
$dbUser = 'homeassistant';
$dbPass = '';
$dbPort = 3306;

// Caricamento configurazione da Home Assistant
if (is_file($optionsFile) && is_readable($optionsFile)) {
    $json = file_get_contents($optionsFile);
    $options = json_decode($json, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($options)) {
        $dbHost = $options['db_host'] ?? $dbHost;
        $dbName = $options['db_name'] ?? $dbName;
        $dbUser = $options['db_user'] ?? $dbUser;

        if (isset($options['db_pass']) && is_string($options['db_pass'])) {
            $dbPass = $options['db_pass'];
        }
    }
}

// Creazione connessione
mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);

// Controllo connessione
if ($conn->connect_error) {
    http_response_code(500);
    die("Errore connessione database. Verifica configurazione add-on.");
}

// Charset corretto per emoji e compatibilità
if (!$conn->set_charset("utf8mb4")) {
    // fallback silenzioso
}

// Collation consigliata
$conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
