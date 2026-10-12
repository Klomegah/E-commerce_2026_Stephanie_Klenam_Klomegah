<?php

// Delete one product (admin only, POST only, CSRF-checked).

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../classes/ProductClass.php';

// ---------- 1.

require_admin();

// ---------- 2.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('views/admin/product.php');
}

// A form forged on another site has no valid token, so it stops here.
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Your session expired. Please try again.';
    redirect('views/admin/product.php');
}

$id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    $_SESSION['error'] = 'Invalid product id.';
    redirect('views/admin/product.php');
}

$controller = new ProductController();

// Read the row first: once it is deleted the image name is gone with it.
$product = $controller->getProductById($id);

$result = $controller->deleteProduct($id);

if ($result['success']) {
    if ($product !== null && !empty($product['product_image'])) {
        // basename() strips any path, so a tampered value cannot escape the folder.
        @unlink('../' . ProductClass::IMAGE_DIR . basename($product['product_image']));
    }

    unset($_SESSION['error']);
    $_SESSION['success'] = 'Product deleted.';
    redirect('views/admin/product.php');
}

$_SESSION['error'] = $result['error'];
redirect('views/admin/product.php');
