<?php
declare(strict_types=1);
require_once __DIR__ . '/Database.php';

final class ClaimService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connect();
    }

    public function findFoundReport(int $reportId): array
    {
        $statement = $this->pdo->prepare('SELECT report_id, user_id, item_name, location, status
            FROM item_reports WHERE report_id = ? AND report_type = ?');
        $statement->execute([$reportId, 'found']);
        $report = $statement->fetch();
        if (!$report) {
            throw new DomainException('Found-item report not found.', 404);
        }
        return $report;
    }

    public function finderReport(int $reportId, int $actorId): array
    {
        $this->checkActor($actorId);
        $report = $this->findFoundReport($reportId);
        $this->checkFinder($report, $actorId);
        return $report;
    }

    public function submit(int $reportId, int $claimantId, string $evidence): int
    {
        $this->checkActor($claimantId);
        $evidence = trim($evidence);
        if (!preg_match('/\A.{10,2000}\z/us', $evidence)) {
            throw new InvalidArgumentException('Write between 10 and 2,000 characters of identifying detail.');
        }

        return $this->transaction(function () use ($reportId, $claimantId, $evidence): int {
            $report = $this->lockReport($reportId);
            $this->checkOpenFound($report);
            if ((int) $report['user_id'] === $claimantId) {
                throw new DomainException('You cannot claim your own report.', 403);
            }
            $statement = $this->pdo->prepare('SELECT claim_id FROM claims
                WHERE report_id = ? AND claimant_id = ? AND status = ? FOR UPDATE');
            $statement->execute([$reportId, $claimantId, 'pending']);
            if ($statement->fetch()) {
                throw new DomainException('You already have a pending claim for this item.', 409);
            }
            $statement = $this->pdo->prepare('INSERT INTO claims (report_id, claimant_id, identifying_detail)
                VALUES (?, ?, ?)');
            $statement->execute([$reportId, $claimantId, $evidence]);
            return (int) $this->pdo->lastInsertId();
        });
    }

    public function listByClaimant(int $actorId): array
    {
        $this->checkActor($actorId);
        $statement = $this->pdo->prepare('SELECT c.claim_id, c.report_id, c.identifying_detail,
            c.status, c.created_at, r.item_name, r.status AS report_status
            FROM claims c JOIN item_reports r ON r.report_id = c.report_id
            WHERE c.claimant_id = ? ORDER BY c.created_at DESC, c.claim_id DESC');
        $statement->execute([$actorId]);
        return $statement->fetchAll();
    }

    public function listForReport(int $reportId, int $actorId): array
    {
        $this->finderReport($reportId, $actorId);
        $statement = $this->pdo->prepare('SELECT c.claim_id, c.identifying_detail, c.status,
            c.created_at, u.name AS claimant_name
            FROM claims c JOIN users u ON u.user_id = c.claimant_id
            WHERE c.report_id = ? ORDER BY c.created_at DESC, c.claim_id DESC');
        $statement->execute([$reportId]);
        return $statement->fetchAll();
    }

    public function listOwnFoundReports(int $actorId): array
    {
        $this->checkActor($actorId);
        $statement = $this->pdo->prepare('SELECT r.report_id, r.item_name, r.location, r.status,
            (SELECT COUNT(*) FROM claims c WHERE c.report_id = r.report_id AND c.status = ?) AS pending_count
            FROM item_reports r WHERE r.user_id = ? AND r.report_type = ?
            ORDER BY r.created_at DESC, r.report_id DESC');
        $statement->execute(['pending', $actorId, 'found']);
        return $statement->fetchAll();
    }

    public function approve(int $claimId, int $actorId): void
    {
        $this->checkActor($actorId);
        $reportId = $this->claimReportId($claimId);
        $this->transaction(function () use ($claimId, $actorId, $reportId): void {
            [$report, $claim] = $this->lockClaimContext($claimId, $reportId);
            $this->checkFinder($report, $actorId);
            $this->checkOpenFound($report);
            $this->checkPending($claim);
            if ((int) $claim['claimant_id'] === $actorId) {
                throw new DomainException('You cannot approve a claim on your own behalf.', 403);
            }
            $statement = $this->pdo->prepare('SELECT claim_id FROM claims
                WHERE report_id = ? AND status = ? FOR UPDATE');
            $statement->execute([$report['report_id'], 'approved']);
            if ($statement->fetch()) {
                throw new DomainException('A claim has already been approved.', 409);
            }
            $statement = $this->pdo->prepare('UPDATE claims SET status = ? WHERE claim_id = ?');
            $statement->execute(['approved', $claimId]);
            $statement = $this->pdo->prepare('UPDATE claims SET status = ?
                WHERE report_id = ? AND status = ? AND claim_id <> ?');
            $statement->execute(['closed', $report['report_id'], 'pending', $claimId]);
            $statement = $this->pdo->prepare('UPDATE item_reports SET status = ? WHERE report_id = ?');
            $statement->execute(['reserved', $report['report_id']]);
        });
    }

    public function reject(int $claimId, int $actorId): void
    {
        $this->checkActor($actorId);
        $reportId = $this->claimReportId($claimId);
        $this->transaction(function () use ($claimId, $actorId, $reportId): void {
            [$report, $claim] = $this->lockClaimContext($claimId, $reportId);
            $this->checkFinder($report, $actorId);
            $this->checkOpenFound($report);
            $this->checkPending($claim);
            $statement = $this->pdo->prepare('UPDATE claims SET status = ? WHERE claim_id = ?');
            $statement->execute(['rejected', $claimId]);
        });
    }

    public function markReturned(int $reportId, int $actorId): void
    {
        $this->checkActor($actorId);
        $this->transaction(function () use ($reportId, $actorId): void {
            $report = $this->lockReport($reportId);
            $this->checkFinder($report, $actorId);
            if ($report['report_type'] !== 'found' || $report['status'] !== 'reserved') {
                throw new DomainException('Only a reserved found item can be marked returned.', 409);
            }
            $statement = $this->pdo->prepare('SELECT claim_id FROM claims
                WHERE report_id = ? AND status = ? FOR UPDATE');
            $statement->execute([$reportId, 'approved']);
            if (count($statement->fetchAll()) !== 1) {
                throw new DomainException('The report must have exactly one approved claim.', 409);
            }
            $statement = $this->pdo->prepare('UPDATE item_reports SET status = ? WHERE report_id = ?');
            $statement->execute(['returned', $reportId]);
        });
    }

    private function lockReport(int $reportId): array
    {
        $statement = $this->pdo->prepare('SELECT report_id, user_id, report_type, status
            FROM item_reports WHERE report_id = ? FOR UPDATE');
        $statement->execute([$reportId]);
        $report = $statement->fetch();
        if (!$report) {
            throw new DomainException('Report not found.', 404);
        }
        return $report;
    }

    private function claimReportId(int $claimId): int
    {
        $statement = $this->pdo->prepare('SELECT report_id FROM claims WHERE claim_id = ?');
        $statement->execute([$claimId]);
        $reportId = $statement->fetchColumn();
        if ($reportId === false) {
            throw new DomainException('Claim not found.', 404);
        }
        return (int) $reportId;
    }

    private function lockClaimContext(int $claimId, int $reportId): array
    {
        $report = $this->lockReport($reportId);
        $statement = $this->pdo->prepare('SELECT claim_id, report_id, claimant_id, status
            FROM claims WHERE claim_id = ? FOR UPDATE');
        $statement->execute([$claimId]);
        $claim = $statement->fetch();
        if (!$claim || (int) $claim['report_id'] !== (int) $report['report_id']) {
            throw new DomainException('Claim not found.', 404);
        }
        return [$report, $claim];
    }

    private function checkActor(int $actorId): void
    {
        if ($actorId < 1) {
            throw new DomainException('Please log in.', 403);
        }
    }

    private function checkFinder(array $report, int $actorId): void
    {
        if ((int) $report['user_id'] !== $actorId) {
            throw new DomainException('Only the finder can manage this report.', 403);
        }
    }

    private function checkOpenFound(array $report): void
    {
        if ($report['report_type'] !== 'found' || $report['status'] !== 'open') {
            throw new DomainException('Only open found items can receive claim decisions.', 409);
        }
    }

    private function checkPending(array $claim): void
    {
        if ($claim['status'] !== 'pending') {
            throw new DomainException('This claim has already been decided.', 409);
        }
    }

    private function transaction(callable $action): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $action();
            $this->pdo->commit();
            return $result;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }
}
