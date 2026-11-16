<?php
declare(strict_types=1);

// Run from XAMPP Shell. This diagnostic is deliberately CLI-only.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../src/Database.php';

try {
    $pdo = Database::connect();
    foreach (['users', 'item_reports', 'claims'] as $table) {
        // Names come only from the fixed list above.
        $pdo->query("SELECT 1 FROM " . $table . " LIMIT 1");
    }
    echo "PASS: PHP connected and all three tables are accessible." . PHP_EOL;

    $config = require __DIR__ . '/../config/config.php';
    $directory = $config['storage_path'] . '/items';
    if (!is_dir($directory) || !is_writable($directory)) {
        throw new RuntimeException('Create the configured storage/items folder and make it writable.');
    }
    echo "PASS: Photo storage folder is writable from PHP CLI." . PHP_EOL;
    echo "Server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . PHP_EOL;
    echo "Apache upload handling will be checked when the report feature is built." . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, "FAIL: " . $error->getMessage() . PHP_EOL);
    exit(1);
}
