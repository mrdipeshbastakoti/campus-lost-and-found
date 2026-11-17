# Shared layout

The homepage uses Arial, a white background, and a single-column layout. Header, footer, and CSS are shared so the feature pages can use the same appearance.

## Run locally

Start Apache in XAMPP and open http://localhost/campus-lost-and-found/.

Check that the stylesheet loads, the navigation links work, and the page fits a narrow mobile window. Tab through the links to check keyboard focus.

## Adding a page

Keep public PHP pages in the project root for this version. Set `$pageTitle` and `$activePage` before including `includes/header.php`. Include `includes/footer.php` after the page content. The header escapes the page title. New pages must escape their own user-provided content.

For example, an account page can set `$activePage = 'accounts'`. Add its navigation link when the account feature is ready. Session handling and redirects belong before the header outputs HTML.

The homepage currently introduces the project. It does not query reports or implement accounts. Shiva will replace the introductory content with report browsing when that feature is built.

## Verification

The file paths and includes were reviewed. PHP was unavailable in the preparation environment, so PHP linting and XAMPP browser testing remain pending. Existing database setup results do not test these new layout files.
