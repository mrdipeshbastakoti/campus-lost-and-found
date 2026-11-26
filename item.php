<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/reports-bootstrap.php';
$report = null;
$error = '';
try {
    $id = reportId($_GET['report_id'] ?? null);
    $report = (new ReportService())->findById($id);
} catch (Throwable $exception) {
    $error = reportError($exception);
}
$pageTitle = 'Item details | Campus Lost & Found';
$activePage = 'search';
require __DIR__ . '/includes/header.php';
?>
<p><a href="search.php">Back to search</a></p>
<?php if ($report === null): ?>
    <h1>Item details</h1>
    <p class="form-error" role="alert"><?= escape($error) ?></p>
<?php else: ?>
    <article>
        <h1><?= escape($report['item_name']) ?></h1>
        <p><?= escape(ucfirst($report['report_type'])) ?> · <?= escape(ucfirst($report['status'])) ?></p>
        <?php if ($report['photo_path'] !== null): ?>
            <img class="report-photo" src="photo.php?report_id=<?= (int) $report['report_id'] ?>" alt="Photo of <?= escape($report['item_name']) ?>">
        <?php else: ?>
            <p class="photo-placeholder">No photo attached</p>
        <?php endif; ?>
        <dl class="report-details">
            <dt>Location</dt><dd><?= escape($report['location']) ?></dd>
            <dt>Date lost or found</dt><dd><?= escape($report['event_date']) ?></dd>
            <dt>Description</dt><dd><?= nl2br(escape($report['description'])) ?></dd>
        </dl>
        <?php if ((int) $report['user_id'] === AuthService::currentUserId()): ?>
            <p>This is your report. <a href="my-reports.php">View My Reports</a>.</p>
        <?php endif; ?>
    </article>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
