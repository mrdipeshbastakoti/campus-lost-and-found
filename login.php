<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (AuthService::currentUserId() !== null) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(postString('email'));
    if (!Session::validCsrf(postString('csrf_token'))) {
        http_response_code(403);
        $error = 'The form expired. Please try again.';
    } else {
        try {
            if (AuthService::login($email, postString('password'))) {
                header('Location: index.php', true, 303);
                exit;
            }
            http_response_code(422);
            $error = 'Email or password is incorrect.';
        } catch (Throwable $exception) {
            error_log('Login: ' . $exception->getMessage());
            http_response_code(503);
            $error = 'Login is unavailable right now. Please try again later.';
        }
    }
}

$pageTitle = 'Log in | Campus Lost & Found';
$activePage = 'login';
require __DIR__ . '/includes/header.php';
?>
<section class="account-panel">
    <h1>Log in</h1>
    <?php if (($_GET['registered'] ?? '') === '1'): ?>
        <p class="form-success" role="status">Your account was created. You can log in now.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="form-error" role="alert"><?= escape($error) ?></p>
    <?php endif; ?>
    <form action="login.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= escape(Session::csrfToken()) ?>">
        <label class="form-field">Email
            <input type="email" name="email" value="<?= escape($email) ?>" maxlength="255" autocomplete="email" required>
        </label>
        <label class="form-field">Password
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button class="button" type="submit">Log in</button>
    </form>
    <p class="account-link">New here? <a href="register.php">Create an account</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
