<?php

function ensureOcrColumns(): void
{
    global $conn;

    $conn->query("ALTER TABLE documenti ADD COLUMN ocr_text LONGTEXT NULL");
}

function runOcrOnImage(string $filePath): string
{
    if (!is_file($filePath)) return '';

    $cmd = "tesseract " . escapeshellarg($filePath) . " stdout -l ita+eng 2>/dev/null";
    $output = shell_exec($cmd);

    if (!$output) return '';

    return trim($output);
}
