<?php
declare(strict_types=1);

final class PhotoStorage
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    private string $directory;

    public function __construct(?string $root = null)
    {
        if ($root === null) {
            $config = require __DIR__ . '/../config/config.php';
            $root = $config['storage_path'];
        }
        $directory = realpath($root . '/items');
        if ($directory === false || !is_dir($directory)) {
            throw new RuntimeException('The configured photo storage folder is missing.');
        }
        $this->directory = $directory;
    }

    public function save(?array $upload): ?string
    {
        if ($upload === null) {
            return null;
        }
        $error = $upload['error'] ?? null;
        if (!is_int($error)) {
            throw new InvalidArgumentException('Choose one JPG or PNG photo.');
        }
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new InvalidArgumentException('The photo must be 5 MB or smaller.');
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('The photo upload did not finish. Choose the file again.');
        }
        $temporary = $upload['tmp_name'] ?? null;
        if (!is_string($temporary) || !is_uploaded_file($temporary)) {
            throw new InvalidArgumentException('Choose a valid uploaded photo.');
        }
        $size = filesize($temporary);
        if ($size === false || $size < 1 || $size > self::MAX_BYTES) {
            throw new InvalidArgumentException('The photo must be between 1 byte and 5 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporary);
        $image = @getimagesize($temporary);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($extensions[$mime]) || $image === false || ($image['mime'] ?? '') !== $mime) {
            throw new InvalidArgumentException('Upload an actual JPG or PNG image.');
        }
        $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!@move_uploaded_file($temporary, $this->directory . '/' . $name)) {
            throw new RuntimeException('The photo could not be saved.');
        }
        return 'items/' . $name;
    }

    public function path(string $relative): string
    {
        if (!preg_match('/\Aitems\/[a-f0-9]{32}\.(?:jpg|png)\z/', $relative)) {
            throw new DomainException('Photo not found.', 404);
        }
        $path = realpath($this->directory . '/' . basename($relative));
        if ($path === false || dirname($path) !== $this->directory || !is_file($path)) {
            throw new DomainException('Photo not found.', 404);
        }
        return $path;
    }

    public function delete(string $relative): void
    {
        if (!@unlink($this->path($relative))) {
            throw new RuntimeException('The unused photo could not be removed.');
        }
    }
}
