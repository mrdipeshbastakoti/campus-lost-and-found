<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/claims-bootstrap.php';

$error = '';
$reports = [];
try {
    $reports = (new ClaimService())->listOwnFoundReports($claimActorId);
} catch (Throwable $exception) {
    $error = claimPageError($exception);
}
$pageTitle = 'Manage found items | Campus Lost & Found';
$activePage = 'manage-found';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/claims-nav.php';
?>
<h1>Manage my found items</h1>
<p>Review ownership claims and record completed returns.</p>
<?php if ($error !== ''): ?>
    <p class="notice" role="alert"><?= escape($error) ?></p>
<?php elseif ($reports === []): ?>
    <p>You have not posted any found items yet.</p>
<?php else: ?>
    <?php foreach ($reports as $report): ?>
        <section class="reminder">
            <h2><?= escape($report['item_name']) ?></h2>
            <p><?= escape($report['location']) ?> · <?= escape(ucfirst($report['status'])) ?></p>
            <p><?= (int) $report['pending_count'] ?> pending claim(s)</p>
            <a href="review-claims.php?report_id=<?= (int) $report['report_id'] ?>">Review claims and return</a>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
