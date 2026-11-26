# Item reports and photos

This increment adds lost/found reports, optional photos, public item details, My Reports, and recovery of lost items. It uses the existing accounts, database schema, and search service.

## Pages

| Page | Access and purpose |
|---|---|
| `report.php` | Signed-in students can post a lost or found report. |
| `search.php` | Everyone can browse and filter reports, see thumbnails, and open details. |
| `item.php?report_id=ID` | Public item details, description, date, location, and photo. |
| `photo.php?report_id=ID` | Public JPG/PNG image response, looked up by report ID. |
| `my-reports.php` | Signed-in students see only their own reports and can resolve their open lost reports. |

## Validation and permissions

- Item name: required, up to 150 characters.
- Description: required, up to 5,000 characters. This is public text; keep one identifying detail private.
- Location: required, up to 200 characters.
- Event date: a real date in `YYYY-MM-DD` format, with a year from 1000 to 9999.
- Report type: `lost` or `found`. New reports always start `open`.
- The author comes from the authenticated session, not submitted form fields.
- Creating and resolving reports require a valid CSRF token.
- Only the author of an open lost report can mark it `resolved`, after confirming recovery. Found-item reservations and returns belong to the claims increment.
- Public pages display no email addresses, password hashes, or claim evidence. User text is escaped, and database values use prepared statements.

## Photo storage

Each report allows one optional JPG or PNG, no larger than 5 MB (5,242,880 bytes). The server checks the actual uploaded file size, MIME type, and image information. Original filenames and browser-supplied MIME types are not trusted. Files receive random names; the database stores only a value such as `items/0123456789abcdef0123456789abcdef.png`.

Use the existing `storage_path` setting. With the example configuration, the folder is `C:/xampp/campus-lost-found-storage/items`, outside `htdocs`. Keep `config/config.php` and real uploads out of Git. No schema changes or SQL re-import are needed for this increment.

For the full 5 MB allowance in XAMPP, open XAMPP Control Panel, choose Apache > Config > PHP (php.ini), and check:

```ini
file_uploads = On
upload_max_filesize = 5M
post_max_size = 8M
```

Save and restart Apache. The PHP `fileinfo` extension must be enabled. Standard XAMPP installations normally include it; if it is disabled, enable the existing `extension=fileinfo` line and restart Apache.

The image endpoint accepts a report ID, never a caller-supplied filesystem path. It serves only supported images under the configured `items` folder. If database insertion fails after a new photo is saved, the service removes that new photo. Invalid report fields are rejected before saving a photo. A small browser preview is optional; posting still works without JavaScript.

## Service methods

`ReportService` uses `Database::connect()` by default. It also accepts a PDO connection and a `PhotoStorage` instance when needed for testing.

```php
$service = new ReportService();
$id = $service->create($actorId, $data, $_FILES['photo'] ?? null);
$report = $service->findById($id);
$myReports = $service->listByUser($actorId);
$service->markResolved($id, $actorId);
```

`create()` returns a report ID. `findById()` returns a report array or throws a 404 `DomainException`. `ReportSearch::search()` remains the shared search method. The request helpers map validation to 422, unauthorized actions to 403, missing reports/photos to 404, stale status changes to 409, and unexpected storage/database failures to a generic 503.

## Local acceptance checks

Record actual outcomes separately after running these on your own PC:

1. Signed-out access to Report an item and My Reports redirects to login.
2. Post a lost item without a photo. It appears in search and My Reports, with a no-photo placeholder on details.
3. Post a found item with a JPG, then with a PNG. Confirm the preview, search thumbnail, and detail image.
4. Reload the page after posting. The redirect avoids reposting the form, and the record remains after restarting Apache/MySQL.
5. Try missing fields, an invalid date, a fake image renamed `.jpg`, and a file over 5 MB. Each must be rejected without a new report or stored photo.
6. Use a second account. It must not see the first account's reports in My Reports or resolve them through a forged request.
7. Resolve an open lost report as its author. Search shows `resolved`; repeating the action or resolving a found report is rejected.
8. Check item names/descriptions containing HTML-like text. The text must display without executing.
9. Verify login, logout, and search filters still work, including Search items in the navigation.

Do not link to claims pages until that increment exists. The claims integration will add links from eligible found-item details and the author's report area while preserving the search and reporting links.
