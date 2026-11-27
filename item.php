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
        <?php if ($report['report_type'] === 'found'): ?>
            <?php if ((int) $report['user_id'] === AuthService::currentUserId()): ?>
                <p><a href="review-claims.php?report_id=<?= (int) $report['report_id'] ?>">Review claims and record return</a></p>
            <?php elseif ($report['status'] === 'open'): ?>
                <?php if (AuthService::currentUserId() === null): ?>
                    <p><a href="login.php">Log in</a> to submit a private ownership claim for this item.</p>
                <?php else: ?>
                    <p><a class="button" href="claim.php?report_id=<?= (int) $report['report_id'] ?>">Claim this item</a></p>
                <?php endif; ?>
            <?php else: ?>
                <p>This found report is no longer accepting claims.</p>
            <?php endif; ?>
        <?php endif; ?>
    </article>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
