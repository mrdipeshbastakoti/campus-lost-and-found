<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (AuthService::currentUserId() !== null) {
    header('Location: index.php');
    exit;
}

$error = '';
$name = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(postString('name'));
    $email = trim(postString('email'));
    if (!Session::validCsrf(postString('csrf_token'))) {
        http_response_code(403);
        $error = 'The form expired. Please try again.';
    } elseif (postString('password') !== postString('password_confirmation')) {
        http_response_code(422);
        $error = 'The passwords do not match.';
    } else {
        try {
            AuthService::register($name, $email, postString('password'));
            header('Location: login.php?registered=1', true, 303);
            exit;
        } catch (InvalidArgumentException $exception) {
            http_response_code(422);
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            error_log('Registration: ' . $exception->getMessage());
            http_response_code(503);
            $error = 'Registration is unavailable right now. Please try again later.';
        }
    }
}

$pageTitle = 'Register | Campus Lost & Found';
$activePage = 'register';
require __DIR__ . '/includes/header.php';
?>
<section class="account-panel">
    <h1>Create an account</h1>
    <p>Register to post reports and claim your belongings.</p>
    <?php if ($error !== ''): ?>
        <p class="form-error" role="alert"><?= escape($error) ?></p>
    <?php endif; ?>
    <form action="register.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= escape(Session::csrfToken()) ?>">
        <label class="form-field">Name
            <input type="text" name="name" value="<?= escape($name) ?>" maxlength="100" autocomplete="name" required>
        </label>
        <label class="form-field">Email
            <input type="email" name="email" value="<?= escape($email) ?>" maxlength="255" autocomplete="email" required>
        </label>
        <label class="form-field">Password
            <input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" aria-describedby="password-help" required>
        </label>
        <p id="password-help" class="form-help">Use 8–72 bytes. English letters, numbers, and symbols use one byte each.</p>
        <label class="form-field">Confirm password
            <input type="password" name="password_confirmation" minlength="8" maxlength="72" autocomplete="new-password" required>
        </label>
        <button class="button" type="submit">Create account</button>
    </form>
    <p class="account-link">Already registered? <a href="login.php">Log in</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
