<?php


require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../classes/ProductClass.php';

// ---------- 1.

require_admin();

// ---------- 2.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('views/admin/product.php');
}


$id       = $_POST['product_id'] ?? '';
$cat      = $_POST['product_cat']    ?? '';
$brand    = $_POST['product_brand']  ?? '';
$title    = trim(strip_tags($_POST['product_title']    ?? ''));
$price    = trim($_POST['product_price'] ?? '');
$desc     = trim(strip_tags($_POST['product_desc']    ?? ''));
$keywords = trim(strip_tags($_POST['product_keywords'] ?? ''));

$previous_image = basename((string) ($_POST['product_image_current'] ?? ''));

$_SESSION['old'] = [
    'product_id'       => $id,
    'product_cat'      => $cat,
    'product_brand'    => $brand,
    'product_title'    => $title,
    'product_price'    => $price,
    'product_desc'     => $desc,
    'product_keywords' => $keywords,
];

function product_form_error($message)
{
    $_SESSION['error'] = $message;
    redirect('views/admin/product.php');
}


$id = filter_var($id, FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    product_form_error('Invalid product id.');
}


$new_upload = isset($_FILES['product_image'])
    && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE;

$allowed   = ProductClass::ALLOWED_IMAGE_TYPES;
$filename  = '';
$destination = '';
$new_type  = false;

if ($new_upload) {
    $file = $_FILES['product_image'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        product_form_error('The image could not be uploaded (error code ' . (int) $file['error'] . ').');
    }

    // The same checks as the add Action, in the same order: upload.
    if ($file['size'] <= 0 || $file['size'] > ProductClass::MAX_IMAGE_BYTES) {
        product_form_error(
            'Image must be smaller than ' . (ProductClass::MAX_IMAGE_BYTES / 1048576) . ' MB.'
        );
    }

    if (!in_array($file['type'], $allowed, true)) {
        product_form_error('Image must be a JPEG, PNG, GIF or WEBP file.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realType = $finfo->file($file['tmp_name']);

    if ($realType === false || !in_array($realType, $allowed, true)) {
        product_form_error('That file is not a valid image.');
    }

    if (getimagesize($file['tmp_name']) === false) {
        product_form_error('That image appears to be damaged or is not a real image.');
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    if (!isset($extensions[$realType])) {
        product_form_error('That image format is not supported.');
    }

    $new_type    = true;
    $filename    = uniqid() . '.' . $extensions[$realType];
    $destination = '../' . ProductClass::IMAGE_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        error_log('Product image upload failed: ' . $file['tmp_name'] . ' -> ' . $destination);
        product_form_error('The image could not be saved. Please try again.');
    }
} else {
    $ext = strtolower(pathinfo($previous_image, PATHINFO_EXTENSION));

    if ($previous_image === '' || !in_array($ext, ProductClass::ALLOWED_IMAGE_EXTENSIONS, true)) {
        product_form_error('Please choose a product image.');
    }

    $filename = $previous_image;
}


$controller = new ProductController();

$result = $controller->updateProduct($id, $cat, $brand, $title, $price, $desc, $filename, $keywords);

if ($result['success']) {
    if ($new_type && $previous_image !== '' && $previous_image !== $filename) {
        @unlink('../' . ProductClass::IMAGE_DIR . $previous_image);
    }

    unset($_SESSION['error'], $_SESSION['old']);
    $_SESSION['success'] = 'Product updated.';
    redirect('views/admin/product.php');
}

if ($new_type && $destination !== '') {
    @unlink($destination);
}

$_SESSION['error'] = $result['error'];
redirect('views/admin/product.php');
