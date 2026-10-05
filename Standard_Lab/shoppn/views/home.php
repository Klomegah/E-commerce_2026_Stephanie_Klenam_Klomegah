<?php
/* views home.php - the landing page content (VIEW layer). */

if (!function_exists('is_logged_in')) {
    exit('Direct access forbidden.');
}

require_once __DIR__ . '/../classes/ProductClass.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$productController = new ProductController();

$latestProducts = $productController->getLatestProducts(8);
?>
<h1>Shoppn</h1>

<h2>Latest products</h2>

<?php if (empty($latestProducts)): ?>
    <!--
        No products yet. This is the normal state on a fresh install: the
        admin has not added anything through views/admin/product.php yet.
        Saying so plainly is better than showing an empty grid, which just
        looks like something has broken.
    -->
    <p class="muted">No products have been added yet.</p>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($latestProducts as $product): ?>
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

<p>
    <a class="btn" href="<?= BASE_URL ?>views/all_products.php">Browse all products</a>
</p>
