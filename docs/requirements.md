# Requirements — Campus Lost-and-Found System

Status: Requirements reviewed by the team, as confirmed by Dipesh. MySQL and the photo-upload additions have been approved. Classmate interviews and professor review have not yet been recorded.

## 1. Problem and goal

Campus students need a shared place to report lost and found belongings and arrange their return. The project will provide a searchable report list and a private ownership-claim process.

## 2. Users

- A visitor can browse and search public reports.
- A registered student can post reports and submit claims.
- A finder is the student who posts a found report.
- A claimant is a student who believes a found item belongs to them.

Finder and claimant are roles of the same user account.

## 3. Functional requirements

| ID | Requirement | Acceptance check |
|---|---|---|
| FR-01 | A student shall be able to register, sign in, and sign out. | Correct credentials allow sign-in, incorrect credentials are rejected, and signing out prevents further account actions until sign-in. |
| FR-02 | A signed-in student shall be able to post a lost or found report with item name, description, location, and event date. | A valid report appears in the list and remains after restarting the application. Missing required fields are rejected. |
| FR-03 | A visitor shall be able to browse reports and open report details. | The list distinguishes lost and found reports. An empty list displays a helpful message. |
| FR-04 | A visitor shall be able to search reports by item name. | Searching for an existing item returns matching reports. A search with no matches shows a clear message. |
| FR-05 | A signed-in student shall be able to submit private ownership evidence for an open found report posted by another student. | A pending claim is created. Claims on lost, reserved, or returned reports and claims on the student's own report are rejected. |
| FR-06 | A claimant and the finder shall be able to view their shared claim details. | Each can read the evidence. A third account cannot read it, including through a direct URL. |
| FR-07 | Only the finder shall be able to approve or reject pending claims on their report. | Approval reserves the report for that claimant and closes other pending claims. A second approval is rejected, including if requests arrive together. |
| FR-08 | Only the finder shall be able to mark a reserved found report as returned after handover. | The report becomes returned and rejects further claims. Approval alone does not mark the item returned. |
| FR-09 | The author of an open lost report shall be able to mark it resolved after recovering the item. | The report displays resolved and another student cannot change its status. |

| FR-10 | A student may attach one optional JPG or PNG photo when creating a lost/found report. Maximum size is 5 MB. | A valid image is accepted. Posting without a photo also succeeds. |
| FR-11 | Reports display a thumbnail in the list and a larger image on the details page. | The correct image appears on both pages. Reports without images show a placeholder. |

## 4. Non-functional requirements

| ID | Requirement | Verification |
|---|---|---|
| NFR-01 | Claim evidence and account email addresses shall be excluded from public pages and responses. | Inspect public lists and details while signed out and as an unrelated student. |
| NFR-02 | Passwords shall be stored as password hashes and verified using PHP password functions. | Review registration and login code and inspect a test account's stored value. |
| NFR-03 | User-controlled database values shall use prepared statements and displayed user text shall be escaped for HTML. | Review database calls and submit text containing quotes and HTML markup. It must remain text and preserve records. |
| NFR-04 | State-changing actions shall check authentication, ownership where applicable, and a valid CSRF token on the server. | Missing or invalid tokens and requests from unauthorized accounts leave data unchanged. |
| NFR-05 | At most one approved claim shall exist per found report. | Attempt competing approvals and verify one approved claim, one reserved report, and consistent remaining claim states. |
| NFR-06 | All four members shall be able to run the project using documented setup steps. | Each member records whether setup and the report-and-browse demo pass on their laptop. |

| NFR-07 | The server validates the uploaded image's actual type and size and assigns a unique filename. | An oversized file or a non-image renamed .jpg is rejected. |
| NFR-08 | Failed submissions leave no incomplete report or leftover uploaded file. | Invalid input or a database failure produces a clear error and cleans up any newly saved image. |

These are planned checks. Actual results will be recorded after implementation.

## 5. Report and claim rules

- Lost reports move from Open to Resolved.
- Found reports move from Open to Reserved when a claim is approved, then to Returned after handover.
- Claims begin Pending and become Approved, Rejected, or Closed.
- Closed means another claim was approved for the same report.
- Public photos and descriptions should omit at least one identifying detail so an owner can supply evidence privately.
- The finder reviews evidence manually. The software records the decision.
- For the initial scope, approval is final. Reopening reservations and handling disputes are future improvements.

## 6. Increment plan

1. Accounts. Build registration, sign-in, and sign-out (FR-01).
2. Reports with photos. Build posting, browsing, searching, and image display (FR-02, FR-03, FR-04, FR-10, FR-11).
3. Claims and returns. Build private claims, decisions, and recovery (FR-05 through FR-09), then verify the complete workflow.

Every increment includes relevant validation and testing. Privacy and authorization must be implemented before real claims are used.

## 7. Scope and pending decisions

The proposed first release includes manual claims and returns. One optional photo per report is included. Chat, automated matching, notifications, and an administrator dashboard are future options.

- Team requirements review confirmed by Dipesh.
- Ask classmates how they report and recover lost items. Record actual feedback and resulting changes.
- Database choice: MySQL. Local PHP environment: XAMPP. Verify the installed database server during setup because XAMPP distributions may bundle MariaDB.
- Ask the professor whether additional UML diagrams or a report template are required.
