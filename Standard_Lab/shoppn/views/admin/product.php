<?php

require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

require_admin();

$page_title = 'Manage Products';

$productController = new ProductController();


$editId    = null;
$edit      = null;
$editImage = '';

// $_SESSION['old'] so the admin does not lose their work.

if (isset($_GET['edit_id'])) {
    $maybeId = filter_var($_GET['edit_id'], FILTER_VALIDATE_INT);

    if ($maybeId !== false && $maybeId > 0) {
        $product = $productController->getProductById($maybeId);

        if ($product !== null) {
            $editId    = (int) $product['product_id'];
            $edit      = $product;
            $editImage = (string) $product['product_image'];
        }
    }
}

$isEditMode = ($editId !== null);

// 2.

$old = $_SESSION['old'] ?? [];

function product_field($key, $edit, $old, $default = '')
{
    if ($edit !== null && isset($edit[$key])) {
        return (string) $edit[$key];
    }

    if (isset($old[$key])) {
        return (string) $old[$key];
    }

    return $default;
}

$value_title    = product_field('product_title', $edit, $old);
$value_cat      = product_field('product_cat', $edit, $old);
$value_brand    = product_field('product_brand', $edit, $old);
$value_price    = product_field('product_price', $edit, $old);
$value_desc     = product_field('product_desc', $edit, $old);
$value_keywords = product_field('product_keywords', $edit, $old);


$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error'], $_SESSION['old']);


$categories = $productController->getAllCategories();
$brands     = $productController->getAllBrands();
$products   = $productController->getAllProducts();

require_once __DIR__ . '/../layout/header.php';
?>

<div class="container">

    <?php require __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="main-content">
        <h1>Manage Products</h1>

        <?php if ($success !== null): ?>
            <div class="notice"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <h2><?= $isEditMode ? 'Edit product' : 'Add a product' ?></h2>

        <?php if ($isEditMode): ?>
            <p class="muted">
                Editing product #<?= (int) $editId ?>.
                <a href="<?= BASE_URL ?>views/admin/product.php">Cancel and add a new product instead</a>
            </p>
        <?php endif; ?>

        <?php if (empty($categories) || empty($brands)): ?>
            <div class="alert alert-error">
                A product needs a category and a brand.
                <a href="<?= BASE_URL ?>views/admin/category.php">Add categories</a> and
                <a href="<?= BASE_URL ?>views/admin/brand.php">add brands</a> first.
            </div>
        <?php else: ?>

        <!-- multipart/form-data is what tells the browser to send the
             file as well as the text fields. -->
        <form action="<?= BASE_URL ?>actions/<?= $isEditMode ? 'update_product_action.php' : 'add_product_action.php' ?>"
              method="POST" enctype="multipart/form-data" id="product-form">

            <?php if ($isEditMode): ?>
                <input type="hidden" name="product_id" value="<?= (int) $editId ?>">

                <!-- On edit the image is optional, so the Action needs to
                     know the current file name in order to keep it when no
                     new file is chosen. -->
                <input type="hidden" name="product_image_current"
                       value="<?= htmlspecialchars($editImage) ?>">
            <?php endif; ?>

            <div class="form-row">
                <label for="product_title">Product title</label>
                <input type="text" id="product_title" name="product_title"
                       value="<?= htmlspecialchars($value_title) ?>"
                       maxlength="<?= ProductClass::MAX_TITLE ?>" required>
            </div>

            <div class="form-row">
                <label for="product_cat">Category</label>
                <select id="product_cat" name="product_cat" required>
                    <option value="">-- choose a category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <?php ?>
                        <option value="<?= (int) $cat['cat_id'] ?>"
                            <?= ($value_cat === (string) $cat['cat_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['cat_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <label for="product_brand">Brand</label>
                <select id="product_brand" name="product_brand" required>
                    <option value="">-- choose a brand --</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= (int) $brand['brand_id'] ?>"
                            <?= ($value_brand === (string) $brand['brand_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($brand['brand_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <label for="product_price">Price</label>
                <input type="number" id="product_price" name="product_price"
                       value="<?= htmlspecialchars($value_price) ?>"
                       step="0.01" min="0" required>
            </div>

            <div class="form-row">
                <label for="product_desc">Description</label>
                <textarea id="product_desc" name="product_desc" rows="5" required><?= htmlspecialchars($value_desc) ?></textarea>
            </div>

            <div class="form-row">
                <label for="product_keywords">Keywords</label>
                <input type="text" id="product_keywords" name="product_keywords"
                       value="<?= htmlspecialchars($value_keywords) ?>"
                       maxlength="<?= ProductClass::MAX_KEYWORDS ?>">
                <span class="field-error" id="err-product_keywords"></span>
            </div>

            <div class="form-row">
                <label for="product_image">
                    Image <?= $isEditMode ? '(leave empty to keep the current one)' : '' ?>
                </label>
                <input type="file" id="product_image" name="product_image"
                       accept=".jpg,.jpeg,.png,.gif,.webp" <?= $isEditMode ? '' : 'required' ?>>
                <span class="field-error" id="err-product_image"></span>
                <p class="muted">JPEG, PNG, GIF or WEBP. Up to 2 MB.</p>
            </div>

            <?php if ($isEditMode && $editImage !== ''): ?>
                <div class="form-row">
                    <label>Current image</label>
                    <img class="admin-image-preview"
                         src="<?= BASE_URL . ProductClass::IMAGE_DIR . htmlspecialchars($editImage) ?>"
                         alt="<?= htmlspecialchars($value_title) ?>">
                </div>
            <?php endif; ?>

            <button type="submit" class="btn">
                <?= $isEditMode ? 'Update product' : 'Add product' ?>
            </button>
        </form>

        <?php endif; ?>

        <h2>All products</h2>

        <?php if (empty($products)): ?>
            <p class="muted">No products yet. Add the first one above.</p>
        <?php else: ?>
            <table class="detail-table">
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Price</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($products as $row): ?>
                    <tr>
                        <td><?= (int) $row['product_id'] ?></td>
                        <td>
                            <?php if (!empty($row['product_image'])): ?>
                                <img class="admin-thumb"
                                     src="<?= BASE_URL . ProductClass::IMAGE_DIR . htmlspecialchars($row['product_image']) ?>"
                                     alt="">
                            <?php else: ?>
                                <span class="muted">none</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['product_title']) ?></td>
                        <td><?= htmlspecialchars($row['cat_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['brand_name'] ?? '') ?></td>
                        <td><?= number_format((float) $row['product_price'], 2) ?></td>
                        <td>
                            <a class="btn"
                               href="<?= BASE_URL ?>views/admin/product.php?edit_id=<?= (int) $row['product_id'] ?>">
                                Edit
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </main>

</div>

<?php
require_once __DIR__ . '/../layout/footer.php';
