<?php

// Delete the signed-in customer's own account. Password + typed
// confirmation required; hard delete, blocked if orders still reference it.

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

// ---------- 1.

require_login();

// ---------- 2.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('views/account/delete_account.php');
}

// ---------- 3.

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Your session expired. Please try again.';
    redirect('views/account/delete_account.php');
}

// An admin account is not removed from here: it could be the only way back
// into the site. That has to be done deliberately, not from this button.
if (is_admin()) {
    $_SESSION['error'] = 'Administrator accounts cannot be deleted here.';
    redirect('views/account/delete_account.php');
}

$id       = (int) core_get_user_id();
$password = $_POST['customer_pass'] ?? '';
$typed    = trim($_POST['confirm_delete'] ?? '');

if ($password === '') {
    $_SESSION['error'] = 'Please enter your password to confirm.';
    redirect('views/account/delete_account.php');
}

// The typed word guards against an accidental click on a live session.
if (strtoupper($typed) !== 'DELETE') {
    $_SESSION['error'] = 'Please type DELETE in the box to confirm.';
    redirect('views/account/delete_account.php');
}

$controller = new CustomerController();
$result     = $controller->deleteAccount($id, $password);

if ($result['success']) {
    secureLogout();
    $_SESSION['error'] = 'Your account has been deleted. We are sorry to see you go.';
    redirect('views/login.php');
}

$_SESSION['error'] = $result['error'];
redirect('views/account/delete_account.php');
