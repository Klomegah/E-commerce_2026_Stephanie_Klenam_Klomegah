<?php

/**
 * views/cart.php - the visitor's shopping cart (Tasks 11 + 12).
 *
 * An ENTRY POINT, like my_account.php: it boots core itself, works out
 * what to show, then includes the shared header/sidebar/footer.
 *
 * Deliberately NOT behind require_login(): a cart row belongs to an IP
 * address, so a guest can browse, add, update and remove exactly like a
 * signed-in customer. Nothing on this page reads $_SESSION['customer_id'].
 *
 * It displays data and nothing else - the actual adding, removing and
 * updating happen in the three action files, each of which redirects
 * back here when it is done (the Post/Redirect/Get pattern, so a refresh
 * never asks to re-submit).
 */

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';
// Loaded for its IMAGE_DIR constant, so the image path lives in one place.
require_once __DIR__ . '/../classes/ProductClass.php';

$page_title = 'Shopping Cart';

$cartController = new CartController();
$ip = get_ip();

$cartItems = $cartController->getCartItems($ip);
$cartCount = $cartController->getCartCount($ip);
$cartTotal = $cartController->getCartTotal($ip);

// Flash messages, read and cleared BEFORE the header is printed - the
// same convention as my_account.php. The header renders any flash it
// finds, so by the time it runs these are already gone: one message,
// shown once, in the page body where it belongs.
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

            <!--
                TWO FORMS, ONE TABLE.

                "Remove" and "Update" are two separate operations with two
                separate action files, so they must be two separate forms -
                and a <form> cannot wrap half of a table row. The HTML5
                form="" attribute solves this: each control names the form
                it belongs to, wherever it sits in the document. Both forms
                live here (empty except for their CSRF token), and the
                checkboxes / quantity boxes below simply point at them.

                The alternative - nesting one form inside the other - is
                invalid HTML that browsers "repair" in unpredictable ways,
                and a single form would have to post to one action file,
                mixing two jobs back together.
            -->
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

                                <!-- Tells update_qty_action.php WHICH
                                     product this quantity belongs to. The
                                     two arrays are index-matched: this
                                     hidden field and the qty box above are
                                     rendered together, in the same order. -->
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

            <!--
                The two submit buttons live OUTSIDE both forms on purpose
                (a button inside form A would submit form A), and point at
                their form with the same form="" attribute. Ticking no
                checkbox and pressing "Remove from Cart" submits an empty
                list, which the action answers with a friendly prompt.
            -->
            <p class="cart-actions">
                <button type="submit" form="cart-remove-form" class="btn btn-danger">
                    Remove from Cart
                </button>
                <button type="submit" form="cart-update-form" class="btn">
                    Update Quantity
                </button>
                <a class="btn btn-cancel" href="<?= BASE_URL ?>index.php">Continue Shopping</a>
                <!-- Checkout is Task 13; the link exists now so the flow
                     is complete end to end. -->
                <a class="btn" href="<?= BASE_URL ?>views/checkout.php">Checkout</a>
            </p>

        <?php endif; ?>
    </main>

</div>

<?php
require_once __DIR__ . '/layout/footer.php';
