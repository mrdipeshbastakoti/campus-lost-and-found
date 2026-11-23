<?php
$pageTitle = 'Home | Campus Lost & Found';
require __DIR__ . '/includes/header.php';
?>
<section class="intro">
    <h1>Campus lost and found</h1>
    <p>Report lost belongings, browse found items, and help return them to their owners.</p>
</section>
<section class="notice" aria-labelledby="status-heading">
    <h2 id="status-heading">The website is being built</h2>
    <p>Registration and login are available. Item reports and claims are coming next.</p>
</section>
<section id="how-it-works">
    <h2>How it works</h2>
    <ol class="steps">
        <li><strong>Report an item</strong><p>Add the item name, location, date, and an optional photo.</p></li>
        <li><strong>Check the reports</strong><p>Search for your belongings. If you recognise a found item, sign in and send a private ownership claim.</p></li>
        <li><strong>Arrange the return</strong><p>The finder reviews the claim and approves it. After handover, the finder marks the item as returned.</p></li>
    </ol>
</section>
<p class="reminder">Keep one identifying detail private so the owner can use it to support their claim.</p>
<?php require __DIR__ . '/includes/footer.php'; ?>
