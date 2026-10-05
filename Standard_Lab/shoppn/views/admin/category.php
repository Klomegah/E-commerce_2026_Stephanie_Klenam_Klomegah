<?php

require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

require_admin();

$page_title = 'Manage Categories';

$productController = new ProductController();


$editId   = null;
$editName = '';

if (isset($_GET['edit_id'])) {
    $maybeId = filter_var($_GET['edit_id'], FILTER_VALIDATE_INT);

    if ($maybeId !== false && $maybeId > 0) {
        $category = $productController->getCategoryById($maybeId);

        if ($category !== null) {
            $editId   = (int) $category['cat_id'];
            $editName = $category['cat_name'];
        }
    }
}

$isEditMode = ($editId !== null);


$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);


$categories = $productController->getAllCategories();

require_once __DIR__ . '/../layout/header.php';
?>

<div class="container">

    <?php require __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="main-content">
        <h1>Manage Categories</h1>

        <?php if ($success !== null): ?>
            <div class="notice"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <h2><?= $isEditMode ? 'Edit category' : 'Add a category' ?></h2>

        <?php if ($isEditMode): ?>
            <p class="muted">
                Editing category #<?= (int) $editId ?>.
                <a href="<?= BASE_URL ?>views/admin/category.php">Cancel and add a new category instead</a>
            </p>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>actions/<?= $isEditMode ? 'update_category_action.php' : 'add_category_action.php' ?>"
              method="POST">

            <?php if ($isEditMode): ?>
                <input type="hidden" name="cat_id" value="<?= (int) $editId ?>">
            <?php endif; ?>

            <div class="form-row">
                <label for="cat_name">Category name</label>
                <input type="text" id="cat_name" name="cat_name"
                       value="<?= htmlspecialchars($editName) ?>"
                       maxlength="<?= ProductClass::MAX_CATEGORY_NAME ?>" required>
            </div>

            <button type="submit" class="btn">
                <?= $isEditMode ? 'Update category' : 'Add category' ?>
            </button>
        </form>

        <h2>All categories</h2>

        <?php if (empty($categories)): ?>
            <p class="muted">No categories yet. Add the first one above.</p>
        <?php else: ?>
            <table class="detail-table">
                <tr>
                    <th>ID</th>
                    <th>Category name</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= (int) $category['cat_id'] ?></td>
                        <td><?= htmlspecialchars($category['cat_name']) ?></td>
                        <td>
                            <a class="btn"
                               href="<?= BASE_URL ?>views/admin/category.php?edit_id=<?= (int) $category['cat_id'] ?>">
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
