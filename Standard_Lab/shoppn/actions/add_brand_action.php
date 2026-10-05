<?php

// Accept only POST requests from administrators.

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

// ---------- 1.

require_admin();

// ---------- 2.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('views/admin/brand.php');
}


// HTML.
$name = trim(strip_tags($_POST['brand_name'] ?? ''));


if ($name === '') {
    $_SESSION['error'] = 'Please enter a brand name.';
    redirect('views/admin/brand.php');
}

$controller = new ProductController();
$result     = $controller->addBrand($name);

if ($result['success']) {
    // refresh, so redirect after every one.
    unset($_SESSION['error']);
    $_SESSION['success'] = 'Brand added.';
    redirect('views/admin/brand.php');
}

$_SESSION['error'] = $result['error'];
redirect('views/admin/brand.php');
