<?php
/* views/partials/product_card.php - one product card.
   Expects $product (a row with product_id, product_title, product_price,
   product_image, cat_name, brand_name). Shared by home.php, search and
   any listing, so the card markup only lives in one place. */

$card_id    = (int) $product['product_id'];
$card_title = (string) $product['product_title'];
$card_image = (string) ($product['product_image'] ?? '');
$card_cat   = (string) ($product['cat_name']   ?? '');
$card_brand = (string) ($product['brand_name'] ?? '');
?>
<div class="product-card">
    <?php if ($card_image !== ''): ?>
        <a href="<?= BASE_URL ?>views/single_product.php?pro_id=<?= $card_id ?>">
            <img class="product-thumb"
                 src="<?= BASE_URL . ProductClass::IMAGE_DIR . htmlspecialchars($card_image) ?>"
                 alt="<?= htmlspecialchars($card_title) ?>"
                 loading="lazy">
        </a>
    <?php else: ?>
        <div class="product-thumb product-thumb-empty" aria-hidden="true">No image</div>
    <?php endif; ?>

    <h4>
        <a href="<?= BASE_URL ?>views/single_product.php?pro_id=<?= $card_id ?>">
            <?= htmlspecialchars($card_title) ?>
        </a>
    </h4>

    <div class="meta">
        <?= htmlspecialchars($card_cat !== '' ? $card_cat : 'Uncategorised') ?>
        &middot;
        <?= htmlspecialchars($card_brand !== '' ? $card_brand : 'No brand') ?>
    </div>

    <div class="price">
        GH&cent; <?= number_format((float) $product['product_price'], 2) ?>
    </div>

    <div class="card-actions">
        <a class="btn"
           href="<?= BASE_URL ?>views/single_product.php?pro_id=<?= $card_id ?>">Details</a>
        <!--
            A plain GET link, matching the original add_cart pattern:
            actions/add_to_cart_action.php reads ?add_cart=N, checks the
            product exists, refuses duplicates, then sends the visitor
            back to whatever page this link was clicked from.
        -->
        <?php if (!is_admin()): ?>
        <a class="btn"
           href="<?= BASE_URL ?>actions/add_to_cart_action.php?add_cart=<?= $card_id ?>">Add to Cart</a>
        <?php endif; ?>
    </div>
</div>
