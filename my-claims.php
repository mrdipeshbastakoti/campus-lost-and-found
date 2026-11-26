<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/claims-bootstrap.php';

$error = '';
$claims = [];
try {
    $claims = (new ClaimService())->listByClaimant($claimActorId);
} catch (Throwable $exception) {
    $error = claimPageError($exception);
}
$pageTitle = 'My claims | Campus Lost & Found';
$activePage = 'claims';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/claims-nav.php';
?>
<h1>My claims</h1>
<?php if (($_GET['submitted'] ?? '') === '1'): ?>
    <p class="notice" role="status">Your claim was submitted. The finder will review it.</p>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <p class="notice" role="alert"><?= escape($error) ?></p>
<?php elseif ($claims === []): ?>
    <p>You have not submitted any claims yet. Submit one from a found-item report.</p>
<?php else: ?>
    <?php foreach ($claims as $claim): ?>
        <section class="reminder">
            <h2><?= escape($claim['item_name']) ?></h2>
            <p>Claim: <strong><?= escape(ucfirst($claim['status'])) ?></strong> · Item: <?= escape(ucfirst($claim['report_status'])) ?></p>
            <p>Your evidence: <?= nl2br(escape($claim['identifying_detail'])) ?></p>
            <?php if ($claim['status'] === 'approved'): ?>
                <p><?= $claim['report_status'] === 'returned' ? 'The finder has recorded the handover.' : 'Your claim was approved. Arrange collection with the finder at a public place on campus.' ?></p>
            <?php elseif ($claim['status'] === 'closed'): ?>
                <p>The finder approved another claim for this item.</p>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
