<?php

/**
 * views/account/my_orders.php - customer order history.
 */

require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/CartController.php';

require_login();

$page_title = 'My Orders';

if (is_admin()) {
    $_SESSION['error'] = 'Admins do not have a customer order history. Please use the admin area.';
    redirect('views/admin/product.php');
}

$cartController = new CartController();
$orders         = $cartController->getOrdersByCustomer(core_get_user_id());

require_once __DIR__ . '/../layout/header.php';
?>

<div class="container">

    <?php require __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="main-content">
        <h1>My Orders</h1>

        <p class="muted">
            Every order on this account, newest first. Open an invoice to
            see the items and the amount paid.
        </p>

        <?php if (empty($orders)): ?>
            <div class="notice">
                You have not placed any orders yet.
            </div>

            <p class="cart-actions">
                <a class="btn" href="<?= BASE_URL ?>index.php">Continue Shopping</a>
                <a class="btn btn-cancel" href="<?= BASE_URL ?>views/account/my_account.php">
                    My Account
                </a>
            </p>
        <?php else: ?>
            <table class="cart-table order-history">
                <thead>
                    <tr>
                        <th scope="col">Order</th>
                        <th scope="col">Date</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="cart-col-qty">Items</th>
                        <th scope="col" class="cart-col-price">Total</th>
                        <th scope="col"><span class="sr-only">Invoice</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order):
                        $orderId     = (int) $order['order_id'];
                        $invoiceNo   = (string) $order['invoice_no'];
                        $orderDate   = (string) $order['order_date'];
                        $status      = (string) $order['order_status'];
                        $itemCount   = (int) $order['item_count'];
                        $orderTotal  = (float) $order['order_total'];

                        $timestamp   = strtotime($orderDate);
                        $dateLabel   = $timestamp !== false ? date('d M Y', $timestamp) : $orderDate;
                        $statusLabel = ucfirst(str_replace('_', ' ', $status));
                        $statusClass = preg_replace('/[^a-z0-9_-]/', '', strtolower($status));
                    ?>
                        <tr>
                            <td>
                                <strong>#<?= $orderId ?></strong><br>
                                <span class="muted">Inv. <?= htmlspecialchars($invoiceNo) ?></span>
                            </td>

                            <td><?= htmlspecialchars($dateLabel) ?></td>

                            <td>
                                <span class="order-status order-status-<?= htmlspecialchars($statusClass) ?>">
                                    <?= htmlspecialchars($statusLabel) ?>
                                </span>
                            </td>

                            <td class="cart-col-qty"><?= $itemCount ?></td>

                            <td class="cart-col-price">
                                GH&cent; <?= number_format($orderTotal, 2) ?>
                            </td>

                            <td>
                                <a class="btn btn-small"
                                   href="<?= BASE_URL ?>views/payment_success.php?order_id=<?= $orderId ?>">
                                    View invoice
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="cart-actions">
                <a class="btn" href="<?= BASE_URL ?>index.php">Continue Shopping</a>
                <a class="btn btn-cancel" href="<?= BASE_URL ?>views/account/my_account.php">
                    My Account
                </a>
            </p>
        <?php endif; ?>
    </main>

</div>

<?php
require_once __DIR__ . '/../layout/footer.php';
