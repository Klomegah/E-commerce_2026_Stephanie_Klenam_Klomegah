<?php
/* views/single_product.php - one product's full detail page.
   Local variable names are prefixed so the sidebar/footer (required into
   this same scope) cannot overwrite them. */

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/ProductClass.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$productId = $_GET['pro_id'] ?? '';

// A missing, non-numeric or unknown id is not an error page: send them home.
if ($productId === '' || !is_numeric($productId) || (int) $productId < 1) {
    redirect('index.php');
}

$productController = new ProductController();
$product = $productController->getPublicProductById((int) $productId);

if ($product === null) {
    redirect('index.php');
}

$page_title = (string) $product['product_title'];

$productTitle    = (string) $product['product_title'];
$productImage    = (string) ($product['product_image'] ?? '');
$productCatName  = (string) ($product['cat_name']   ?? '');
$productBrandName = (string) ($product['brand_name'] ?? '');
$productCatId    = (int) ($product['product_cat']   ?? 0);
$productBrandId  = (int) ($product['product_brand'] ?? 0);
$productDesc     = (string) ($product['product_desc']     ?? '');
$productKeywords = (string) ($product['product_keywords'] ?? '');

require_once __DIR__ . '/layout/header.php';
?>

<div class="container">

    <?php require __DIR__ . '/layout/sidebar.php'; ?>

    <main class="main-content">
        <h1><?= htmlspecialchars($productTitle) ?></h1>

        <?php if ($productImage !== ''): ?>
            <img class="product-hero"
                 src="<?= BASE_URL . ProductClass::IMAGE_DIR . htmlspecialchars($productImage) ?>"
                 alt="<?= htmlspecialchars($productTitle) ?>">
        <?php else: ?>
            <div class="product-hero product-thumb-empty" aria-hidden="true">No image</div>
        <?php endif; ?>

        <p class="product-price">GH&cent; <?= number_format((float) $product['product_price'], 2) ?></p>

        <section class="product-description">
            <h2>Description</h2>
            <?php if ($productDesc !== ''): ?>
                <p><?= nl2br(htmlspecialchars($productDesc)) ?></p>
            <?php else: ?>
                <p class="muted">No description has been provided for this product.</p>
            <?php endif; ?>
        </section>

        <!-- Brand and category, placed under the description. Each links to
             its listing. An empty one is left out entirely, so a product
             with no brand does not show a bare "Brand:" line. -->
        <?php if (($productCatName !== '' && $productCatId > 0) || ($productBrandName !== '' && $productBrandId > 0)): ?>
            <p class="product-meta">
                <?php if ($productCatName !== '' && $productCatId > 0): ?>
                    <span class="product-meta-label">Category:</span>
                    <a href="<?= BASE_URL ?>index.php?cat=<?= $productCatId ?>"><?= htmlspecialchars($productCatName) ?></a>
                <?php endif; ?>

                <?php if ($productCatName !== '' && $productCatId > 0 && $productBrandName !== '' && $productBrandId > 0): ?>
                    <span class="product-meta-sep">&middot;</span>
                <?php endif; ?>

                <?php if ($productBrandName !== '' && $productBrandId > 0): ?>
                    <span class="product-meta-label">Brand:</span>
                    <a href="<?= BASE_URL ?>index.php?brand=<?= $productBrandId ?>"><?= htmlspecialchars($productBrandName) ?></a>
                <?php endif; ?>
            </p>
        <?php endif; ?>

        <?php if ($productKeywords !== ''): ?>
            <p class="muted">Keywords: <?= htmlspecialchars($productKeywords) ?></p>
        <?php endif; ?>

        <p>
            <a class="btn"
               href="<?= BASE_URL ?>index.php?add_cart=<?= (int) $product['product_id'] ?>">Add to Cart</a>
            <a class="btn" href="<?= BASE_URL ?>views/all_products.php">Back to products</a>
        </p>
    </main>

</div>

<?php
require_once __DIR__ . '/layout/footer.php';
