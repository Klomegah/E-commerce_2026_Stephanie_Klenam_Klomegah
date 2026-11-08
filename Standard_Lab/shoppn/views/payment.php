<?php

/**
 * views/payment.php - Paystack payment page.
 */

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../core/paystack.php';
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../classes/ProductClass.php';

$page_title = 'Payment';

/* Require confirm_order=1 from the review page. */
if (($_GET['confirm_order'] ?? '') !== '1') {
    $_SESSION['error'] = 'Please confirm your order before paying.';
    redirect('views/checkout.php');
}

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

/* Paystack amounts are integers in the smallest unit: GHS 89.50 becomes
   8950 pesewas. round() keeps 89.505-style float noise from truncating a
   pesewa before the cast. */
$amountPesewas = (int) round($cartTotal * 100);

/* The placeholders from core/paystack.php make the page safe to open
   before the student has added real test keys. */
$paystackConfigured = strpos(PAYSTACK_PUBLIC_KEY, 'YOUR_') === false
                   && strpos(PAYSTACK_SECRET_KEY, 'YOUR_') === false;

require_once __DIR__ . '/layout/header.php';
?>

<div class="container">

    <?php require __DIR__ . '/layout/sidebar.php'; ?>

    <main class="main-content">
        <h1>Payment</h1>

        <div class="notice">
            Order confirmed. Complete the payment below with Paystack.
        </div>

        <p class="muted">
            You will be redirected to your invoice once Paystack confirms the
            payment. Your card or mobile-money details are entered inside
            Paystack's secure popup, not on this page.
        </p>

        <?php if (!$paystackConfigured): ?>
            <div class="alert alert-error" role="alert">
                Paystack test keys are not configured yet. Copy
                <code>core/paystack_cred.example.php</code> to
                <code>core/paystack_cred.php</code> and add your test keys
                before trying a payment.
            </div>
        <?php endif; ?>

        <h2>Cart summary</h2>

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

        <p class="cart-actions">
            <button type="button"
                    id="paystack-button"
                    class="btn"
                    onclick="payWithPaystack()"
                    <?= $paystackConfigured ? '' : 'disabled' ?>>
                Pay GH&cent; <?= number_format($cartTotal, 2) ?> with Paystack
            </button>

            <a class="btn btn-cancel" href="<?= BASE_URL ?>views/checkout.php">
                Back to Review
            </a>
        </p>
    </main>

</div>

<!-- Paystack InlineJS v2; the public key is safe to expose. -->
<script src="https://js.paystack.co/v2/inline.js"></script>
<script>
var payButton     = document.getElementById('paystack-button');
var payButtonText = payButton ? payButton.innerHTML : '';
var paymentBusy   = false;

function resetPayButton() {
    if (!payButton) {
        return;
    }

    payButton.disabled = false;
    payButton.innerHTML = payButtonText;
}

function payWithPaystack() {
    if (paymentBusy) {
        return;
    }

    if (typeof PaystackPop === 'undefined') {
        alert('Paystack could not load. Check your internet connection and refresh the page.');
        return;
    }

    paymentBusy = true;

    if (payButton) {
        payButton.disabled = true;
        payButton.innerHTML = 'Opening Paystack...';
    }

    try {
        var popup = new PaystackPop();

        popup.newTransaction({
            key: <?= json_encode(PAYSTACK_PUBLIC_KEY, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            email: <?= json_encode($customerEmail, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            amount: <?= $amountPesewas ?>,
            currency: <?= json_encode(PAYSTACK_CURRENCY, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,

            onSuccess: function (response) {
                /* The reference identifies the charge but does NOT prove it.
                   The Action verifies it with Paystack before recording.
                   Keep the button disabled while the browser navigates. */
                window.location.href = <?= json_encode(BASE_URL . 'actions/process_payment_action.php', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
                                     + '?ref=' + encodeURIComponent(response.reference);
            },

            onCancel: function () {
                paymentBusy = false;
                resetPayButton();
                alert('Payment cancelled. Your cart is still saved.');
            },

            onError: function (error) {
                paymentBusy = false;
                resetPayButton();

                var message = error && error.message ? error.message : 'Unknown Paystack error.';
                alert('Could not start the payment: ' + message);
            }
        });
    } catch (error) {
        paymentBusy = false;
        resetPayButton();

        var message = error && error.message ? error.message : 'Unknown Paystack error.';
        alert('Could not start the payment: ' + message);
    }
}
</script>

<?php
require_once __DIR__ . '/layout/footer.php';
