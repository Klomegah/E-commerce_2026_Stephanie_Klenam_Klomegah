<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$page_title = 'Search results';

$products = [];
$error    = null;
$term     = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($term !== '') {
    $result = (new ProductController())->search($term);

    if ($result['success']) {
        $products = $result['products'];
    } else {
        $error = $result['error'];
    }
}

require_once __DIR__ . '/layout/header.php';
?>
<div class="container">

    <?php require __DIR__ . '/layout/sidebar.php'; ?>

    <main class="main-content">
        <h1>Search</h1>

        <?php if ($term === ''): ?>
            <p class="muted">Type something into the search box above.</p>

        <?php elseif ($error !== null): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>

        <?php elseif (empty($products)): ?>
            <p class="muted">No products matched &ldquo;<?= htmlspecialchars($term) ?>&rdquo;.</p>

        <?php else: ?>
            <p class="muted">
                <?= count($products) ?> result(s) for
                &ldquo;<?= htmlspecialchars($term) ?>&rdquo;.
            </p>

            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <h4><?= htmlspecialchars($product['product_title']) ?></h4>
                        <div class="meta">
                            <?= htmlspecialchars($product['cat_name'] ?? 'Uncategorised') ?>
                            &middot;
                            <?= htmlspecialchars($product['brand_name'] ?? 'No brand') ?>
                        </div>
                        <div class="price">
                            GH&cent; <?= number_format((float) $product['product_price'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

</div>

<?php
require_once __DIR__ . '/layout/footer.php';
