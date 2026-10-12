<?php
/**
 * views/account/delete_account.php - delete the signed-in customer's account.
 *
 * The dangerous page: it warns, then asks for the password AND a typed
 * word, and the form pops a final are-you-sure dialog before posting.
 */

require_once __DIR__ . '/../../core/core.php';

require_login();

$page_title = 'Delete Account';

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

require_once __DIR__ . '/../layout/header.php';
?>

<div class="container">

    <?php require __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="main-content">
        <h1>Delete account</h1>

        <?php if ($error !== null): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="alert alert-error">
            <strong>This cannot be undone.</strong>
            Deleting your account removes your details from Shoppn permanently.
            If you have placed orders, the account cannot be deleted and you
            should contact support instead.
        </div>

        <?php if (is_admin()): ?>

            <p class="muted">
                You are signed in as an administrator. Administrator accounts
                cannot be deleted from this page.
            </p>
            <p><a class="btn" href="<?= BASE_URL ?>views/account/my_account.php">Back to my account</a></p>

        <?php else: ?>

            <form action="<?= BASE_URL ?>actions/delete_account_action.php"
                  method="POST"
                  data-confirm="Delete your Shoppn account permanently? This cannot be undone."
                  data-confirm-label="Delete my account">
                <?= csrf_field() ?>

                <div class="form-row">
                    <label for="customer_pass">Your password</label>
                    <input type="password" id="customer_pass" name="customer_pass"
                           autocomplete="current-password" required>
                </div>

                <div class="form-row">
                    <label for="confirm_delete">
                        Type <strong>DELETE</strong> to confirm
                    </label>
                    <input type="text" id="confirm_delete" name="confirm_delete"
                           placeholder="DELETE" autocomplete="off" required>
                </div>

                <button type="submit" class="btn btn-danger">Delete my account</button>
                <a class="btn" href="<?= BASE_URL ?>views/account/my_account.php">Cancel</a>
            </form>

        <?php endif; ?>
    </main>

</div>

<?php
require_once __DIR__ . '/../layout/footer.php';
