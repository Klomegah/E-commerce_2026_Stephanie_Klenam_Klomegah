<?php

/**
 * actions/process_payment_action.php - payment callback.
 */

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../core/paystack.php';
require_once __DIR__ . '/../controllers/CartController.php';

/* ---------- 1. The callback is a GET redirect from Paystack. ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    redirect('views/cart.php');
}

/* ---------- 2. A customer must be signed in: Paystack needs an email,
                 and the order is attached to a customer_id. ---------- */
if (!is_logged_in()) {
    $_SESSION['error'] = 'Please log in before completing payment.';
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

/* ---------- 3. Validate the reference before it touches the API URL. ---------- */
$reference = trim((string) ($_GET['ref'] ?? $_GET['reference'] ?? ''));

if ($reference === '' || !preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $reference)) {
    $_SESSION['error'] = 'No valid payment reference was provided.';
    redirect('views/payment_failed.php');
}

/* A refreshed callback URL must not create a second order. Keeping the
   last reference in the session lets a refresh go straight to the invoice
   it already produced. */
if (isset($_SESSION['payment_reference'], $_SESSION['payment_order_id'])
    && $_SESSION['payment_reference'] === $reference) {
    redirect('views/payment_success.php?order_id=' . (int) $_SESSION['payment_order_id']);
}

$ip             = get_ip();
$cartController = new CartController();
$cartItems      = $cartController->getCartItems($ip);

if (empty($cartItems)) {
    $_SESSION['error'] = 'Your cart is empty or this payment has already been processed.';
    redirect('views/payment_failed.php');
}

$cartTotal       = $cartController->getCartTotal($ip);
$expectedPesewas = (int) round($cartTotal * 100);

if ($expectedPesewas < 1) {
    $_SESSION['error'] = 'Your cart total is invalid. Please return to the cart.';
    redirect('views/payment_failed.php');
}

/* ---------- 4. Server-side verification. ----------
   The secret key is sent in an Authorization header, server to server.
   The response is decoded and every field that matters is checked. */
$url = 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference);

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Accept: application/json',
        'Cache-Control: no-cache',
    ],
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT        => 20,
]);

$response  = curl_exec($curl);
$curlError = curl_error($curl);
$httpCode  = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

if ($response === false || $curlError !== '') {
    error_log('Paystack verify request failed for ' . $reference . ': ' . $curlError);
    $_SESSION['error'] = 'We could not reach Paystack to confirm your payment. Please try again.';
    redirect('views/payment_failed.php');
}

if ($httpCode !== 200) {
    error_log('Paystack verify HTTP ' . $httpCode . ' for ' . $reference . ': ' . substr((string) $response, 0, 500));
    $_SESSION['error'] = 'Paystack could not confirm this transaction.';
    redirect('views/payment_failed.php');
}

$result = json_decode((string) $response, true);

if (!is_array($result) || empty($result['status']) || !isset($result['data']) || !is_array($result['data'])) {
    error_log('Paystack verify returned an unexpected body for ' . $reference . ': ' . substr((string) $response, 0, 500));
    $_SESSION['error'] = 'Paystack returned an unexpected response.';
    redirect('views/payment_failed.php');
}

$data              = $result['data'];
$verifiedStatus    = (string) ($data['status'] ?? '');
$verifiedReference = (string) ($data['reference'] ?? '');
$verifiedCurrency  = (string) ($data['currency'] ?? '');
$verifiedAmount    = (int) ($data['amount'] ?? -1);

if ($verifiedStatus !== 'success') {
    $_SESSION['error'] = 'Your payment was not successful, so no order was created.';
    redirect('views/payment_failed.php');
}

if ($verifiedReference !== $reference) {
    error_log('Paystack reference mismatch: asked for ' . $reference . ', got ' . $verifiedReference);
    $_SESSION['error'] = 'The payment reference did not match. No order was created.';
    redirect('views/payment_failed.php');
}

if ($verifiedCurrency !== PAYSTACK_CURRENCY) {
    error_log('Paystack currency mismatch for ' . $reference . ': ' . $verifiedCurrency);
    $_SESSION['error'] = 'The payment currency did not match. No order was created.';
    redirect('views/payment_failed.php');
}

/* The most important check after status. A valid payment for GH¢1 must
   not buy a GH¢500 cart: compare what Paystack charged (pesewas) with
   what this cart actually costs right now. */
if ($verifiedAmount !== $expectedPesewas) {
    error_log(
        'Paystack amount mismatch for ' . $reference . ': paid ' . $verifiedAmount
        . ' pesewas, cart is ' . $expectedPesewas . ' pesewas'
    );
    $_SESSION['error'] = 'The amount paid does not match your cart total. No order was created.';
    redirect('views/payment_failed.php');
}

/* ---------- 5. Record the order (transaction lives in the Model). ---------- */
$order_id = $cartController->processPayment(
    (int) $_SESSION['customer_id'],
    $ip,
    $reference,
    $verifiedAmount / 100
);

if (!$order_id) {
    /* The money was taken but the order could not be written. Do NOT ask
       the customer to pay again - give support the reference instead. */

    $_SESSION['error'] = 'Your payment succeeded, but we could not record the order. '
                       . 'Please contact support and quote reference ' . $reference . '.';
    redirect('views/payment_failed.php');
}

$_SESSION['payment_reference'] = $reference;
$_SESSION['payment_order_id']  = (int) $order_id;

/* ---------- 6. Show the invoice immediately. ---------- */
$_SESSION['success'] = 'Payment received. Your order has been recorded.';
redirect('views/payment_success.php?order_id=' . (int) $order_id);
