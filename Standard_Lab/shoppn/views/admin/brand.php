<?php

require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

require_admin();

$page_title = 'Manage Brands';

$brandController = new ProductController();


$editId   = null;
$editName = '';

if (isset($_GET['edit_id'])) {
    $maybeId = filter_var($_GET['edit_id'], FILTER_VALIDATE_INT);

    if ($maybeId !== false && $maybeId > 0) {
        $brand = $brandController->getBrandById($maybeId);

        if ($brand !== null) {
            $editId   = (int) $brand['brand_id'];
            $editName = $brand['brand_name'];
        }
    }
}

$isEditMode = ($editId !== null);


// it in the session would bring it back on the next refresh.
$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);


$brands = $brandController->getAllBrands();

require_once __DIR__ . '/../layout/header.php';
?>

<div class="container">

    <?php require __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="main-content">
        <h1>Manage Brands</h1>

        <?php if ($success !== null): ?>
            <div class="notice"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <h2><?= $isEditMode ? 'Edit brand' : 'Add a brand' ?></h2>

        <?php if ($isEditMode): ?>
            <p class="muted">
                Editing brand #<?= (int) $editId ?>.
                <a href="<?= BASE_URL ?>views/admin/brand.php">Cancel and add a new brand instead</a>
            </p>
        <?php endif; ?>

        <!-- One form, two targets. In edit mode it posts the id in a
             hidden field so the update Action knows which row to change. -->
        <form action="<?= BASE_URL ?>actions/<?= $isEditMode ? 'update_brand_action.php' : 'add_brand_action.php' ?>"
              method="POST">

            <?php if ($isEditMode): ?>
                <input type="hidden" name="brand_id" value="<?= (int) $editId ?>">
            <?php endif; ?>

            <div class="form-row">
                <label for="brand_name">Brand name</label>
                <input type="text" id="brand_name" name="brand_name"
                       value="<?= htmlspecialchars($editName) ?>"
                       maxlength="<?= ProductClass::MAX_BRAND_NAME ?>" required>
            </div>

            <button type="submit" class="btn">
                <?= $isEditMode ? 'Update brand' : 'Add brand' ?>
            </button>
        </form>

        <h2>All brands</h2>

        <?php if (empty($brands)): ?>
            <p class="muted">No brands yet. Add the first one above.</p>
        <?php else: ?>
            <table class="detail-table">
                <tr>
                    <th>ID</th>
                    <th>Brand name</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($brands as $brand): ?>
                    <tr>
                        <td><?= (int) $brand['brand_id'] ?></td>
                        <td><?= htmlspecialchars($brand['brand_name']) ?></td>
                        <td>
                            <!-- A link, not a form: clicking it is a GET,
                                 so the View just re-renders with the form
                                 pre-filled. -->
                            <a class="btn"
                               href="<?= BASE_URL ?>views/admin/brand.php?edit_id=<?= (int) $brand['brand_id'] ?>">
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
