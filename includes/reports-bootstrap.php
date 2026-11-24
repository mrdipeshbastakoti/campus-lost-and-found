<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../src/ReportService.php';

function reportId($value): int
{
    if (!is_string($value) || !preg_match('/\A[1-9][0-9]*\z/', $value)) {
        throw new InvalidArgumentException('Choose a valid report.');
    }
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        throw new InvalidArgumentException('Choose a valid report.');
    }
    return $id;
}

function reportError(Throwable $exception): string
{
    if ($exception instanceof InvalidArgumentException) {
        http_response_code(422);
        return $exception->getMessage();
    }
    if ($exception instanceof DomainException && in_array($exception->getCode(), [403, 404, 409, 413], true)) {
        http_response_code($exception->getCode());
        return $exception->getMessage();
    }
    error_log('Item reports: ' . $exception->getMessage());
    http_response_code(503);
    return 'Reports are unavailable right now. Check the local database and photo storage, then try again.';
}
