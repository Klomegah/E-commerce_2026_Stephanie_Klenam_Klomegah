<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$page_title = 'Search results';

$products = [];
$error    = null;
$term     = isset($_GET['user_query']) ? trim($_GET['user_query']) : '';

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

            <!-- Same card as the home page, so results look consistent. -->
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php require __DIR__ . '/partials/product_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

</div>

<?php
require_once __DIR__ . '/layout/footer.php';
