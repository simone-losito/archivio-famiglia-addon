<?php

function isLogged(): bool
{
    return !empty($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLogged()) {
        header("Location: login.php");
        exit;
    }
}

function currentUser(): array
{
    return [
        'id' => $_SESSION['user_id'] ?? 0,
        'username' => $_SESSION['username'] ?? '',
        'ruolo' => $_SESSION['ruolo'] ?? 'user',
    ];
}

function isAdmin(): bool
{
    return ($_SESSION['ruolo'] ?? '') === 'admin';
}

function requireAdmin()
{
    requireLogin();

    if (!isAdmin()) {
        die("Accesso negato");
    }
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/i', '_', $text);
    return trim($text, '_') ?: 'categoria';
}

function getCategories(): array
{
    global $conn;

    $cats = [];
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

    return isset($cats['altro']) ? 'altro' : array_key_first($cats);
}
