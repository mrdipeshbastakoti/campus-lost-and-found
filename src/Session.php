<?php
declare(strict_types=1);

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('campus_lost_found');
        $path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => rtrim($path, '/') . '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (!session_start()) {
            throw new RuntimeException('Could not start the session.');
        }
    }

    public static function csrfToken(): string
    {
        if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function validCsrf(string $token): bool
    {
        return $token !== '' && hash_equals(self::csrfToken(), $token);
    }
}
