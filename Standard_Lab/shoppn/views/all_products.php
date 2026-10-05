<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/ProductClass.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$page_title = 'All Products';

$productController = new ProductController();


$catId   = $_GET['cat']   ?? null;
$brandId = $_GET['brand'] ?? null;

$hasCatFilter   = ($catId !== null && $catId !== '');
$hasBrandFilter = ($brandId !== null && $brandId !== '');

$badCatId   = $hasCatFilter   && (!is_numeric($catId)   || (int) $catId < 1);
$badBrandId = $hasBrandFilter && (!is_numeric($brandId) || (int) $brandId < 1);


$filterName = '';
$filterKind = '';

if (!$badCatId && $hasCatFilter) {
    $cat = $productController->getCategoryById((int) $catId);

    if ($cat === null) {
        $badCatId = true;
    } else {
        $filterName = $cat['cat_name'];
        $filterKind = 'category';
    }
}

if (!$badBrandId && $hasBrandFilter) {
    $brand = $productController->getBrandById((int) $brandId);

    if ($brand === null) {
        $badBrandId = true;
    } else {
        $filterName = $brand['brand_name'];
        $filterKind = 'brand';
    }
}


$products = [];

if (!$badCatId && !$badBrandId) {
    if ($hasCatFilter) {
        $products = $productController->getProductsByCategory($catId);
    } elseif ($hasBrandFilter) {
        $products = $productController->getProductsByBrand($brandId);
    } else {
        $products = $productController->getLatestProducts(0);
    }
}

$badRequest = $badCatId || $badBrandId;

// heading.
$heading = 'All Products';

if (!$badRequest && $filterName !== '') {
    $heading = $filterKind === 'category' ? 'Category: ' . $filterName
                                         : 'Brand: ' . $filterName;
}

$page_title = $heading;

require_once __DIR__ . '/layout/header.php';
?>

<div class="container">

    <?php require __DIR__ . '/layout/sidebar.php'; ?>

    <main class="main-content">
        <h1><?= htmlspecialchars($heading) ?></h1>

        <?php if ($badRequest): ?>
            <div class="alert alert-error">
                That category or brand no longer exists.
                <a href="<?= BASE_URL ?>views/all_products.php">Show all products</a> instead.
            </div>

        <?php elseif (empty($products)): ?>
            <p class="muted">
                No products here yet.
                <?php if ($filterName !== ''): ?>
                    Try <a href="<?= BASE_URL ?>views/all_products.php">all products</a>.
                <?php endif; ?>
            </p>

        <?php else: ?>
            <p class="muted">
                <?= count($products) ?>
                product<?= count($products) === 1 ? '' : 's' ?>
                <?= $filterName !== '' ? 'in ' . htmlspecialchars(strtolower($filterKind)) : '' ?>.
            </p>

            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php
                    $title = (string) $product['product_title'];
                    $catName   = $product['cat_name']   ?? '';
                    $brandName = $product['brand_name'] ?? '';
                    $image     = (string) ($product['product_image'] ?? '');
                    ?>
                    <div class="product-card">
                        <?php if ($image !== ''): ?>
                            <img class="product-thumb"
                                 src="<?= BASE_URL . ProductClass::IMAGE_DIR . htmlspecialchars($image) ?>"
                                 alt="<?= htmlspecialchars($title) ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="product-thumb product-thumb-empty" aria-hidden="true">No image</div>
                        <?php endif; ?>

                        <h4><?= htmlspecialchars($title) ?></h4>

                        <div class="meta">
                            <?= htmlspecialchars($catName !== '' ? $catName : 'Uncategorised') ?>
                            &middot;
                            <?= htmlspecialchars($brandName !== '' ? $brandName : 'No brand') ?>
                        </div>

                        <div class="price">
                            GH&cent; <?= number_format((float) $product['product_price'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p><a class="btn" href="<?= BASE_URL ?>">Back to home</a></p>
    </main>

</div>

<?php
require_once __DIR__ . '/layout/footer.php';
