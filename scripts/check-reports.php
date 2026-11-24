<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

foreach (['ReportService.php', 'PhotoStorage.php', 'ReportSearch.php'] as $file) {
    if (!is_file(__DIR__ . '/../src/' . $file)) {
        fwrite(STDERR, 'FAIL: Missing src/' . $file . '. Include the item-reports feature before running these checks.' . PHP_EOL);
        exit(1);
    }
}
require_once __DIR__ . '/../src/ReportService.php';
require_once __DIR__ . '/../src/ReportSearch.php';

$pdo = null;
$started = false;
$userIds = [];
$passed = 0;
$failure = null;

$check = function (bool $condition, string $label) use (&$passed): void {
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $passed++;
    echo 'PASS: ' . $label . PHP_EOL;
};
$rejects = function (callable $operation, string $class, int $code, string $label) use ($check): void {
    try {
        $operation();
    } catch (Throwable $error) {
        $check($error instanceof $class && $error->getCode() === $code, $label);
        return;
    }
    throw new RuntimeException($label);
};
$ids = function (array $rows): array {
    $values = array_map('intval', array_column($rows, 'report_id'));
    sort($values);
    return $values;
};

try {
    $pdo = Database::connect();
    $engines = $pdo->query("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('users', 'item_reports')")->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach (['users', 'item_reports'] as $table) {
        if (strtolower($engines[$table] ?? '') !== 'innodb') {
            throw new RuntimeException('The ' . $table . ' table must use InnoDB so temporary rows can be rolled back.');
        }
    }
    if ($pdo->inTransaction()) {
        throw new RuntimeException('Run this script in its own PHP process.');
    }

    $pdo->beginTransaction();
    $started = true;
    $prefix = 'ReportCheck-' . bin2hex(random_bytes(8));
    $insertUser = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    foreach (['owner', 'other'] as $name) {
        $insertUser->execute(['Report check ' . $name, $prefix . '-' . $name . '@example.test',
            password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
        $userIds[] = (int) $pdo->lastInsertId();
    }
    [$owner, $other] = $userIds;
    $service = new ReportService($pdo);
    $data = ['item_name' => $prefix . ' wallet', 'description' => 'Temporary public report description',
        'location' => 'Library', 'event_date' => '2026-01-01', 'report_type' => 'lost'];
    $lost = $service->create($owner, $data);
    $found = $service->create($owner, array_replace($data, ['report_type' => 'found']));
    $otherLost = $service->create($other, array_replace($data, ['item_name' => $prefix . ' notebook']));
    $reportIds = [$lost, $found, $otherLost];
    sort($reportIds);

    $report = $service->findById($lost);
    $check($report['report_type'] === 'lost' && $report['status'] === 'open' && $report['photo_path'] === null,
        'Creates an open lost report without a photo');
    $report = $service->findById($found);
    $check($report['report_type'] === 'found' && $report['status'] === 'open', 'Creates an open found report');
    $rows = ReportSearch::search($prefix);
    $check($ids($rows) === $reportIds, 'Reports created through the service appear in search');
    $check($ids(ReportSearch::search($prefix . ' wallet')) === [$lost, $found], 'Item-name search includes both matching report types');
    $check($ids(ReportSearch::search($prefix, 'lost')) === [$lost, $otherLost], 'Lost filter includes both authors without found reports');
    $check($ids(ReportSearch::search($prefix, 'found')) === [$found], 'Found filter excludes lost reports');
    $check($ids(ReportSearch::search($prefix, '', 'open')) === $reportIds, 'Open filter finds the new reports');
    $check($ids(ReportSearch::search($prefix . ' wallet', 'lost', 'open')) === [$lost], 'Name, type, and status filters work together');
    $check(ReportSearch::search($prefix . ' missing') === [], 'Unknown item name returns no matches');
    $check(array_keys($rows[0]) === ['report_id', 'item_name', 'location', 'event_date', 'report_type', 'status', 'photo_path', 'created_at'],
        'Search returns public report fields without private account or claim data');
    $check($ids($service->listByUser($owner)) === [$lost, $found], 'My Reports contains only the first author\'s reports');
    $check($ids($service->listByUser($other)) === [$otherLost], 'The second author sees only their own report');

    foreach ([['item_name' => ''], ['description' => ''], ['event_date' => '2026-02-30'], ['report_type' => 'invalid']] as $index => $change) {
        $rejects(fn() => $service->create($owner, array_replace($data, $change)), InvalidArgumentException::class, 0,
            ['Rejects a missing item name', 'Rejects a missing description', 'Rejects an impossible date', 'Rejects an invalid report type'][$index]);
    }
    $check($ids(ReportSearch::search($prefix)) === $reportIds, 'Rejected reports add no searchable records');
    $rejects(fn() => $service->markResolved($lost, $other), DomainException::class, 403,
        'Another account cannot resolve the author\'s lost report');
    $check($ids(ReportSearch::search($prefix . ' wallet', 'lost', 'open')) === [$lost],
        'Unauthorized resolution leaves the report open in search');
    $rejects(fn() => $service->markResolved($found, $owner), DomainException::class, 409,
        'A found report cannot use the lost-report resolution action');
    $check($ids(ReportSearch::search($prefix, 'found', 'open')) === [$found], 'Rejected resolution leaves the found report open');

    $service->markResolved($lost, $owner);
    $resolved = ReportSearch::search($prefix . ' wallet', 'lost', 'resolved');
    $check($ids($resolved) === [$lost] && $resolved[0]['status'] === 'resolved', 'Author recovery appears in the resolved search filter');
    $check($ids(ReportSearch::search($prefix, '', 'open')) === [$found, $otherLost], 'Resolved report disappears from the open filter');
    $check($ids(ReportSearch::search($prefix, 'lost')) === [$lost, $otherLost], 'Lost reports remain searchable after recovery');
    $check($service->findById($otherLost)['status'] === 'open', 'Recovery leaves the other author\'s report unchanged');
    $rejects(fn() => $service->markResolved($lost, $owner), DomainException::class, 409, 'Repeated recovery action is rejected');
    $check($ids(ReportSearch::search($prefix)) === $reportIds, 'Recovery changes status without adding or removing reports');
} catch (Throwable $error) {
    $failure = $error;
} finally {
    if ($started && $pdo->inTransaction()) {
        try {
            $pdo->rollBack();
        } catch (Throwable $error) {
            $failure = $failure ?? $error;
            fwrite(STDERR, 'FAIL: Could not roll back temporary rows: ' . $error->getMessage() . PHP_EOL);
        }
    }
}

if ($pdo !== null && $userIds !== []) {
    try {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $verify = $pdo->prepare('SELECT COUNT(*) FROM item_reports WHERE user_id IN (' . $placeholders . ')');
        $verify->execute($userIds);
        $check((int) $verify->fetchColumn() === 0, 'Rollback removes the temporary reports');
        $verify = $pdo->prepare('SELECT COUNT(*) FROM users WHERE user_id IN (' . $placeholders . ')');
        $verify->execute($userIds);
        $check((int) $verify->fetchColumn() === 0, 'Rollback removes both temporary accounts');
    } catch (Throwable $error) {
        $failure = $failure ?? $error;
        fwrite(STDERR, 'FAIL: Cleanup verification: ' . $error->getMessage() . PHP_EOL);
    }
}
if ($failure !== null) {
    fwrite(STDERR, 'FAIL: ' . $failure->getMessage() . PHP_EOL);
    exit(1);
}
echo $passed . ' report and search integration checks passed.' . PHP_EOL;
