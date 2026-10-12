<?php
/**
 * views/account/change_pass.php - change the signed-in customer's password.
 */

require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../core/validation.php';

require_login();

$page_title = 'Change Password';

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$passwordRules = Validator::passwordRules();

require_once __DIR__ . '/../layout/header.php';
?>

<div class="container">

    <?php require __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="main-content">
        <h1>Change password</h1>

        <?php if ($error !== null): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>actions/change_password_action.php" method="POST">
            <?= csrf_field() ?>

            <div class="form-row">
                <label for="customer_pass_current">Current password</label>
                <input type="password" id="customer_pass_current" name="customer_pass_current"
                       autocomplete="current-password" required>
            </div>

            <div class="form-row">
                <label for="customer_pass">New password</label>
                <input type="password" id="customer_pass" name="customer_pass"
                       minlength="<?= Validator::PASS_MIN ?>"
                       maxlength="<?= Validator::PASS_MAX ?>"
                       autocomplete="new-password"
                       aria-describedby="password-hint" required>

                <p id="password-hint" class="hint">
                    Must contain:
                    <?php
                    $labels = array_map(
                        function ($rule) {
                            return lcfirst($rule['label']);
                        },
                        $passwordRules
                    );
                    echo htmlspecialchars(implode('; ', $labels)) . '.';
                    ?>
                </p>
            </div>

            <div class="form-row">
                <label for="customer_pass_confirm">Confirm new password</label>
                <input type="password" id="customer_pass_confirm" name="customer_pass_confirm"
                       maxlength="<?= Validator::PASS_MAX ?>"
                       autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn">Change password</button>
            <a class="btn" href="<?= BASE_URL ?>views/account/my_account.php">Cancel</a>
        </form>
    </main>

</div>

<?php
require_once __DIR__ . '/../layout/footer.php';
