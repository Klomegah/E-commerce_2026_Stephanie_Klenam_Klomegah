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



// Names are prefixed with "side" on purpose: this file is require()d into
// the same scope as the view, so a generic $brand/$categories would
// overwrite the view's own variables.
require_once __DIR__ . '/../../controllers/ProductController.php';

$sideProductController = new ProductController();
$sideCategories = $sideProductController->getCategories();
$sideBrands     = $sideProductController->getBrands();

?>

<aside class="sidebar">
    <section>
        <h3>Categories</h3>
        <?php if (empty($sideCategories)): ?>
            <p class="muted">No categories yet.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($sideCategories as $sideCat): ?>
                    <li>
                        <!-- Home reads ?cat=N and switches to that listing. -->
                        <a href="<?= BASE_URL ?>index.php?cat=<?= (int) $sideCat['cat_id'] ?>">
                            <?= htmlspecialchars($sideCat['cat_name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section>
        <h3>Brands</h3>
        <?php if (empty($sideBrands)): ?>
            <p class="muted">No brands yet.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($sideBrands as $sideBrand): ?>
                    <li>
                        <a href="<?= BASE_URL ?>index.php?brand=<?= (int) $sideBrand['brand_id'] ?>">
                            <?= htmlspecialchars($sideBrand['brand_name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</aside>
