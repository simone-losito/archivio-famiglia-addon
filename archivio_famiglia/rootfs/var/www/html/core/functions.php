<?php
// core/functions.php

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function supportedLanguages(): array
{
    return ['it', 'en'];
}

function normalizeLanguage(?string $lang): string
{
    $lang = strtolower(trim((string)$lang));

    return in_array($lang, supportedLanguages(), true) ? $lang : 'it';
}

function currentLanguage(): string
{
    if (isset($_GET['lang'])) {
        $lang = normalizeLanguage($_GET['lang']);

        $_SESSION['lang'] = $lang;

        if (!headers_sent()) {
            setcookie('familydocs_lang', $lang, time() + (86400 * 365), '/', '', false, true);
        }

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

function urlWithLang(string $url): string
{
    $lang = currentLanguage();

    if ($lang === 'it') {
        return $url;
    }

    if (preg_match('/([?&])lang=(it|en)/', $url)) {
        return preg_replace('/([?&])lang=(it|en)/', '$1lang=' . urlencode($lang), $url);
    }

    $separator = str_contains($url, '?') ? '&' : '?';

    return $url . $separator . 'lang=' . urlencode($lang);
}

function languageUrl(string $lang): string
{
    $lang = normalizeLanguage($lang);

    $path = $_SERVER['PHP_SELF'] ?? 'index.php';
    $query = $_GET ?? [];
    $query['lang'] = $lang;

    return $path . '?' . http_build_query($query);
}

function languageSwitchHtml(): string
{
    $lang = currentLanguage();

    return '
    <div class="language-switch">
        <a href="' . h(languageUrl('it')) . '" class="' . ($lang === 'it' ? 'active' : '') . '">IT</a>
        <a href="' . h(languageUrl('en')) . '" class="' . ($lang === 'en' ? 'active' : '') . '">EN</a>
    </div>';
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
