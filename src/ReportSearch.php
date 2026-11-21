<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class ReportSearch
{
    public static function search(string $keyword = '', string $type = '', string $status = ''): array
    {
        $keyword = trim($keyword);
        if (!preg_match('/\A.{0,150}\z/us', $keyword)) {
            throw new InvalidArgumentException('Search must be 150 characters or fewer.');
        }
        if (!in_array($type, ['', 'lost', 'found'], true)) {
            throw new InvalidArgumentException('Choose lost, found, or all item types.');
        }
        if (!in_array($status, ['', 'open', 'reserved', 'returned', 'resolved'], true)) {
            throw new InvalidArgumentException('Choose a valid report status.');
        }

        $conditions = [];
        $values = [];
        if ($keyword !== '') {
            $conditions[] = 'LOCATE(?, item_name) > 0';
            $values[] = $keyword;
        }
        if ($type !== '') {
            $conditions[] = 'report_type = ?';
            $values[] = $type;
        }
        if ($status !== '') {
            $conditions[] = 'status = ?';
            $values[] = $status;
        }

        $sql = 'SELECT report_id, item_name, location, event_date, report_type, status, photo_path, created_at
                FROM item_reports';
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY created_at DESC, report_id DESC';

        $statement = Database::connect()->prepare($sql);
        $statement->execute($values);
        return $statement->fetchAll();
    }
}
