<?php
/**
 * Header partial for the Shoppn application.
 *
 * This file is included at the top of every page, before the main
 * content and sidebar. It contains the opening <html> and <body>
 * tags, as well as any header content you want to display on every page.
 *
 */
?>


<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Shoppn') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <!--
        defer means the file downloads in parallel and runs after the HTML
        is parsed, so validate.js can rely on the form existing.
        validate.js returns early on pages that have no form, so loading
        it everywhere is harmless.
    -->
    <script src="<?= BASE_URL ?>js/validate.js" defer></script>
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
                <?php if (is_admin()): ?>
                    <a href="<?= BASE_URL ?>views/admin/product.php">Admin</a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>logout.php">Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>views/register.php">Register</a>
                <a href="<?= BASE_URL ?>views/login.php">Login</a>
            <?php endif; ?>
        </nav>

    </div>
</header>
