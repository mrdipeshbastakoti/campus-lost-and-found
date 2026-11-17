<?php
$pageTitle = $pageTitle ?? 'Campus Lost & Found';
$activePage = $activePage ?? 'home';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
    <div class="container header-content">
        <a class="site-name" href="index.php">Campus Lost &amp; Found<span>Dong-Eui University</span></a>
        <nav aria-label="Main navigation">
            <a href="index.php" <?= $activePage === 'home' ? 'aria-current="page"' : '' ?>>Home</a>
            <a href="index.php#how-it-works">How it works</a>
        </nav>
    </div>
</header>
<main id="main" class="container">
