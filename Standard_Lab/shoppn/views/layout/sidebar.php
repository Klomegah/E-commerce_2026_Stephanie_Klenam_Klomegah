<?php
/**
 * views/layout/sidebar.php - categories and brands.
 *
 * Look how far the data travels to reach this HTML:
 *
 *   sidebar.php  ->  ProductController  ->  ProductClass  ->  Database
 *   (View)          (decides/guards)       (owns the SQL)     (connection)
 *
 */



require_once __DIR__ . '/../../controllers/ProductController.php';

$productController = new ProductController();
$categories = $productController->getCategories();
$brands     = $productController->getBrands();

?>

<aside class="sidebar">
    <section>
        <h3>Categories</h3>
        <?php if (empty($categories)): ?>
            <p class="muted">No categories yet.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="<?= BASE_URL ?>views/all_products.php?cat=<?= (int) $cat['cat_id'] ?>">
                            <?= htmlspecialchars($cat['cat_name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section>
        <h3>Brands</h3>
        <?php if (empty($brands)): ?>
            <p class="muted">No brands yet.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($brands as $brand): ?>
                    <li>
                        <a href="<?= BASE_URL ?>views/all_products.php?brand=<?= (int) $brand['brand_id'] ?>">
                            <?= htmlspecialchars($brand['brand_name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</aside>
