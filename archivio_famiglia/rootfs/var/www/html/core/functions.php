<?php
// core/functions.php

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Lingue supportate da FamilyDocs.
 */
function supportedLanguages(): array
{
    return ['it', 'en'];
}

/**
 * Normalizza una lingua richiesta.
 */
function normalizeLanguage(?string $lang): string
{
    $lang = strtolower(trim((string)$lang));

    if (in_array($lang, supportedLanguages(), true)) {
        return $lang;
    }

    return 'it';
}

/**
 * Restituisce la lingua corrente.
 * Priorità:
 * 1. parametro URL ?lang=it / ?lang=en
 * 2. sessione
 * 3. cookie
 * 4. italiano
 */
function currentLanguage(): string
{
    if (isset($_GET['lang'])) {
        $lang = normalizeLanguage($_GET['lang']);
        $_SESSION['lang'] = $lang;
        setcookie('familydocs_lang', $lang, time() + (86400 * 365), '/');

        return $lang;
    }

    if (!empty($_SESSION['lang'])) {
        return normalizeLanguage($_SESSION['lang']);
    }

    if (!empty($_COOKIE['familydocs_lang'])) {
        $lang = normalizeLanguage($_COOKIE['familydocs_lang']);
        $_SESSION['lang'] = $lang;

        return $lang;
    }

    return 'it';
}

/**
 * Carica il file lingua.
 */
function loadLanguageFile(string $lang): array
{
    $lang = normalizeLanguage($lang);
    $file = dirname(__DIR__) . '/lang/' . $lang . '.php';

    if (is_file($file)) {
        $data = require $file;

        if (is_array($data)) {
            return $data;
        }
    }

    return [];
}

/**
 * Traduzione UI.
 * Uso:
 * echo t('login');
 *
 * Fallback:
 * - lingua corrente
 * - italiano
 * - chiave stessa
 */
function t(string $key): string
{
    static $cache = [];

    $lang = currentLanguage();

    if (!isset($cache[$lang])) {
        $cache[$lang] = loadLanguageFile($lang);
    }

    if (!isset($cache['it'])) {
        $cache['it'] = loadLanguageFile('it');
    }

    if (isset($cache[$lang][$key])) {
        return (string)$cache[$lang][$key];
    }

    if (isset($cache['it'][$key])) {
        return (string)$cache['it'][$key];
    }

    return $key;
}

/**
 * Crea URL mantenendo la lingua selezionata.
 */
function urlWithLang(string $url): string
{
    $lang = currentLanguage();

    if ($lang === 'it') {
        return $url;
    }

    $separator = str_contains($url, '?') ? '&' : '?';

    return $url . $separator . 'lang=' . urlencode($lang);
}

function isLogged(): bool
{
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLogged()) {
        header("Location: " . urlWithLang('login.php'));
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
        die(t('access_denied'));
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
    header("Location: " . urlWithLang($url));
    exit;
}
