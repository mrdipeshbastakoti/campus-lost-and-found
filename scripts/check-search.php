<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../src/ReportSearch.php';

$pdo = null;
$passed = 0;
function checkSearch(bool $condition, string $label): void
{
    global $passed;
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $passed++;
    echo 'PASS: ' . $label . PHP_EOL;
}

try {
    $pdo = Database::connect();
    $pdo->beginTransaction();
    $prefix = 'SearchCheck-' . bin2hex(random_bytes(8));
    $user = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    $user->execute(['Search check', $prefix . '@example.test', password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
    $userId = (int) $pdo->lastInsertId();

    $insert = $pdo->prepare('INSERT INTO item_reports
        (user_id, item_name, description, location, event_date, report_type, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $ids = [];
    foreach ([
        [' wallet', 'lost', 'open', '2026-01-01 10:00:00'],
        [' wallet', 'found', 'open', '2026-01-02 10:00:00'],
        [' keys_100%', 'found', 'reserved', '2026-01-02 10:00:00'],
        [' notebook', 'lost', 'resolved', '2026-01-01 10:00:00'],
        [' bottle', 'found', 'returned', '2026-01-01 10:00:00'],
    ] as [$suffix, $type, $status, $created]) {
        $insert->execute([$userId, $prefix . $suffix, 'Temporary search check', 'Library', '2026-01-01', $type, $status, $created]);
        $ids[] = (int) $pdo->lastInsertId();
    }

    $all = ReportSearch::search();
    checkSearch(array_diff($ids, array_map('intval', array_column($all, 'report_id'))) === [], 'Empty search includes the test reports');
    $filtered = ReportSearch::search('', 'found', 'reserved');
    checkSearch(in_array($ids[2], array_map('intval', array_column($filtered, 'report_id')), true)
        && count(array_filter($filtered, fn(array $row): bool => $row['report_type'] !== 'found' || $row['status'] !== 'reserved')) === 0, 'Filters work without a keyword');

    $rows = ReportSearch::search($prefix);
    checkSearch(count($rows) === 5, 'Finds all matching reports');
    checkSearch(array_map('intval', array_column($rows, 'report_id')) === [$ids[2], $ids[1], $ids[4], $ids[3], $ids[0]], 'Newest first with a consistent tie order');
    checkSearch(count(ReportSearch::search($prefix . ' wallet')) === 2, 'Searches within item names');
    checkSearch(count(ReportSearch::search(' ' . strtolower($prefix) . ' ')) === 5, 'Trims spaces and matches case-insensitively');
    checkSearch(count(ReportSearch::search($prefix, 'lost')) === 2, 'Filters lost reports');
    checkSearch(count(ReportSearch::search($prefix, 'found')) === 3, 'Filters found reports');
    checkSearch(count(ReportSearch::search($prefix, '', 'open')) === 2, 'Filters open reports');
    checkSearch(count(ReportSearch::search($prefix, 'found', 'reserved')) === 1, 'Combines name, type, and status');
    checkSearch(count(ReportSearch::search($prefix, '', 'returned')) === 1, 'Filters returned reports');
    checkSearch(count(ReportSearch::search($prefix, '', 'resolved')) === 1, 'Filters resolved reports');
    checkSearch(ReportSearch::search($prefix, 'lost', 'returned') === [], 'Returns an empty array for no matches');
    checkSearch(count(ReportSearch::search($prefix . ' keys_100%')) === 1, 'Treats underscore and percent as literal text');
    checkSearch(ReportSearch::search($prefix . "' OR 1=1 --") === [], 'SQL-like text cannot broaden the search');
    checkSearch(array_keys($rows[0]) === ['report_id', 'item_name', 'location', 'event_date', 'report_type', 'status', 'photo_path', 'created_at'], 'Returns only public report-list fields');
    foreach ([['', 'invalid', ''], ['', '', 'invalid'], [str_repeat('x', 151), '', '']] as $input) {
        $rejected = false;
        try {
            ReportSearch::search(...$input);
        } catch (InvalidArgumentException $error) {
            $rejected = true;
        }
        checkSearch($rejected, 'Rejects invalid search options');
    }

    $pdo->rollBack();
    $verify = $pdo->prepare('SELECT COUNT(*) FROM item_reports WHERE user_id = ?');
    $verify->execute([$userId]);
    checkSearch((int) $verify->fetchColumn() === 0, 'Temporary reports removed by rollback');
    $verify = $pdo->prepare('SELECT COUNT(*) FROM users WHERE user_id = ?');
    $verify->execute([$userId]);
    checkSearch((int) $verify->fetchColumn() === 0, 'Temporary account removed by rollback');
    echo $passed . ' search checks passed.' . PHP_EOL;
} catch (Throwable $error) {
    if ($pdo !== null && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
