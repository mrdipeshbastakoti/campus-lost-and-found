<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/reports-bootstrap.php';
$actorId = AuthService::requireLogin();
$reports = [];
$error = '';
try {
    $service = new ReportService();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Session::validCsrf(postString('csrf_token'))) {
            throw new DomainException('The form expired. Please try again.', 403);
        }
        if (postString('action') !== 'resolve' || postString('confirmed') !== '1') {
            throw new InvalidArgumentException('Confirm that you recovered the item before marking it resolved.');
        }
        $service->markResolved(reportId($_POST['report_id'] ?? null), $actorId);
        header('Location: my-reports.php?resolved=1', true, 303);
        exit;
    }
    $reports = $service->listByUser($actorId);
} catch (Throwable $exception) {
    $error = reportError($exception);
}
$pageTitle = 'My Reports | Campus Lost & Found';
$activePage = 'my-reports';
require __DIR__ . '/includes/header.php';
?>
<section class="intro">
    <h1>My Reports</h1>
    <p><a href="report.php">Report a lost or found item</a></p>
</section>
<?php if ($error !== ''): ?>
    <p class="form-error" role="alert"><?= escape($error) ?></p>
    <p><a href="my-reports.php">Reload My Reports</a></p>
<?php elseif ($reports === []): ?>
    <p>You have not posted any reports yet.</p>
<?php else: ?>
    <?php if (($_GET['resolved'] ?? '') === '1'): ?><p class="form-success" role="status">Your lost report is marked resolved.</p><?php endif; ?>
    <ul class="report-list">
        <?php foreach ($reports as $report): ?>
            <li>
                <h2><a href="item.php?report_id=<?= (int) $report['report_id'] ?>"><?= escape($report['item_name']) ?></a></h2>
                <p><?= escape(ucfirst($report['report_type'])) ?> · <?= escape(ucfirst($report['status'])) ?> · <?= escape($report['location']) ?> · <?= escape($report['event_date']) ?></p>
                <?php if ($report['report_type'] === 'lost' && $report['status'] === 'open'): ?>
                    <form action="my-reports.php" method="post" class="resolve-form">
                        <input type="hidden" name="csrf_token" value="<?= escape(Session::csrfToken()) ?>">
                        <input type="hidden" name="report_id" value="<?= (int) $report['report_id'] ?>">
                        <input type="hidden" name="action" value="resolve">
                        <label><input type="checkbox" name="confirmed" value="1" required> I have recovered this item.</label>
                        <button class="button" type="submit">Mark resolved</button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
