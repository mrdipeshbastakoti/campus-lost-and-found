<?php
declare(strict_types=1);
require_once __DIR__ . '/src/ReportSearch.php';

function searchEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$keyword = '';
$type = '';
$status = '';
$error = '';
$reports = [];
try {
    foreach (['q', 'type', 'status'] as $key) {
        if (isset($_GET[$key]) && !is_string($_GET[$key])) {
            throw new InvalidArgumentException('Use a valid search value.');
        }
    }
    $keyword = trim($_GET['q'] ?? '');
    $type = $_GET['type'] ?? '';
    $status = $_GET['status'] ?? '';
    $reports = ReportSearch::search($keyword, $type, $status);
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    $error = $exception->getMessage();
} catch (Throwable $exception) {
    error_log('Report search: ' . $exception->getMessage());
    http_response_code(503);
    $error = 'Reports are unavailable right now. Check that MySQL is running and try again.';
}

$pageTitle = 'Search items | Campus Lost & Found';
$activePage = 'search';
require __DIR__ . '/includes/header.php';
?>
<section class="intro">
    <h1>Search items</h1>
    <p>Search lost and found reports on campus.</p>
</section>
<form action="search.php" method="get">
    <label class="form-field">Item name
        <input type="search" name="q" maxlength="150" value="<?= searchEscape($keyword) ?>" placeholder="For example, wallet">
    </label>
    <label class="form-field">Report type
        <select name="type">
            <?php foreach (['' => 'All types', 'lost' => 'Lost', 'found' => 'Found'] as $value => $label): ?>
                <option value="<?= $value ?>" <?= $type === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="form-field">Status
        <select name="status">
            <?php foreach (['' => 'All statuses', 'open' => 'Open', 'reserved' => 'Reserved', 'returned' => 'Returned', 'resolved' => 'Resolved'] as $value => $label): ?>
                <option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="button" type="submit">Search</button>
    <a href="search.php">Clear filters</a>
</form>
<section class="reminder" aria-labelledby="results-heading">
    <h2 id="results-heading">Results</h2>
    <?php if ($error !== ''): ?>
        <p role="alert"><?= searchEscape($error) ?></p>
    <?php elseif ($reports === []): ?>
        <p>No matching reports. Try another search or clear the filters.</p>
        <p>If no reports have been posted yet, this list will be empty.</p>
    <?php else: ?>
        <p><?= count($reports) ?> report(s) found.</p>
        <ul class="report-list">
            <?php foreach ($reports as $report): ?>
                <li>
                    <?php if ($report['photo_path'] !== null): ?>
                        <img class="report-thumbnail" src="photo.php?report_id=<?= (int) $report['report_id'] ?>" alt="Photo of <?= searchEscape($report['item_name']) ?>" loading="lazy">
                    <?php else: ?>
                        <span class="photo-placeholder">No photo</span>
                    <?php endif; ?>
                    <h2><a href="item.php?report_id=<?= (int) $report['report_id'] ?>"><?= searchEscape($report['item_name']) ?></a></h2>
                    <p><?= searchEscape(ucfirst($report['report_type'])) ?> · <?= searchEscape(ucfirst($report['status'])) ?></p>
                    <p><?= searchEscape($report['location']) ?> · <?= searchEscape($report['event_date']) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
