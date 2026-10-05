<?php


require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

// ---------- 1.

require_admin();

// ---------- 2.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('views/admin/brand.php');
}


$name = trim(strip_tags($_POST['brand_name'] ?? ''));

$id = filter_var($_POST['brand_id'] ?? null, FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    $_SESSION['error'] = 'Invalid brand id.';
    redirect('views/admin/brand.php');
}

if ($name === '') {
    $_SESSION['error'] = 'Please enter a brand name.';
    redirect('views/admin/brand.php');
}


$controller = new ProductController();
$result     = $controller->updateBrand($id, $name);

if ($result['success']) {
    unset($_SESSION['error']);
    $_SESSION['success'] = 'Brand updated.';
    redirect('views/admin/brand.php');
}

$_SESSION['error'] = $result['error'];
redirect('views/admin/brand.php');
