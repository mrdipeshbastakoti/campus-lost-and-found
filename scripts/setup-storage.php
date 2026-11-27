<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    $configPath = __DIR__ . '/../config/config.php';
    if (!is_file($configPath)) {
        throw new RuntimeException('Copy config/config.example.php to config/config.php first.');
    }
    $config = require $configPath;
    $root = is_array($config) ? ($config['storage_path'] ?? null) : null;
    if (!is_string($root) || trim($root) === '' || strpos($root, "\0") !== false) {
        throw new RuntimeException('Set storage_path to your local photo storage folder in config/config.php.');
    }

    if (PHP_OS_FAMILY === 'Windows') {
        $normalized = str_replace('\\', '/', $root);
        $absolute = preg_match('~\A[A-Za-z]:/~', $normalized) === 1
            || preg_match('~\A//[^/]+/[^/]+(?:/|$)~', $normalized) === 1;
    } else {
        $absolute = strpos($root, '/') === 0;
    }
    if (!$absolute) {
        throw new RuntimeException('storage_path must be an absolute path for this operating system.');
    }

    $directory = rtrim($root, '/\\') . '/items';
    if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Cannot create the configured items folder. Check storage_path and folder permissions.');
    }

    $probe = $directory . '/.setup-storage-' . bin2hex(random_bytes(16)) . '.tmp';
    $handle = @fopen($probe, 'x');
    if ($handle === false) {
        throw new RuntimeException('PHP CLI cannot write to the configured items folder.');
    }
    try {
        $message = 'Photo storage check';
        if (@fwrite($handle, $message) !== strlen($message) || !@fflush($handle)) {
            throw new RuntimeException('PHP CLI could not finish writing the storage check file.');
        }
    } finally {
        fclose($handle);
        if (!@unlink($probe)) {
            throw new RuntimeException('PHP CLI cannot remove its temporary storage check file: ' . $probe);
        }
    }

    echo 'PASS: Photo storage folder is ready: ' . realpath($directory) . PHP_EOL;
    echo 'PASS: PHP CLI can write and remove files in photo storage.' . PHP_EOL;
    echo 'Next: run php scripts/check-setup.php and open scripts/checklocal.php through localhost to check Apache.' . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
