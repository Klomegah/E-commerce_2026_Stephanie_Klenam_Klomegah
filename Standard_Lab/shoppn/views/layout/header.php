<?php
/**
 * views/layout/header.php - shared page head and navigation.
 *
 * Included at the top of every view. It is a PARTIAL, so it must not
 * load core.php; it assumes core.php is already included by whichever
 * entry point rendered this page.
 *
 * MVC RULE: no SQL in this file. It may read $_SESSION (that is not a
 * database call) and may call helpers, but it never queries.
 * The nav changes based on session state - that is the only logic here.
 *
 * The guard below stops anyone opening this file directly in the browser.
 * Every protected view in this project uses the same guard.
 */

if (!function_exists('is_logged_in')) {
    exit('Direct access forbidden.');
}
?>
<!DOCTYPE html>
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
