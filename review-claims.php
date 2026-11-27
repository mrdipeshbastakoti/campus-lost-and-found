<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/claims-bootstrap.php';

$error = '';
$report = null;
$claims = [];
try {
    $reportId = claimPositiveId($_GET['report_id'] ?? null);
    $service = new ClaimService();
    $report = $service->finderReport($reportId, $claimActorId);
    $claims = $service->listForReport($reportId, $claimActorId);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Session::validCsrf(postString('csrf_token'))) {
            throw new DomainException('The form expired. Please refresh and try again.', 403);
        }
        $action = postString('action');
        if ($action === 'return') {
            if (postString('confirmed') !== '1') {
                throw new InvalidArgumentException('Confirm that the item has been handed over.');
            }
            $service->markReturned($reportId, $claimActorId);
        } elseif (in_array($action, ['approve', 'reject'], true)) {
            $claimId = claimPositiveId(postString('claim_id'));
            if (!in_array($claimId, array_map('intval', array_column($claims, 'claim_id')), true)) {
                throw new InvalidArgumentException('Choose a claim from this report.');
            }
            if ($action === 'approve') {
                if (postString('confirmed') !== '1') {
                    throw new InvalidArgumentException('Confirm that you have checked the ownership evidence.');
                }
                $service->approve($claimId, $claimActorId);
            } else {
                $service->reject($claimId, $claimActorId);
            }
        } else {
            throw new InvalidArgumentException('Choose a valid action.');
        }
        header('Location: review-claims.php?report_id=' . $reportId . '&saved=1', true, 303);
        exit;
    }
} catch (Throwable $exception) {
    $error = claimPageError($exception);
}

$pageTitle = 'Review claims | Campus Lost & Found';
$activePage = 'manage-found';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/claims-nav.php';
?>
<h1>Review claims</h1>
<?php if ($error !== ''): ?>
    <p class="notice" role="alert"><?= escape($error) ?></p>
<?php elseif (($_GET['saved'] ?? '') === '1'): ?>
    <p class="notice" role="status">The report was updated.</p>
<?php endif; ?>
<?php if ($report !== null): ?>
    <h2><?= escape($report['item_name']) ?></h2>
    <p>Item status: <strong><?= escape(ucfirst($report['status'])) ?></strong></p>
    <?php if ($report['status'] === 'reserved'): ?>
        <form action="review-claims.php?report_id=<?= (int) $report['report_id'] ?>" method="post">
            <input type="hidden" name="csrf_token" value="<?= escape(Session::csrfToken()) ?>">
            <input type="hidden" name="action" value="return">
            <p><label><input type="checkbox" name="confirmed" value="1" required> The approved claimant has collected the item.</label></p>
            <button class="button" type="submit">Record return</button>
        </form>
    <?php elseif ($report['status'] === 'returned'): ?>
        <p>The handover has been recorded. This report is closed to new claims.</p>
    <?php endif; ?>
    <?php if ($claims === []): ?>
        <p>No claims have been submitted for this item.</p>
    <?php endif; ?>
    <?php foreach ($claims as $claim): ?>
        <section class="reminder">
            <h2><?= escape($claim['claimant_name']) ?></h2>
            <p>Claim: <?= escape(ucfirst($claim['status'])) ?></p>
            <p><?= nl2br(escape($claim['identifying_detail'])) ?></p>
            <?php if ($report['status'] === 'open' && $claim['status'] === 'pending'): ?>
                <form action="review-claims.php?report_id=<?= (int) $report['report_id'] ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= escape(Session::csrfToken()) ?>">
                    <input type="hidden" name="claim_id" value="<?= (int) $claim['claim_id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <p><label><input type="checkbox" name="confirmed" value="1" required> I checked the evidence and believe this person owns the item.</label></p>
                    <p>Approval is final in this version and closes other pending claims.</p>
                    <button class="button" type="submit">Approve and reserve</button>
                </form>
                <form action="review-claims.php?report_id=<?= (int) $report['report_id'] ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= escape(Session::csrfToken()) ?>">
                    <input type="hidden" name="claim_id" value="<?= (int) $claim['claim_id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <p><button class="button" type="submit">Reject claim</button></p>
                </form>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
