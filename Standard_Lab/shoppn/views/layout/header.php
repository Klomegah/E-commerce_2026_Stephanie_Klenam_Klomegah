<?php
?>

    
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Shoppn') ?></title>

    <!--
        Cache-busting.

        A browser that has already cached style.css or validate.js will
        not fetch them again, so an edit to those files can appear to do
        nothing: the HTML is new but the old CSS/JS still runs. Appending
        the file's modification time as a query string gives the URL a new
        name every time the file changes, which forces a fresh download,
        and leaves the browser free to cache properly in between.
    -->
    <?php
    $asset_v = function ($relative) {
        $path = __DIR__ . '/../../' . $relative;

        return file_exists($path) ? filemtime($path) : '1';
    };
    ?>
    <link rel="stylesheet"
          href="<?= BASE_URL ?>css/style.css?v=<?= $asset_v('css/style.css') ?>">
    <!--
        defer means the file downloads in parallel and runs after the HTML
        is parsed, so validate.js can rely on the form existing.
        validate.js returns early on pages that have no form, so loading
        it everywhere is harmless.
    -->
    <script src="<?= BASE_URL ?>js/validate.js?v=<?= $asset_v('js/validate.js') ?>" defer></script>
    <!--
        admin.js only does anything on the admin product form, so
        loading it everywhere is harmless. It is a convenience for the
        admin, never a security control - the real file checks run in
        actions/add_product_action.php.
    -->
    <script src="<?= BASE_URL ?>js/admin.js?v=<?= $asset_v('js/admin.js') ?>" defer></script>
</head>
<body>

<header class="site-header">
    <div class="header-inner">

        <a href="<?= BASE_URL ?>" class="logo">Shoppn</a>

        <form action="<?= BASE_URL ?>views/search_results.php" method="GET" class="search-box">
            <input type="text" name="q" placeholder="Search products..." aria-label="Search products">
            <button type="submit">Search</button>
        </form>

        <nav class="site-nav">
            <?php if (is_logged_in()): ?>
                <span class="welcome">
                    Welcome, <?= htmlspecialchars($_SESSION['customer_name'] ?? 'User') ?>
                </span>
                <a href="<?= BASE_URL ?>views/account/my_account.php">My Account</a>

                <!--
                    Admin-only links.

                    is_admin() is checked here so a normal customer never
                    even sees these in the page. That is only cosmetic
                    though - hiding a link stops nobody. The real
                    protection is require_admin() at the top of each admin
                    page and each admin action, which blocks the request
                    even if someone types the address in by hand.
                -->
                <?php if (is_admin()): ?>
                    <a href="<?= BASE_URL ?>views/admin/product.php">Products</a>
                    <a href="<?= BASE_URL ?>views/admin/category.php">Categories</a>
                    <a href="<?= BASE_URL ?>views/admin/brand.php">Brands</a>
                <?php endif; ?>

                <a href="<?= BASE_URL ?>logout.php">Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>views/register.php">Register</a>
                <a href="<?= BASE_URL ?>views/login.php">Login</a>
            <?php endif; ?>
        </nav>

    </div>
</header>
