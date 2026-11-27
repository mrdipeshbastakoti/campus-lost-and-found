<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/claims-bootstrap.php';

$error = '';
$report = null;
$evidence = '';
try {
    $reportId = claimPositiveId($_GET['report_id'] ?? null);
    $service = new ClaimService();
    $report = $service->findFoundReport($reportId);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $evidence = postString('evidence');
        if (!Session::validCsrf(postString('csrf_token'))) {
            throw new DomainException('The form expired. Please refresh and try again.', 403);
        }
        $service->submit($reportId, $claimActorId, $evidence);
        header('Location: my-claims.php?submitted=1', true, 303);
        exit;
    }
} catch (Throwable $exception) {
    $error = claimPageError($exception);
}

$pageTitle = 'Claim an item | Campus Lost & Found';
$activePage = 'claim';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/claims-nav.php';
?>
<h1>Claim an item</h1>
<?php if ($error !== ''): ?>
    <p class="notice" role="alert"><?= escape($error) ?></p>
<?php endif; ?>
<?php if ($report !== null): ?>
    <h2><?= escape($report['item_name']) ?></h2>
    <p><?= escape($report['location']) ?> · <?= escape(ucfirst($report['status'])) ?></p>
    <?php if ((int) $report['user_id'] === $claimActorId): ?>
        <p>This is your report. <a href="review-claims.php?report_id=<?= (int) $report['report_id'] ?>">Review its claims</a>.</p>
    <?php elseif ($report['status'] !== 'open'): ?>
        <p>This report is no longer accepting claims.</p>
    <?php else: ?>
        <p>Describe a detail only the owner would know. Only you and the finder can view your evidence.</p>
        <form action="claim.php?report_id=<?= (int) $report['report_id'] ?>" method="post">
            <input type="hidden" name="csrf_token" value="<?= escape(Session::csrfToken()) ?>">
            <label class="form-field">Ownership evidence
                <textarea name="evidence" rows="5" minlength="10" maxlength="2000" required><?= escape($evidence) ?></textarea>
            </label>
            <p>Use 10–2,000 characters. Keep passwords, ID numbers, and other sensitive information out of the claim.</p>
            <button class="button" type="submit">Submit claim</button>
        </form>
    <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
