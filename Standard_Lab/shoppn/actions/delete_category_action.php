<?php

// Delete one category (admin only, POST only, CSRF-checked).

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

// ---------- 1.

require_admin();

// ---------- 2.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('views/admin/category.php');
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Your session expired. Please try again.';
    redirect('views/admin/category.php');
}

$id = filter_var($_POST['cat_id'] ?? null, FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    $_SESSION['error'] = 'Invalid category id.';
    redirect('views/admin/category.php');
}

$controller = new ProductController();
$result     = $controller->deleteCategory($id);

if ($result['success']) {
    unset($_SESSION['error']);
    $_SESSION['success'] = 'Category deleted.';
    redirect('views/admin/category.php');
}

$_SESSION['error'] = $result['error'];
redirect('views/admin/category.php');
