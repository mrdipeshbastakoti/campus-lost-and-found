<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/reports-bootstrap.php';
$actorId = AuthService::requireLogin();
$error = '';
$values = ['item_name' => '', 'description' => '', 'location' => '', 'event_date' => '', 'report_type' => 'lost'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $unused) {
        $values[$key] = postString($key);
    }
    try {
        if ($_POST === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > PhotoStorage::MAX_BYTES) {
            throw new DomainException('The submission is too large. Choose a photo of 5 MB or smaller.', 413);
        }
        if (!Session::validCsrf(postString('csrf_token'))) {
            throw new DomainException('The form expired. Please try again.', 403);
        }
        $photo = $_FILES['photo'] ?? null;
        if ($photo !== null && !is_array($photo)) {
            throw new InvalidArgumentException('Choose one JPG or PNG photo.');
        }
        $id = (new ReportService())->create($actorId, $values, $photo);
        header('Location: item.php?report_id=' . $id, true, 303);
        exit;
    } catch (Throwable $exception) {
        $error = reportError($exception);
    }
}
$pageTitle = 'Report an item | Campus Lost & Found';
$activePage = 'report';
require __DIR__ . '/includes/header.php';
?>
<section class="report-form">
    <h1>Report an item</h1>
    <p>Keep one identifying detail out of the description and photo so the owner can use it as proof.</p>
    <?php if ($error !== ''): ?><p class="form-error" role="alert"><?= escape($error) ?></p><?php endif; ?>
    <form action="report.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= escape(Session::csrfToken()) ?>">
        <label class="form-field">Report type
            <select name="report_type" required>
                <option value="lost" <?= $values['report_type'] === 'lost' ? 'selected' : '' ?>>Lost</option>
                <option value="found" <?= $values['report_type'] === 'found' ? 'selected' : '' ?>>Found</option>
            </select>
        </label>
        <label class="form-field">Item name
            <input name="item_name" maxlength="150" value="<?= escape($values['item_name']) ?>" required>
        </label>
        <label class="form-field">Description
            <textarea name="description" rows="5" maxlength="5000" required><?= escape($values['description']) ?></textarea>
        </label>
        <label class="form-field">Location
            <input name="location" maxlength="200" value="<?= escape($values['location']) ?>" required>
        </label>
        <label class="form-field">Date lost or found
            <input type="date" name="event_date" value="<?= escape($values['event_date']) ?>" required>
        </label>
        <label class="form-field">Photo (optional)
            <input type="file" id="report-photo" name="photo" accept="image/jpeg,image/png" aria-describedby="photo-help">
        </label>
        <p class="form-help" id="photo-help">One JPG or PNG, up to 5 MB. After an error, choose your photo again.</p>
        <img id="photo-preview" class="report-photo" alt="Selected photo preview" hidden>
        <button class="button" type="submit">Post report</button>
        <a class="secondary-link" href="search.php">Cancel</a>
    </form>
</section>
<script src="assets/js/report-photo.js" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
