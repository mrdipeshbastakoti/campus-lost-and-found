# Accounts
feature covers registration, login, logout, and the account links in the shared header. It uses the existing users table and Database::connect(). No schema change or additional SQL import is needed.

## Files

| File | Responsibility |
|---|---|
| src/Session.php | Starts sessions and creates/checks CSRF tokens |
| src/AuthService.php | Validates registration, hashes/verifies passwords, and manages login/logout |
| includes/bootstrap.php | Starts the session before output and supplies input/HTML helpers |
| register.php | Registration form and submission |
| login.php | Login form and submission |
| logout.php | POST-only logout with a valid CSRF token |
| includes/header.php | Account links and logout form |
| assets/css/accounts.css | Account form and navigation styling |
| index.php | Updates the feature status message |

Registration creates a database record and redirects to login. It does not automatically sign in. A successful login regenerates the session ID and stores only the user ID. Logout clears the session and its cookie. Forms validate on the server even when browser validation is bypassed.

Names allow 1–100 Unicode characters. Emails are trimmed, converted to lowercase, and limited to 255 bytes. Passwords accept 8–72 bytes, reject null bytes, and are never redisplayed. Duplicate emails are rejected using the database's unique constraint, including competing inserts.

Account pages use no-store responses. Account emails and password hashes are not displayed in the public header. Errors from the database go to PHP's error log; visitors see a general failure message.

## Shared API for later features

Public PHP pages stay in the project root for this version. Before any HTML output:

```php
require_once __DIR__ . '/includes/bootstrap.php';
$userId = AuthService::requireLogin();
```

This returns the signed-in user ID or redirects to login. On optional-login pages, use AuthService::currentUserId(), which returns an integer or null. Do not accept the acting user's ID from a form.

Use Session::csrfToken() in hidden form fields and Session::validCsrf(postString('csrf_token')) before state changes. Use escape() for text written into HTML. Keep session handling, POST processing, and redirects before requiring the shared header.

The implementation refines the earlier UML draft: register() returns the new integer user ID rather than a User object; currentUserId() returns ?int. AuthService uses static methods and the existing Database connection. Session is the supporting session/CSRF class. Future diagrams should reflect these signatures.

## Verification before handoff

Eight PHP files passed PHP linting. Thirty-two HTTP/database checks passed on PHP 8.4.27 and MariaDB 11.8.9, using the repository schema and Database.php. Checks included valid/invalid registration, case-insensitive duplicate emails, password hashing, wrong/correct login, session ID rotation, rejected CSRF tokens, POST-only logout, session invalidation, escaped form values, malformed inputs, stylesheet responses, and a handled database failure.

This was a Linux PHP development-server test, not a Windows XAMPP or visual browser test. The code uses PHP 8 syntax. Dipesh must record the XAMPP results separately. No claim is made that the application is error-free.

## Local review checklist

1. Start Apache and MySQL in XAMPP. Keep the existing local configuration, database, and photo folder.
2. Open /campus-lost-and-found/register.php. Submit a new test name, email, and matching password of at least eight English characters. Expect a login page with a success message.
3. Register the same email again, including different capitalization. Expect an error and no second user record.
4. Try mismatched passwords and a short password. Expect rejection.
5. Log in with a wrong password, then the correct password. Expect rejection first, then a homepage with Log out.
6. Refresh the homepage. Expect the session to remain signed in. Open login.php while signed in; expect a redirect home.
7. Click Log out. Expect Register and Log in to return. Refresh and verify you remain signed out.
8. Open logout.php directly in the address bar. Expect a message telling you to use the website's logout button; a GET request must not change login state.
9. In phpMyAdmin, inspect the test user's password_hash. It must be a hash, not the password. Do not share credentials or screenshots containing them.
10. Make the browser narrow. Check navigation wrapping, form labels, keyboard focus, and stylesheet loading.
11. To check CSRF rejection, use browser developer tools to remove or change a form's hidden csrf_token, then submit. Expect HTTP 403 and no account or session change. Reload the form before continuing.

