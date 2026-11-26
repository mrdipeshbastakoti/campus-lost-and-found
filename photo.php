<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/reports-bootstrap.php';
try {
    $report = (new ReportService())->findById(reportId($_GET['report_id'] ?? null));
    if ($report['photo_path'] === null) {
        throw new DomainException('Photo not found.', 404);
    }
    $path = (new PhotoStorage())->path($report['photo_path']);
    $image = @getimagesize($path);
    $mime = $image['mime'] ?? '';
    if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
        throw new DomainException('Photo not found.', 404);
    }
    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: default-src 'none'; sandbox");
    header('Content-Disposition: inline; filename="report.' . ($mime === 'image/png' ? 'png' : 'jpg') . '"');
    readfile($path);
} catch (Throwable $exception) {
    $message = reportError($exception);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
}
