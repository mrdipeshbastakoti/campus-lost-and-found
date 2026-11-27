# Claims and returns

Owner: Shiva. Branch: `feature/claims-and-returns`.

This increment implements FR-05 through FR-08 using the existing `users`, `item_reports`, and `claims` tables. Lost-report recovery (FR-09) remains in the reports module.

## Prerequisites

Start from the latest main after the complete accounts, search, and item-report features have been merged. No schema migration or SQL re-import is needed. Keep your existing local configuration and photo storage.

## Pages and integration

| File | Purpose |
|---|---|
| `claim.php?report_id=ID` | Submit private ownership evidence for an open found report |
| `my-claims.php` | View only the signed-in student's claims |
| `manage-found.php` | List only the finder's own found reports and pending counts |
| `review-claims.php?report_id=ID` | Finder-only evidence review, decisions, and return confirmation |
| `item.php` | Links eligible students to the claim form and the finder to review |
| `my-reports.php` | Adds claim-review links to the author's found reports; preserves lost recovery |
| `includes/header.php` | Preserves existing links/styles and adds the two claims areas |

The new service is `src/ClaimService.php`. `includes/claims-bootstrap.php` requires login and handles IDs/errors. `includes/claims-nav.php` connects the claimant and finder pages. All pages reuse the shared layout and session helpers.

## Rules

- Only signed-in students can submit or manage claims. Acting user IDs come from the server session.
- Evidence must contain 10–2,000 Unicode characters after trimming. It is visible only in that claimant's own list and the finder's protected review page.
- Claims apply only to open found reports posted by another student. Lost, reserved, and returned reports reject new claims.
- One student may have only one pending claim per report. After rejection, a new claim may be submitted; previous decisions remain recorded.
- Only the finder can approve or reject pending claims. Approval requires confirmation that they reviewed the evidence.
- Approval marks the selected claim approved, closes other pending claims, and reserves the report in one transaction. Previously rejected claims remain rejected.
- Only the finder can mark a reserved item returned after confirming handover. Exactly one approved claim must exist. That claim remains approved as history, while the report becomes returned.
- Approval is final in this version. Reopening reservations and disputes remain future work.
- State changes require POST and a valid CSRF token. GET requests display information only.
- Database values use prepared statements. Displayed user text is escaped. Claims pages do not expose account emails or password hashes.

## Service use

```php
$service = new ClaimService();
$claimId = $service->submit($reportId, $actorId, $evidence);
$myClaims = $service->listByClaimant($actorId);
$claims = $service->listForReport($reportId, $actorId);
$service->approve($claimId, $actorId);
$service->reject($claimId, $actorId);
$service->markReturned($reportId, $actorId);
```

The service also provides `findFoundReport()`, `finderReport()`, and `listOwnFoundReports()`. Tests can inject a PDO connection through the constructor. Mutating methods own their transaction; do not start an outer transaction around them.

Every mutation locks the report row first. Decisions look up the report ID before starting the transaction, then lock the report and claim and recheck their states. Submission uses the same report lock. This coordinates duplicate submissions, competing approvals, and submissions arriving during approval. A failed transaction rolls back its changes.

## Local acceptance checks

Use test accounts that you control. Record expected and actual results after running each check on XAMPP.

1. As a finder, post a found item through Report an item. Check that its details and My Reports contain a review link.
2. As another student, open its details and submit private identifying evidence. My Claims shows pending.
3. Submit again from the same account while pending. It must be rejected.
4. Use a second claimant account and submit different evidence for the same item.
5. The finder can see both claims. Each claimant sees only their own evidence. A separate account cannot open the review URL or change decisions.
6. Reject one claim. The report remains open. That student may submit new evidence as a new claim.
7. Approve one pending claim after checking its evidence. The report becomes reserved and other pending claims close. It must still be reserved, not returned, before handover.
8. Try to submit another claim or approve a second one. Both must be rejected.
9. Confirm handover as the finder. The report becomes returned; the claimant sees the recorded handover.
10. Confirm public search/details show the updated status without revealing ownership evidence or account emails.
11. Confirm signed-out claim URLs redirect to login, missing/invalid CSRF requests fail, and GET query parameters cannot approve or return an item.
12. Check lost reporting, photo display, searching, logout, and lost-report resolution still work.

Review the final service methods and supporting files when updating the UML/design documentation. Automated Linux test results for the prepared package are recorded in its verification report. Windows XAMPP and browser appearance still need the local checks above.
