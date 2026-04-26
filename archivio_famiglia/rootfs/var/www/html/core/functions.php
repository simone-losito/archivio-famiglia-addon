<?php
// core/functions.php

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function isLogged(): bool
{
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLogged()) {
        header("Location: login.php");
        exit;
    }
}

function currentUser(): array
{
    return [
        'id' => (int)($_SESSION['user_id'] ?? 0),
        'username' => $_SESSION['username'] ?? '',
        'ruolo' => $_SESSION['ruolo'] ?? 'user',
    ];
}

function isAdmin(): bool
{
    return ($_SESSION['ruolo'] ?? '') === 'admin';
}

function requireAdmin(): void
{
    requireLogin();

    if (!isAdmin()) {
        http_response_code(403);
        die("Accesso negato");
    }
}

function slugify(string $text): string
{
    $text = trim($text);
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = strtolower($text ?: '');
    $text = preg_replace('/[^a-z0-9]+/i', '_', $text);
    $text = trim($text, '_');

    return $text !== '' ? $text : 'categoria';
}

function getCategories(): array
{
    global $conn;

    $cats = [];

    if (!isset($conn) || !$conn instanceof mysqli) {
        return $cats;
    }

    $res = $conn->query("SELECT slug, nome FROM categorie ORDER BY nome ASC");

    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $cats[$r['slug']] = $r['nome'];
        }
    }

    return $cats;
}

function safeCategory(string $category): string
{
    $cats = getCategories();

    if (isset($cats[$category])) {
        return $category;
    }

    if (isset($cats['altro'])) {
        return 'altro';
    }

    $first = array_key_first($cats);

    return $first ?: 'altro';
}

function safeFilename(string $filename): string
{
    $filename = basename($filename);
    $filename = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $filename);
    $filename = trim($filename, '._-');

    return $filename !== '' ? $filename : 'file';
}

function formatBytes(int $bytes): string
{
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . ' GB';
    }

    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    }

    if ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }

    return $bytes . ' B';
}

function redirect(string $url): void
{
    header("Location: " . $url);
    exit;
}
