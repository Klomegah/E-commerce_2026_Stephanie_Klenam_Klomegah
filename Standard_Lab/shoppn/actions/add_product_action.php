<?php

// Accept only POST requests from administrators.

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../classes/ProductClass.php';

// ---------- 1.

require_admin();

// ---------- 2.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('views/admin/product.php');
}


// Strip the tags, trim the ends, and keep what is left in the session.
$cat      = $_POST['product_cat']    ?? '';
$brand    = $_POST['product_brand']  ?? '';
$title    = trim(strip_tags($_POST['product_title']    ?? ''));
$price    = trim($_POST['product_price'] ?? '');
$desc     = trim(strip_tags($_POST['product_desc']    ?? ''));
$keywords = trim(strip_tags($_POST['product_keywords'] ?? ''));

$_SESSION['old'] = [
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

// ---------- 4.

if (!isset($_FILES['product_image'])) {
    product_form_error('Please choose a product image.');
}

$file = $_FILES['product_image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    product_form_error('The image could not be uploaded (error code ' . (int) $file['error'] . ').');
}


// PHP has already rejected anything larger than upload_max_filesize,.
if ($file['size'] <= 0 || $file['size'] > ProductClass::MAX_IMAGE_BYTES) {
    product_form_error(
        'Image must be smaller than ' . (ProductClass::MAX_IMAGE_BYTES / 1048576) . ' MB.'
    );
}


$allowed = ProductClass::ALLOWED_IMAGE_TYPES;

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

$extension = $extensions[$realType];


// uniqid() makes the name unique, so a second upload of "shoe.jpg".
// stored - the uploader never chooses where the file goes, and never.
$filename = uniqid() . '.' . $extension;

$destination = '../' . ProductClass::IMAGE_DIR . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    error_log('Product image upload failed: ' . $file['tmp_name'] . ' -> ' . $destination);
    product_form_error('The image could not be saved. Please try again.');
}


$controller = new ProductController();

$result = $controller->addProduct($cat, $brand, $title, $price, $desc, $filename, $keywords);

if ($result['success']) {
    unset($_SESSION['error'], $_SESSION['old']);
    $_SESSION['success'] = 'Product added.';
    redirect('views/admin/product.php');
}

@unlink($destination);

$_SESSION['error'] = $result['error'];
redirect('views/admin/product.php');
