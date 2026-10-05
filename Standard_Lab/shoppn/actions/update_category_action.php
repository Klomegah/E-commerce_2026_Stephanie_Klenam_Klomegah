<?php


require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

// ---------- 1.

require_admin();

// ---------- 2.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('views/admin/category.php');
}


$name = trim(strip_tags($_POST['cat_name'] ?? ''));

$id = filter_var($_POST['cat_id'] ?? null, FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    $_SESSION['error'] = 'Invalid category id.';
    redirect('views/admin/category.php');
}

if ($name === '') {
    $_SESSION['error'] = 'Please enter a category name.';
    redirect('views/admin/category.php');
}


$controller = new ProductController();
$result     = $controller->updateCategory($id, $name);

if ($result['success']) {
    unset($_SESSION['error']);
    $_SESSION['success'] = 'Category updated.';
    redirect('views/admin/category.php');
}

$_SESSION['error'] = $result['error'];
redirect('views/admin/category.php');
