<?php

/**
 * actions/login_action.php - the server entry point for sign-in.
 *
 */

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

// ---------- 1. POST only ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('views/login.php');
}

// ---------- 2. Sanitise ----------
$email = trim(strip_tags($_POST['customer_email'] ?? ''));
$pass  = $_POST['customer_pass'] ?? '';

// ---------- 3. Validate ----------
if ($email === '' || $pass === '') {
    $_SESSION['error'] = 'Please enter your email and password.';
    redirect('views/login.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please enter a valid email address.';
    redirect('views/login.php');
}

if (mb_strlen($email) > CustomerClass::MAX_EMAIL) {
    $_SESSION['error'] = 'Email must be ' . CustomerClass::MAX_EMAIL . ' characters or fewer.';
    redirect('views/login.php');
}

// ---------- 4. Controller, then redirect ----------
$controller = new CustomerController();
$result     = $controller->login($email, $pass);

if ($result['success']) {
    $customer = $result['customer'];

    // New session id on privilege change - defeats session fixation.
    session_regenerate_id(true);

    $_SESSION['customer_id']    = (int) $customer['customer_id'];
    $_SESSION['customer_name']  = $customer['customer_name'];
    $_SESSION['customer_email'] = $customer['customer_email'];
    $_SESSION['user_role']      = (int) $customer['user_role'];

    // Stamp the fingerprint now so core.php's hijack check has a
    // baseline to compare against from this request onwards.
    $_SESSION['fingerprint_ip'] = get_ip();
    $_SESSION['last_activity']  = time();

    unset($_SESSION['error']);

    redirect('index.php');
}

$_SESSION['error'] = $result['error'];
redirect('views/login.php');
