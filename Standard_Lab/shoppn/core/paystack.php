<?php

/**
 * core/paystack.php - Paystack API configuration.
 */

$paystack_cred = __DIR__ . '/paystack_cred.php';

if (is_file($paystack_cred)) {
    require_once $paystack_cred;
}

unset($paystack_cred);

if (!defined('PAYSTACK_SECRET_KEY')) {
    define('PAYSTACK_SECRET_KEY', getenv('PAYSTACK_SECRET_KEY') ?: 'sk_test_YOUR_KEY');
}

if (!defined('PAYSTACK_PUBLIC_KEY')) {
    define('PAYSTACK_PUBLIC_KEY', getenv('PAYSTACK_PUBLIC_KEY') ?: 'pk_test_YOUR_PUBLIC_KEY');
}

/* Paystack charges in the smallest unit (pesewas for GHS, kobo for NGN).
   The View multiplies the cart total by 100 before sending it. */
if (!defined('PAYSTACK_CURRENCY')) {
    define('PAYSTACK_CURRENCY', 'GHS');
}
