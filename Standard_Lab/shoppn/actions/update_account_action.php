<?php

// Save edits to the signed-in customer's own profile.

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

// ---------- 1.

require_login();

// ---------- 2.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('views/account/edit_account.php');
}

// ---------- 3.

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Your session expired. Please try again.';
    redirect('views/account/edit_account.php');
}

$id      = (int) core_get_user_id();
$name    = trim(strip_tags($_POST['customer_name']    ?? ''));
$email   = trim(strip_tags($_POST['customer_email']   ?? ''));
$country = trim(strip_tags($_POST['customer_country'] ?? ''));
$city    = trim(strip_tags($_POST['customer_city']    ?? ''));
$contact = trim(strip_tags($_POST['customer_contact'] ?? ''));

$error = '';

if ($name === '' || $email === '' || $country === '' || $city === '' || $contact === '') {
    $error = 'Please fill in every field.';
}

if ($error === '' && mb_strlen($name) > CustomerClass::MAX_NAME) {
    $error = 'Name must be ' . CustomerClass::MAX_NAME . ' characters or fewer.';
}

if ($error === '' && mb_strlen($email) > CustomerClass::MAX_EMAIL) {
    $error = 'Email must be ' . CustomerClass::MAX_EMAIL . ' characters or fewer.';
}

if ($error === '') {
    $error = Validator::checkEmail($email) ?? '';
}

if ($error === '' && !in_array($country, Validator::countries(), true)) {
    $error = 'Please choose a country from the list.';
}

if ($error === '' && mb_strlen($city) > CustomerClass::MAX_CITY) {
    $error = 'City must be ' . CustomerClass::MAX_CITY . ' characters or fewer.';
}

if ($error === '') {
    $error = Validator::checkPhone($contact, $country, CustomerClass::MAX_CONTACT) ?? '';
}

$storedContact = Validator::normalisePhone($contact, $country);

if ($error !== '') {
    // Keep what was typed so the form comes back filled in.
    $_SESSION['old'] = [
        'customer_name'    => $name,
        'customer_email'   => $email,
        'customer_country' => $country,
        'customer_city'    => $city,
        'customer_contact' => $contact,
    ];
    $_SESSION['error'] = $error;
    redirect('views/account/edit_account.php');
}

$controller = new CustomerController();

$result = $controller->updateProfile($id, [
    'name'    => $name,
    'email'   => $email,
    'country' => $country,
    'city'    => $city,
    'contact' => $storedContact,
]);

if ($result['success']) {
    // Keep the header greeting in step with the new name.
    $_SESSION['customer_name']  = $name;
    $_SESSION['customer_email'] = $email;

    unset($_SESSION['error'], $_SESSION['old']);
    $_SESSION['success'] = 'Your details have been saved.';
    redirect('views/account/my_account.php');
}

$_SESSION['old'] = [
    'customer_name'    => $name,
    'customer_email'   => $email,
    'customer_country' => $country,
    'customer_city'    => $city,
    'customer_contact' => $contact,
];
$_SESSION['error'] = $result['error'];
redirect('views/account/edit_account.php');
