<?php

/**
 * actions/update_qty_action.php - "Update Quantity" (Task 12).
 *
 * One action, one job: rewrite the quantities typed into the cart page,
 * then send the browser back to the cart page.
 *
 * The form posts two parallel arrays:
 *     qty[]     - the quantity typed in each row
 *     update[]  - the product_id that row belongs to
 * Both travel as arrays because one submit button updates every row at
 * once. The two lists are index-matched: qty[0] belongs to update[0],
 * which is why the View renders them inside the same <td>, in order.
 *
 * POST only + CSRF, for the same reasons as remove_from_cart_action.php.
 */

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

/* ---------- 1. POST only ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('views/cart.php');
}

/* ---------- 2. CSRF ---------- */
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Your session expired. Please try again.';
    redirect('views/cart.php');
}

/* ---------- 3. Sanitise + validate both arrays ---------- */
$qtys = $_POST['qty']    ?? '';
$ids  = $_POST['update'] ?? '';

if (!is_array($qtys) || !is_array($ids) || count($ids) === 0) {
    $_SESSION['error'] = 'No quantities were submitted. Please open the cart and try again.';
    redirect('views/cart.php');
}

$ip = get_ip();

/* ---------- 4. Controller, one call per row ---------- */
$cartController = new CartController();

$updated = 0;
$failed  = 0;

foreach ($ids as $index => $rawId) {
    $product_id = CartController::asPositiveInt($rawId);
    $qty        = CartController::asPositiveInt($qtys[$index] ?? null);

    // Every row must pass both checks. One rubbish value fails that one
    // row only - it never stops the honest rows next to it, and it never
    // reaches the Model.
    if ($product_id === null || $qty === null || $qty > CartController::MAX_QTY) {
        $failed++;
        continue;
    }

    $result = $cartController->updateQty($product_id, $qty, $ip);

    if ($result['success']) {
        $updated++;
    } else {
        $failed++;
    }
}

/* ---------- 5. Flash + redirect (Post/Redirect/Get). ---------- */
if ($updated > 0 && $failed === 0) {
    $_SESSION['success'] = $updated === 1
        ? 'Quantity updated for 1 item.'
        : 'Quantity updated for ' . $updated . ' items.';
} elseif ($updated > 0) {
    $_SESSION['notice'] = $updated . ' quantity update(s) saved, but ' . $failed
                        . ' row(s) were skipped. Quantities must be whole numbers between 1 and '
                        . CartController::MAX_QTY . '.';
} else {
    $_SESSION['error'] = 'No quantities were updated. Quantities must be whole numbers between 1 and '
                       . CartController::MAX_QTY . '.';
}

redirect('views/cart.php');
