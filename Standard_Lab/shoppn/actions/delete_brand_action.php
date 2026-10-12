<?php

// Delete one brand (admin only, POST only, CSRF-checked).

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

// ---------- 1.

require_admin();

// ---------- 2.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('views/admin/brand.php');
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Your session expired. Please try again.';
    redirect('views/admin/brand.php');
}

$id = filter_var($_POST['brand_id'] ?? null, FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    $_SESSION['error'] = 'Invalid brand id.';
    redirect('views/admin/brand.php');
}

$controller = new ProductController();
$result     = $controller->deleteBrand($id);

if ($result['success']) {
    unset($_SESSION['error']);
    $_SESSION['success'] = 'Brand deleted.';
    redirect('views/admin/brand.php');
}

$_SESSION['error'] = $result['error'];
redirect('views/admin/brand.php');
