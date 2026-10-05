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


if ($name === '') {
    $_SESSION['error'] = 'Please enter a category name.';
    redirect('views/admin/category.php');
}

$controller = new ProductController();
$result     = $controller->addCategory($name);

if ($result['success']) {
    unset($_SESSION['error']);
    $_SESSION['success'] = 'Category added.';
    redirect('views/admin/category.php');
}

$_SESSION['error'] = $result['error'];
redirect('views/admin/category.php');
