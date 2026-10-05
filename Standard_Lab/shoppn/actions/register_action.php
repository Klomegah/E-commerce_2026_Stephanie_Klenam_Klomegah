<?php


require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

// ---------- 1.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('views/register.php');
}



$name    = trim(strip_tags($_POST['customer_name']    ?? ''));
$email   = trim(strip_tags($_POST['customer_email']   ?? ''));
$pass    = $_POST['customer_pass'] ?? '';  
$country = trim(strip_tags($_POST['customer_country'] ?? ''));
$city    = trim(strip_tags($_POST['customer_city']    ?? ''));
$contact = trim(strip_tags($_POST['customer_contact'] ?? ''));

// The retyped password, checked only to confirm the two boxes agree.
$passConfirm = $_POST['customer_pass_confirm'] ?? null;


$error = '';

if ($name === '' || $email === '' || $pass === '' || $country === '' || $city === '' || $contact === '') {
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

if ($error === '' && mb_strlen($country) > CustomerClass::MAX_COUNTRY) {
    $error = 'Country must be ' . CustomerClass::MAX_COUNTRY . ' characters or fewer.';
}

if ($error === '' && mb_strlen($city) > CustomerClass::MAX_CITY) {
    $error = 'City must be ' . CustomerClass::MAX_CITY . ' characters or fewer.';
}

if ($error === '') {
    $error = Validator::checkPhone($contact, $country, CustomerClass::MAX_CONTACT) ?? '';
}

// Full strength rules, not just a length.
// leading and trailing spaces are legal password characters.
if ($error === '') {
    $error = Validator::checkPassword($pass, $email, $passConfirm) ?? '';
}

$storedContact = Validator::normalisePhone($contact, $country);

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

$controller = new CustomerController();

$result = $controller->register([
    'name'    => $name,
    'email'   => $email,
    'pass'    => $pass,
    'country' => $country,
    'city'    => $city,
    'contact' => $storedContact,
]);

if ($result['success']) {
    $customer = $result['customer'];

    // A brand-new session id the moment privileges change, so a session.

    session_regenerate_id(true);

    $_SESSION['customer_id']    = (int) $customer['customer_id'];
    $_SESSION['customer_name']  = $customer['customer_name'];
    $_SESSION['customer_email'] = $customer['customer_email'];

   
    $_SESSION['user_role'] = (int) $customer['user_role'];

    unset($_SESSION['error'], $_SESSION['old']);

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
redirect('views/register.php');
