<?php

/**
 * actions/add_to_cart_action.php - "Add to Cart" (Task 11).
 *
 * Entry point for the Add to Cart links on the product cards and the
 * single-product page. The links are plain GET links carrying
 * ?add_cart=N, matching the original shop pattern this project is based
 * on - so this file is a state-changing GET endpoint by design, and the
 * notes in CART_MANAGEMENT_EXPLAINED.md spell out what that costs and
 * what a production shop would do instead (POST + CSRF token).
 *
 * Flow: validate the id -> the product must exist -> duplicate check ->
 * insert -> redirect back to whichever page the visitor came from.
 * Every outcome is a one-line flash message plus a redirect, never any
 * HTML - the Views own all output.
 */

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

/**
 * Where to send the visitor afterwards: back to the page the click came
 * from, so adding from a search result returns to the search results.
 *
 * HTTP_REFERER is attacker-influencable, so it is NOT trusted blindly:
 * only the path of a referer that lives inside this application (it
 * starts with BASE_URL) is followed. Anything else - no referer at all,
 * or one from some other site - falls back to the home page. That closes
 * the open-redirect hole: this file can never be used to bounce people
 * to a malicious URL.
 *
 * @return string a path relative to BASE_URL, safe for redirect()
 */
function cart_back_to_referrer()
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';

    if ($ref === '') {
        return 'index.php';
    }

    $parts = parse_url($ref);

    if ($parts === false || empty($parts['path'])) {
        return 'index.php';
    }

    if (strpos($parts['path'], BASE_URL) !== 0) {
        return 'index.php';
    }

    $target = substr($parts['path'], strlen(BASE_URL));

    if (isset($parts['query']) && $parts['query'] !== '') {
        $target .= '?' . $parts['query'];
    }

    return $target !== '' ? $target : 'index.php';
}

/* ---------- 1. GET only (the links are <a href> tags). ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    redirect('index.php');
}

/* ---------- 2. Sanitise + validate the product id. ---------- */
$maybeId = CartController::asPositiveInt($_GET['add_cart'] ?? null);

if ($maybeId === null) {
    $_SESSION['error'] = 'That product could not be added. Please try again from a product page.';
    redirect('index.php');
}

$product_id = $maybeId;
$ip         = get_ip();

/* ---------- 3. The product must still exist. ----------
   A stale bookmark or a hand-typed URL must not create a cart row that
   points at nothing - the cart page would then have no title or price
   to show for it (the INNER JOIN hides the row, so the visitor would
   see a wrong item count and total). */
$productController = new ProductController();

if ($productController->getPublicProductById($product_id) === null) {
    $_SESSION['error'] = 'That product no longer exists.';
    redirect('index.php');
}

$cartController = new CartController();

/* ---------- 4. Duplicate detection (the "already in cart" rule). ---------- */
if ($cartController->isInCart($product_id, $ip)) {
    // 'notice', not 'error': nothing went wrong, and the header renders
    // it as a calm blue message rather than a red alert.
    $_SESSION['notice'] = 'This item is already in your cart. Go to cart to update quantity.';
    redirect(cart_back_to_referrer());
}

/* ---------- 5. Insert. Qty 1 - the link adds one unit; more is what
                 the cart page's quantity box is for. core_get_user_id()
                 passes the customer id when there is one, null for a
                 guest; the Model records the IP either way. ---------- */
$result = $cartController->addToCart($product_id, 1, $ip, core_get_user_id());

if ($result['success']) {
    $_SESSION['success'] = 'Added to your cart.';
    redirect(cart_back_to_referrer());
}

$_SESSION['error'] = $result['error'];
redirect(cart_back_to_referrer());
