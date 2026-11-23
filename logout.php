<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Use the Log out button on the website.');
}

if (!Session::validCsrf(postString('csrf_token'))) {
    http_response_code(403);
    exit('The form expired. Return to the homepage, refresh, and try again.');
}

AuthService::logout();
header('Location: index.php', true, 303);
exit;
