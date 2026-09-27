<?php
/**
 * index.php - the entry point of the site.
 *
 * Responsibilities, in order:
 *   1. load core.php (session, helpers, security)
 *   2. open the page with the shared header
 *   3. lay out the sidebar beside the page content
 *   4. close with the shared footer
 *
 
 */

require_once __DIR__ . '/core/core.php';

$page_title = 'Home';

require_once __DIR__ . '/views/layout/header.php';
?> 


<div class="container">

    <?php require __DIR__ . '/views/layout/sidebar.php'; ?>

    <main class="main-content">
        <?php require __DIR__ . '/views/home.php'; ?>
    </main>

</div>

<?php
require_once __DIR__ . '/views/layout/footer.php';
