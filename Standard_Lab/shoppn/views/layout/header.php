<?php

/* views/layout/header.php - shared header with flash messages and cart summary. */

require_once __DIR__ . '/../../controllers/CartController.php';

$flash_success = $_SESSION['success'] ?? null;
$flash_notice  = $_SESSION['notice'] ?? null;
$flash_error   = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['notice'], $_SESSION['error']);

$cartController = new CartController();
$cart_items     = $cartController->getCartCount(get_ip());
$cart_price     = $cartController->getCartTotal(get_ip());
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Shoppn') ?></title>

    <!-- Cache-bust asset URLs with each file's modification time. -->
    <?php
    $asset_v = function ($relative) {
        $path = __DIR__ . '/../../' . $relative;

        return file_exists($path) ? filemtime($path) : '1';
    };
    ?>
    <link rel="stylesheet"
          href="<?= BASE_URL ?>css/style.css?v=<?= $asset_v('css/style.css') ?>">
    <!-- validate.js runs after the HTML is parsed. -->
    <script src="<?= BASE_URL ?>js/validate.js?v=<?= $asset_v('js/validate.js') ?>" defer></script>
    <!-- admin.js only does anything on the admin product form. -->
    <script src="<?= BASE_URL ?>js/admin.js?v=<?= $asset_v('js/admin.js') ?>" defer></script>
    <!-- Styled confirm dialog for destructive forms (data-confirm). -->
    <script src="<?= BASE_URL ?>js/confirm.js?v=<?= $asset_v('js/confirm.js') ?>" defer></script>
</head>
<body>

<header class="site-header">
    <div class="header-inner">

        <a href="<?= BASE_URL ?>" class="logo">Shoppn</a>

        <form action="<?= BASE_URL ?>views/search_results.php" method="GET" class="search-box">
            <input type="text" name="user_query" placeholder="Search products..." aria-label="Search products">
            <button type="submit">Search</button>
        </form>

        <nav class="site-nav">
            <!-- Cart summary for this visitor; hidden from admins. -->
            <?php if (!is_admin()): ?>
            <a href="<?= BASE_URL ?>views/cart.php" class="cart-summary"
               title="Go to your shopping cart">
                Shopping Cart &mdash;
                Total Items: <strong><?= $cart_items ?></strong> |
                Total Price: <strong>GH&cent; <?= number_format($cart_price, 2) ?></strong> |
                Go to Cart
            </a>
            <?php endif; ?>

            <?php if (is_logged_in()): ?>
                <span class="welcome">
                    Welcome, <?= htmlspecialchars($_SESSION['customer_name'] ?? 'User') ?>
                </span>
                <a href="<?= BASE_URL ?>views/account/my_account.php">My Account</a>

                <?php if (!is_admin()): ?>
                    <a href="<?= BASE_URL ?>views/account/my_orders.php">My Orders</a>
                <?php endif; ?>

                <!-- Admin links; real access control is require_admin(). -->
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

<?php if ($flash_success !== null || $flash_notice !== null || $flash_error !== null): ?>
    <!-- role="status" makes screen readers announce the message without
         stealing focus; the error variant adds role="alert" for urgency. -->
    <div class="flash-wrap" role="status" aria-live="polite">
        <?php if ($flash_success !== null): ?>
            <div class="notice"><?= htmlspecialchars($flash_success) ?></div>
        <?php endif; ?>

        <?php if ($flash_notice !== null): ?>
            <div class="notice"><?= htmlspecialchars($flash_notice) ?></div>
        <?php endif; ?>

        <?php if ($flash_error !== null): ?>
            <div class="alert alert-error" role="alert"><?= htmlspecialchars($flash_error) ?></div>
        <?php endif; ?>
    </div>
<?php endif; ?>
