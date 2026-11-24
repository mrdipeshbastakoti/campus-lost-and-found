<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PhotoStorage.php';

final class ReportService
{
    private PDO $database;
    private ?PhotoStorage $photos;

    public function __construct(?PDO $database = null, ?PhotoStorage $photos = null)
    {
        $this->database = $database ?? Database::connect();
        $this->photos = $photos;
    }

    public function create(int $userId, array $data, ?array $photo = null): int
    {
        $this->checkActor($userId);
        $name = $this->text($data, 'item_name', 'Item name', 150);
        $description = $this->text($data, 'description', 'Description', 5000);
        $location = $this->text($data, 'location', 'Location', 200);
        $date = $data['event_date'] ?? '';
        if (!is_string($date) || !preg_match('/\A[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}\z/', $date)) {
            throw new InvalidArgumentException('Enter a valid date.');
        }
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Enter a valid date.');
        }
        $type = $data['report_type'] ?? '';
        if (!in_array($type, ['lost', 'found'], true)) {
            throw new InvalidArgumentException('Choose lost or found.');
        }
        $storage = $this->photos ?? new PhotoStorage();
        $path = $storage->save($photo);
        try {
            $statement = $this->database->prepare(
                'INSERT INTO item_reports (user_id, item_name, description, location, event_date, report_type, photo_path)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $statement->execute([$userId, $name, $description, $location, $date, $type, $path]);
            return (int) $this->database->lastInsertId();
        } catch (Throwable $exception) {
            if ($path !== null) {
                try {
                    $storage->delete($path);
                } catch (Throwable $cleanupError) {
                    error_log('Report photo cleanup: ' . $cleanupError->getMessage());
                }
            }
            throw $exception;
        }
    }

    public function findById(int $reportId): array
    {
        if ($reportId < 1) {
            throw new DomainException('Report not found.', 404);
        }
        $statement = $this->database->prepare(
            'SELECT report_id, user_id, item_name, description, location, event_date,
                    report_type, status, photo_path, created_at
             FROM item_reports WHERE report_id = ?'
        );
        $statement->execute([$reportId]);
        $report = $statement->fetch();
        if ($report === false) {
            throw new DomainException('Report not found.', 404);
        }
        return $report;
    }

    public function listByUser(int $userId): array
    {
        $this->checkActor($userId);
        $statement = $this->database->prepare(
            'SELECT report_id, item_name, location, event_date, report_type, status, photo_path, created_at
             FROM item_reports WHERE user_id = ? ORDER BY created_at DESC, report_id DESC'
        );
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }

    public function markResolved(int $reportId, int $actorId): void
    {
        $this->checkActor($actorId);
        $statement = $this->database->prepare(
            "UPDATE item_reports SET status = 'resolved'
             WHERE report_id = ? AND user_id = ? AND report_type = 'lost' AND status = 'open'"
        );
        $statement->execute([$reportId, $actorId]);
        if ($statement->rowCount() === 1) {
            return;
        }
        $report = $this->findById($reportId);
        if ((int) $report['user_id'] !== $actorId) {
            throw new DomainException('You can only resolve your own lost report.', 403);
        }
        throw new DomainException('Only an open lost report can be marked resolved.', 409);
    }

    private function text(array $data, string $key, string $label, int $maximum): string
    {
        $value = $data[$key] ?? '';
        if (!is_string($value)) {
            throw new InvalidArgumentException($label . ' must be text.');
        }
        $value = trim($value);
        if (!preg_match('/\A.{1,' . $maximum . '}\z/us', $value) || strpos($value, "\0") !== false) {
            throw new InvalidArgumentException($label . ' is required and must be ' . $maximum . ' characters or fewer.');
        }
        return $value;
    }

    private function checkActor(int $userId): void
    {
        if ($userId < 1) {
            throw new DomainException('Please log in first.', 403);
        }
    }
}
