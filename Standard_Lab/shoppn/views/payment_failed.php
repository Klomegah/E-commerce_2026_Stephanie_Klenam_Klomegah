<?php

/**
 * views/payment_failed.php - payment failure page.
 */

require_once __DIR__ . '/../core/core.php';

$page_title = 'Payment Failed';

/* Read and clear the flash before the header runs. */
$error = $_SESSION['error'] ?? 'Payment failed. Please try again.';
unset($_SESSION['error']);

require_once __DIR__ . '/layout/header.php';
?>

<div class="container">

    <?php require __DIR__ . '/layout/sidebar.php'; ?>

    <main class="main-content">
        <h1>Payment Failed</h1>

        <div class="alert alert-error" role="alert">
            <?= htmlspecialchars($error) ?>
        </div>

        <p>
            No order is created until Paystack confirms the payment
            server-to-server. If you were charged but see this page, please
            contact support with your payment reference before trying again.
        </p>

        <p class="cart-actions">
            <a class="btn" href="<?= BASE_URL ?>views/cart.php">Back to Cart</a>
            <a class="btn btn-cancel" href="<?= BASE_URL ?>index.php">Continue Shopping</a>
        </p>
    </main>

</div>

<?php
require_once __DIR__ . '/layout/footer.php';
