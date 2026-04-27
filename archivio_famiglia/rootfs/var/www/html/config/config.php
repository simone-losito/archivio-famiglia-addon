<?php
// config/config.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'FamilyDocs');
define('APP_SUBTITLE', 'Document Manager for Home Assistant (ex Archivio Famiglia)');
define('APP_VERSION', '1.1.2');

define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_DIR', BASE_PATH . '/uploads');
define('BACKUP_DIR', BASE_PATH . '/backups');

define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50 MB

define('ALLOWED_EXTENSIONS', [
    'pdf',
    'jpg',
    'jpeg',
    'png',
    'webp',
    'doc',
    'docx',
    'xls',
    'xlsx',
    'txt'
]);

define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'image/webp',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain'
]);

date_default_timezone_set('Europe/Rome');
