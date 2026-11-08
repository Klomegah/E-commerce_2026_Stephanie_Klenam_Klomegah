<?php

/**
 * views/checkout.php - order review page before payment.
 */

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../classes/ProductClass.php';

$page_title = 'Confirm Order';

if (!is_logged_in()) {
    $_SESSION['error'] = 'Please log in before checking out.';
    redirect('views/login.php');
}

if (is_admin()) {
    $_SESSION['error'] = 'Admins do not use the shopping cart. Please use the admin area.';
    redirect('views/admin/product.php');
}

$customerEmail = trim((string) ($_SESSION['customer_email'] ?? ''));

if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Your account has no valid email address. Please log in again.';
    redirect('views/login.php');
}

$cartController = new CartController();
$ip             = get_ip();
$cartItems      = $cartController->getCartItems($ip);
$cartTotal      = $cartController->getCartTotal($ip);

if (empty($cartItems)) {
    $_SESSION['error'] = 'Your cart is empty.';
    redirect('views/cart.php');
}

$itemCount = 0;

foreach ($cartItems as $item) {
    $itemCount += (int) $item['qty'];
}

require_once __DIR__ . '/layout/header.php';
?>

<div class="container">

    <?php require __DIR__ . '/layout/sidebar.php'; ?>

    <main class="main-content">
        <h1>Confirm Your Order</h1>

        <p class="muted">
            Check the items and total below. Payment is the next step, and
            Paystack handles the card or mobile-money details securely.
        </p>

        <div class="review-box">
            <dl>
                <dt>Customer</dt>
                <dd><?= htmlspecialchars($_SESSION['customer_name'] ?? 'Customer') ?></dd>

                <dt>Email</dt>
                <dd><?= htmlspecialchars($customerEmail) ?></dd>

                <dt>Items</dt>
                <dd><?= (int) $itemCount ?></dd>
            </dl>

            <p class="muted">
                Your invoice will be shown immediately after payment.
            </p>
        </div>

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
                <?php foreach ($cartItems as $item):
                    $itemId    = (int) $item['p_id'];
                    $itemTitle = (string) $item['product_title'];
                    $itemImage = (string) ($item['product_image'] ?? '');
                    $itemQty   = (int) $item['qty'];
                    $itemPrice = (float) $item['product_price'];
                    $subtotal  = $itemQty * $itemPrice;
                ?>
                    <tr>
                        <td class="cart-product">
                            <?php if ($itemImage !== ''): ?>
                                <img class="cart-thumb"
                                     src="<?= BASE_URL . ProductClass::IMAGE_DIR . htmlspecialchars($itemImage) ?>"
                                     alt=""
                                     loading="lazy">
                            <?php endif; ?>

                            <a href="<?= BASE_URL ?>views/single_product.php?pro_id=<?= $itemId ?>">
                                <?= htmlspecialchars($itemTitle) ?>
                            </a>
                        </td>

                        <td class="cart-col-qty"><?= $itemQty ?></td>

                        <td class="cart-col-price">
                            GH&cent; <?= number_format($itemPrice, 2) ?>
                        </td>

                        <td class="cart-col-price">
                            GH&cent; <?= number_format($subtotal, 2) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="cart-subtotal-row">
                    <th colspan="3" scope="row">Total</th>
                    <td class="cart-col-price">
                        <strong>GH&cent; <?= number_format($cartTotal, 2) ?></strong>
                    </td>
                </tr>
            </tfoot>
        </table>

        <form method="get" action="<?= BASE_URL ?>views/payment.php">
            <label class="confirm-check" for="confirm-order">
                <input type="checkbox"
                       id="confirm-order"
                       name="confirm_order"
                       value="1"
                       required>

                <span>
                    I confirm these items and the total of
                    <strong>GH&cent; <?= number_format($cartTotal, 2) ?></strong>
                    are correct.
                </span>
            </label>

            <p class="cart-actions">
                <button type="submit" class="btn">Confirm &amp; Pay</button>
                <a class="btn btn-cancel" href="<?= BASE_URL ?>views/cart.php">
                    Back to Cart
                </a>
            </p>
        </form>
    </main>

</div>

<?php
require_once __DIR__ . '/layout/footer.php';
