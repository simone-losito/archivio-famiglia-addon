<?php
// core/telemetry.php
// Telemetria anonima opzionale, disattivata di default.

function archivio_load_addon_options(): array
{
    $file = __DIR__ . '/../config/addon_options.json';

    if (!is_file($file) || !is_readable($file)) {
        return [];
    }

    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function archivio_send_telemetry_once(string $event = 'app_open'): void
{
    $options = archivio_load_addon_options();

    $enabled = !empty($options['telemetry_enabled']);
    $endpoint = trim((string)($options['telemetry_endpoint'] ?? ''));

    if (!$enabled || $endpoint === '') {
        return;
    }

    $lockFile = sys_get_temp_dir() . '/archivio_telemetry_' . md5($event) . '.lock';

    // massimo 1 ping ogni 24 ore per installazione/container
    if (is_file($lockFile) && (time() - filemtime($lockFile)) < 86400) {
        return;
    }

    $payload = [
        'event' => $event,
        'app' => 'archivio_famiglia',
        'version' => '1.0.8',
        'php' => PHP_VERSION,
        'time' => date('c'),
    ];

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'timeout' => 2,
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($payload),
        ]
    ]);

    @file_get_contents($endpoint, false, $context);
    @touch($lockFile);
}
