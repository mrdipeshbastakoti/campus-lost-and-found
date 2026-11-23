<?php
require_once __DIR__ . '/bootstrap.php';
$pageTitle = $pageTitle ?? 'Campus Lost & Found';
$activePage = $activePage ?? 'home';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escape($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/accounts.css">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
    <div class="container header-content">
        <a class="site-name" href="index.php">Campus Lost &amp; Found<span>Dong-Eui University</span></a>
        <nav aria-label="Main navigation">
            <a href="index.php" <?= $activePage === 'home' ? 'aria-current="page"' : '' ?>>Home</a>
            <a href="search.php" <?= $activePage === 'search' ? 'aria-current="page"' : '' ?>>Search items</a>
            <a href="index.php#how-it-works">How it works</a>
            <?php if (AuthService::currentUserId() === null): ?>
                <a href="register.php" <?= $activePage === 'register' ? 'aria-current="page"' : '' ?>>Register</a>
                <a href="login.php" <?= $activePage === 'login' ? 'aria-current="page"' : '' ?>>Log in</a>
            <?php else: ?>
                <form class="logout-form" action="logout.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= escape(Session::csrfToken()) ?>">
                    <button type="submit" class="nav-button">Log out</button>
                </form>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main id="main" class="container">
