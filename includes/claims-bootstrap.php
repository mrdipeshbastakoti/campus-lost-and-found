<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../src/ClaimService.php';

$claimActorId = AuthService::requireLogin();

function claimPositiveId(mixed $value): int
{
    if (!is_string($value) || !preg_match('/\A[0-9]+\z/', $value)) {
        throw new InvalidArgumentException('Choose a valid report or claim.');
    }
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        throw new InvalidArgumentException('Choose a valid report or claim.');
    }
    return $id;
}

function claimPageError(Throwable $error): string
{
    if ($error instanceof DomainException) {
        http_response_code($error->getCode());
        return $error->getMessage();
    }
    if ($error instanceof InvalidArgumentException) {
        http_response_code(422);
        return $error->getMessage();
    }
    error_log('Claims: ' . $error->getMessage());
    http_response_code(503);
    return 'Claims are unavailable right now. Please try again later.';
}
