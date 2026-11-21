<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class AuthService
{
    public static function register(string $name, string $email, string $password): int
    {
        $name = trim($name);
        $email = strtolower(trim($email));

        if (!preg_match('/\A.{1,100}\z/us', $name)) {
            throw new InvalidArgumentException('Enter a name between 1 and 100 characters.');
        }
        if (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Enter a valid email address.');
        }
        if (strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
            throw new InvalidArgumentException('Use a password between 8 and 72 bytes (English characters use one byte each).');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo = Database::connect();
        $statement = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');

        try {
            $statement->execute([$name, $email, $hash]);
        } catch (PDOException $error) {
            if (($error->errorInfo[1] ?? null) === 1062) {
                throw new InvalidArgumentException('This email is already registered. Please log in.');
            }
            throw $error;
        }

        return (int) $pdo->lastInsertId();
    }

    public static function login(string $email, string $password): bool
    {
        $email = strtolower(trim($email));
        if (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
            return false;
        }

        $statement = Database::connect()->prepare('SELECT user_id, password_hash FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Could not refresh the session.');
        }
        $_SESSION = ['user_id' => (int) $user['user_id']];
        return true;
    }

    public static function currentUserId(): ?int
    {
        $id = $_SESSION['user_id'] ?? null;
        return is_int($id) && $id > 0 ? $id : null;
    }

    public static function requireLogin(): int
    {
        $id = self::currentUserId();
        if ($id === null) {
            header('Location: login.php');
            exit;
        }
        return $id;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'],
        ]);
        session_destroy();
    }
}
