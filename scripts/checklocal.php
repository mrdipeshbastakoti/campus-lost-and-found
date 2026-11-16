<?php
declare(strict_types=1);

if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(404);
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
ini_set('display_errors', '0');

require_once __DIR__ . '/../src/Database.php';

try {
    echo "PASS: Apache is running PHP in your browser.\n";
    $pdo = Database::connect();
    foreach (['users', 'item_reports', 'claims'] as $table) {
        $pdo->query('SELECT 1 FROM ' . $table . ' LIMIT 1');
    }
    echo "PASS: PHP connected and all three tables are accessible.\n";

    $config = require __DIR__ . '/../config/config.php';
    $directory = $config['storage_path'] . '/items';
    if (!is_dir($directory)) {
        throw new RuntimeException('The configured storage/items folder does not exist.');
    }
    $probe = $directory . '/setup-check-' . bin2hex(random_bytes(16)) . '.tmp';
    if (@file_put_contents($probe, 'Local setup check', LOCK_EX) === false) {
        throw new RuntimeException('Apache cannot write to the photo-storage folder.');
    }
    if (!@unlink($probe)) {
        throw new RuntimeException('Apache cannot remove its temporary storage check file.');
    }
    echo "PASS: Apache can write and remove files in photo storage.\n";
    echo "\nFoundation checks passed. Actual photo uploads will be tested with the report feature.\n";
} catch (Throwable $error) {
    http_response_code(500);
    echo 'FAIL: ' . $error->getMessage() . "\n";
}
