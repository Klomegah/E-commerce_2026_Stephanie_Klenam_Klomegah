<?php

/**
 * actions/register_action.php - the server entry point for sign-up.
 */

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

// ---------- 1. POST only ----------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('views/register.php');
}

// ---------- 2. Sanitise ----------


$name    = trim(strip_tags($_POST['customer_name']    ?? ''));
$email   = trim(strip_tags($_POST['customer_email']   ?? ''));
$pass    = $_POST['customer_pass'] ?? '';  
$country = trim(strip_tags($_POST['customer_country'] ?? ''));
$city    = trim(strip_tags($_POST['customer_city']    ?? ''));
$contact = trim(strip_tags($_POST['customer_contact'] ?? ''));

// ---------- 3. Validate ----------

$error = '';

if ($name === '' || $email === '' || $pass === '' || $country === '' || $city === '' || $contact === '') {
    $error = 'Please fill in every field.';
}

if ($error === '' && mb_strlen($name) > CustomerClass::MAX_NAME) {
    $error = 'Name must be ' . CustomerClass::MAX_NAME . ' characters or fewer.';
}

if ($error === '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Please enter a valid email address.';
}

// The brief says "VARCHAR 100" here, but the live column is varchar(50).
// Checking against the real column is the point of the exercise.
if ($error === '' && mb_strlen($email) > CustomerClass::MAX_EMAIL) {
    $error = 'Email must be ' . CustomerClass::MAX_EMAIL . ' characters or fewer.';
}

if ($error === '' && mb_strlen($country) > CustomerClass::MAX_COUNTRY) {
    $error = 'Country must be ' . CustomerClass::MAX_COUNTRY . ' characters or fewer.';
}

if ($error === '' && mb_strlen($city) > CustomerClass::MAX_CITY) {
    $error = 'City must be ' . CustomerClass::MAX_CITY . ' characters or fewer.';
}

if ($error === '' && mb_strlen($contact) > CustomerClass::MAX_CONTACT) {
    $error = 'Contact number must be ' . CustomerClass::MAX_CONTACT . ' characters or fewer.';
}

// Minimum viable password length. Cheap, and it genuinely helps.
if ($error === '' && strlen($pass) < 6) {
    $error = 'Password must be at least 6 characters.';
}

if ($error !== '') {
 
    $_SESSION['old'] = [
        'customer_name'    => $name,
        'customer_email'   => $email,
        'customer_country' => $country,
        'customer_city'    => $city,
        'customer_contact' => $contact,
    ];
    $_SESSION['error'] = $error;
    redirect('views/register.php');
}

// ---------- 4. Controller, then redirect ----------
$controller = new CustomerController();

$result = $controller->register([
    'name'    => $name,
    'email'   => $email,
    'pass'    => $pass,
    'country' => $country,
    'city'    => $city,
    'contact' => $contact,
]);

if ($result['success']) {
    $customer = $result['customer'];

    // A brand-new session id the moment privileges change, so a session
    // ID planted before sign-up cannot be reused afterwards.

    session_regenerate_id(true);

    $_SESSION['customer_id']    = (int) $customer['customer_id'];
    $_SESSION['customer_name']  = $customer['customer_name'];
    $_SESSION['customer_email'] = $customer['customer_email'];

   
    $_SESSION['user_role'] = (int) $customer['user_role'];

    unset($_SESSION['error'], $_SESSION['old']);

    redirect('views/account/my_account.php');
}

// Failed - the reason is one of ours, never a raw mysqli message.
$_SESSION['old'] = [
    'customer_name'    => $name,
    'customer_email'   => $email,
    'customer_country' => $country,
    'customer_city'    => $city,
    'customer_contact' => $contact,
];
$_SESSION['error'] = $result['error'];
redirect('views/register.php');
