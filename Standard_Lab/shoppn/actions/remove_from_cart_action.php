<?php

/**
 * actions/remove_from_cart_action.php - "Remove from Cart" (Task 12).
 *
 * One action, one job: delete the ticked lines from this visitor's cart,
 * then send the browser back to the cart page. It never renders HTML -
 * views/cart.php does that.
 *
 * POST only. Removing is a state change, and a state change over GET
 * would mean <img src="...?remove=5"> on any website could silently
 * empty somebody's basket. The CSRF token closes the same hole for
 * cross-site POSTs.
 */

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

/* ---------- 1. POST only ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('views/cart.php');
}

/* ---------- 2. CSRF (same rule as every other POST action). ---------- */
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Your session expired. Please try again.';
    redirect('views/cart.php');
}

/* ---------- 3. Sanitise + validate ---------- */
$remove = $_POST['remove'] ?? '';

/* The checkboxes are only ever sent as an array (name="remove[]"); a
   single value or a missing field means nothing was ticked. */
if (!is_array($remove)) {
    $_SESSION['error'] = 'Please tick the box next to the item(s) you want to remove.';
    redirect('views/cart.php');
}

$ip = get_ip();

/* ---------- 4. Controller, one call per ticked row ---------- */
$cartController = new CartController();

$removed = 0;
$failed  = 0;

foreach ($remove as $rawId) {
    $product_id = CartController::asPositiveInt($rawId);

    // A crafted submission with rubbish in it is skipped, not fatal -
    // the honest rows still get removed.
    if ($product_id === null) {
        $failed++;
        continue;
    }

    $result = $cartController->removeFromCart($product_id, $ip);

    if ($result['success']) {
        $removed++;
    } else {
        $failed++;
    }
}

/* ---------- 5. Flash + redirect (Post/Redirect/Get). ---------- */
if ($removed > 0 && $failed === 0) {
    $_SESSION['success'] = $removed === 1
        ? '1 item removed from your cart.'
        : $removed . ' items removed from your cart.';
} elseif ($removed > 0) {
    $_SESSION['notice'] = $removed . ' item(s) removed, but ' . $failed
                        . ' could not be removed. Please try again.';
} else {
    $_SESSION['error'] = 'Nothing was removed. Please tick the box next to an item first.';
}

redirect('views/cart.php');
