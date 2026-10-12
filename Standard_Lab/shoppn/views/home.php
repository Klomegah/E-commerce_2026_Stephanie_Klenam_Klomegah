<?php
/* views/home.php - the landing page grid.
   Loaded INSIDE index.php, which already prints header + sidebar, so this
   file is content only. It reads ?cat=N / ?brand=N to switch between a
   filtered listing and the random "featured" set. */

if (!function_exists('is_logged_in')) {
    exit('Direct access forbidden.');
}

require_once __DIR__ . '/../classes/ProductClass.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$productController = new ProductController();

$catId   = $_GET['cat']   ?? '';
$brandId = $_GET['brand'] ?? '';

$heading  = 'Featured products';
$products = [];

if ($catId !== '' && is_numeric($catId) && (int) $catId > 0) {
    // One category, or nothing if that id is stale.
    $category = $productController->getCategoryById((int) $catId);
    $products = $productController->getProductsByCategory((int) $catId);
    $heading  = $category ? 'Category: ' . $category['cat_name'] : 'Category';
} elseif ($brandId !== '' && is_numeric($brandId) && (int) $brandId > 0) {
    $brand    = $productController->getBrandById((int) $brandId);
    $products = $productController->getProductsByBrand((int) $brandId);
    $heading  = $brand ? 'Brand: ' . $brand['brand_name'] : 'Brand';
} else {
    $products = $productController->getFeaturedProducts(6);
}

?>

<h2><?= htmlspecialchars($heading) ?></h2>

<?php if (empty($products)): ?>
    <!-- Normal on a fresh shop, and after a filter that matches nothing. -->
    <p class="muted">No products found.</p>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <?php require __DIR__ . '/partials/product_card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<p>
    <a class="btn" href="<?= BASE_URL ?>views/all_products.php">Browse all products</a>
</p>
