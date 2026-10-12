<?php

// Change the signed-in customer's password (current password required).

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

// ---------- 1.

require_login();

// ---------- 2.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('views/account/change_pass.php');
}

// ---------- 3.

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Your session expired. Please try again.';
    redirect('views/account/change_pass.php');
}

$id      = (int) core_get_user_id();
$current = $_POST['customer_pass_current'] ?? '';
$new     = $_POST['customer_pass'] ?? '';
$confirm = $_POST['customer_pass_confirm'] ?? null;

if ($current === '') {
    $_SESSION['error'] = 'Please enter your current password.';
    redirect('views/account/change_pass.php');
}

// The new password has to meet the same strength rules as sign-up.
$error = Validator::checkPassword($new, (string) ($_SESSION['customer_email'] ?? ''), $confirm);

if ($error !== null) {
    $_SESSION['error'] = $error;
    redirect('views/account/change_pass.php');
}

$controller = new CustomerController();
$result     = $controller->changePassword($id, $current, $new);

if ($result['success']) {
    // A new session id after a credential change, same as on login.
    session_regenerate_id(true);

    unset($_SESSION['error']);
    $_SESSION['success'] = 'Your password has been changed.';
    redirect('views/account/my_account.php');
}

$_SESSION['error'] = $result['error'];
redirect('views/account/change_pass.php');
