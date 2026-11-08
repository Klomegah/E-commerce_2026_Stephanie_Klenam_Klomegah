<?php

/**
 * views/cart.php - the visitor's shopping cart.
 */

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

// Loaded for its IMAGE_DIR constant, so the image path lives in one place.
require_once __DIR__ . '/../classes/ProductClass.php';

$page_title = 'Shopping Cart';

// Gate: admins should not access the customer cart.
if (is_admin()) {
    $_SESSION['error'] = 'Admins do not use the shopping cart. Please use the admin area.';
    redirect('views/admin/product.php');
}

$cartController = new CartController();
$ip = get_ip();

$cartItems = $cartController->getCartItems($ip);
$cartCount = $cartController->getCartCount($ip);
$cartTotal = $cartController->getCartTotal($ip);


$success = $_SESSION['success'] ?? null;
$notice  = $_SESSION['notice'] ?? null;
$error   = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['notice'], $_SESSION['error']);

require_once __DIR__ . '/layout/header.php';
?>

<div class="container">

    <?php require __DIR__ . '/layout/sidebar.php'; ?>

    <main class="main-content">
        <h1>Shopping Cart</h1>

        <?php if ($success !== null): ?>
            <div class="notice"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($notice !== null): ?>
            <div class="notice"><?= htmlspecialchars($notice) ?></div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>

            <p class="muted">Your cart is empty.</p>

            <p class="cart-actions">
                <a class="btn" href="<?= BASE_URL ?>index.php">Continue Shopping</a>
            </p>

        <?php else: ?>

            <!-- Remove and Update post through separate forms. -->

            <form id="cart-remove-form" method="POST"
                  action="<?= BASE_URL ?>actions/remove_from_cart_action.php">
                <?= csrf_field() ?>
            </form>

            <form id="cart-update-form" method="POST"
                  action="<?= BASE_URL ?>actions/update_qty_action.php">
                <?= csrf_field() ?>
            </form>

            <table class="cart-table">
                <thead>
                    <tr>
                        <th scope="col" class="cart-col-check">Remove</th>
                        <th scope="col">Product</th>
                        <th scope="col" class="cart-col-qty">Qty</th>
                        <th scope="col" class="cart-col-price">Unit price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item):
                        $itemId    = (int) $item['p_id'];
                        $itemTitle = (string) $item['product_title'];
                        $itemImage = (string) ($item['product_image'] ?? '');
                        $itemQty   = (int) $item['qty'];
                        $itemPrice = (float) $item['product_price'];
                    ?>
                        <tr>
                            <td class="cart-col-check">

                                <!-- The aria-label is what a screen reader
                                     announces; without it the box is just
                                     an unlabelled "checkbox". -->

                                <input type="checkbox"
                                       form="cart-remove-form"
                                       name="remove[]"
                                       value="<?= $itemId ?>"
                                       aria-label="Remove <?= htmlspecialchars($itemTitle) ?> from cart">
                            </td>

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

                            <td class="cart-col-qty">

                                <!-- min/max are a convenience for the
                                     keyboard, NOT a safety net: the action
                                     re-validates every value server-side. -->

                                <input type="number"
                                       form="cart-update-form"
                                       name="qty[]"
                                       value="<?= $itemQty ?>"
                                       min="1"
                                       max="<?= (int) CartController::MAX_QTY ?>"
                                       class="cart-qty-input"
                                       aria-label="Quantity for <?= htmlspecialchars($itemTitle) ?>">

                                <!-- Identifies which product this quantity belongs to. -->

                                <input type="hidden"
                                       form="cart-update-form"
                                       name="update[]"
                                       value="<?= $itemId ?>">
                            </td>

                            <td class="cart-col-price">
                                GH&cent; <?= number_format($itemPrice, 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="cart-subtotal-row">
                        <th colspan="3" scope="row">
                            Subtotal (<?= $cartCount ?> item<?= $cartCount === 1 ? '' : 's' ?>)
                        </th>
                        <td class="cart-col-price">
                            GH&cent; <?= number_format($cartTotal, 2) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>

            <!-- Submit buttons point at their forms with the form attribute. -->

            <p class="cart-actions">
                <button type="submit" form="cart-remove-form" class="btn btn-danger">
                    Remove from Cart
                </button>
                <button type="submit" form="cart-update-form" class="btn">
                    Update Quantity
                </button>
                <a class="btn btn-cancel" href="<?= BASE_URL ?>index.php">Continue Shopping</a>

                <!-- Task 13: the customer confirms the order on checkout.php
                     before payment.php opens the Paystack popup. -->

                <a class="btn" href="<?= BASE_URL ?>views/checkout.php">Checkout</a>
            </p>

        <?php endif; ?>
    </main>

</div>

<?php
require_once __DIR__ . '/layout/footer.php';
