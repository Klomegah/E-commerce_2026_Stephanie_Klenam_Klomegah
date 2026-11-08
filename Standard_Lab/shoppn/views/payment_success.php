<?php

/**
 * views/payment_success.php - payment invoice page.
 */

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

$page_title = 'Payment Received';

if (!is_logged_in()) {
    $_SESSION['error'] = 'Please log in to view your invoice.';
    redirect('views/login.php');
}

$orderId = CartController::asPositiveInt($_GET['order_id'] ?? null);

if ($orderId === null) {
    $_SESSION['error'] = 'Invalid order.';
    redirect('views/cart.php');
}

$cartController = new CartController();
$order          = $cartController->getOrderById($orderId);

if ($order === null) {
    $_SESSION['error'] = 'Order not found.';
    redirect('views/cart.php');
}

if (!is_admin() && (int) $order['customer_id'] !== (int) ($_SESSION['customer_id'] ?? 0)) {
    $_SESSION['error'] = 'That invoice does not belong to your account.';
    redirect('views/cart.php');
}

/* Read the flash BEFORE the header prints it, the same convention as
   cart.php: the message belongs in the page body, and it must show once. */
$success = $_SESSION['success'] ?? null;

unset($_SESSION['success']);

require_once __DIR__ . '/layout/header.php';
?>

<div class="container">

    <?php require __DIR__ . '/layout/sidebar.php'; ?>

    <main class="main-content">
        <h1>Payment Received</h1>

        <?php if ($success !== null): ?>
            <div class="notice"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <p>
            Thank you for your order. Your invoice is below.
        </p>

        <h2>Invoice</h2>

        <table class="cart-table">
            <tbody>
                <tr>
                    <th scope="row">Order number</th>
                    <td><?= htmlspecialchars((string) $order['order_id']) ?></td>
                </tr>
                <tr>
                    <th scope="row">Invoice number</th>
                    <td><?= htmlspecialchars((string) $order['invoice_no']) ?></td>
                </tr>
                <tr>
                    <th scope="row">Order date</th>
                    <td><?= htmlspecialchars((string) $order['order_date']) ?></td>
                </tr>
                <tr>
                    <th scope="row">Status</th>
                    <td><?= htmlspecialchars((string) $order['order_status']) ?></td>
                </tr>
            </tbody>
        </table>

        <h3>Items</h3>

        <table class="cart-table">
            <thead>
                <tr>
                    <th scope="col">Product</th>
                    <th scope="col" class="cart-col-qty">Quantity</th>
                    <th scope="col" class="cart-col-price">Unit price</th>
                    <th scope="col" class="cart-col-price">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = 0.0;

                foreach ($order['order_details'] as $detail):
                    $qty      = (int) $detail['qty'];
                    $price    = (float) $detail['product_price'];
                    $subtotal = $qty * $price;
                    $total   += $subtotal;
                ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $detail['product_title']) ?></td>
                        <td class="cart-col-qty"><?= $qty ?></td>
                        <td class="cart-col-price">GH&cent; <?= number_format($price, 2) ?></td>
                        <td class="cart-col-price">GH&cent; <?= number_format($subtotal, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="cart-subtotal-row">
                    <th colspan="3" scope="row">Total paid</th>
                    <td class="cart-col-price">
                        <strong>GH&cent; <?= number_format($total, 2) ?></strong>
                    </td>
                </tr>
            </tfoot>
        </table>

        <p class="cart-actions">
            <a class="btn" href="<?= BASE_URL ?>index.php">Continue Shopping</a>
            <?php if (!is_admin()): ?>
                <a class="btn btn-cancel" href="<?= BASE_URL ?>views/account/my_orders.php">
                    My Orders
                </a>
            <?php endif; ?>
        </p>
    </main>

</div>

<?php
require_once __DIR__ . '/layout/footer.php';
