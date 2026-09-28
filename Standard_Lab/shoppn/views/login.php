<?php
/**
 * views/login.php - the sign-in form (VIEW layer).
 * 
 */

require_once __DIR__ . '/../core/core.php';

// Loaded only for its column-limit constant (CustomerClass::MAX_EMAIL).
require_once __DIR__ . '/../classes/CustomerClass.php';

$page_title = 'Login';

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

require_once __DIR__ . '/layout/header.php';
?>

<div class="container">

    <?php require __DIR__ . '/layout/sidebar.php'; ?>

    <main class="main-content">
        <h1>Log in</h1>

        <?php if ($error !== null): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>actions/login_action.php" method="POST" id="login-form" novalidate>

            <div class="form-row">
                <label for="customer_email">Email</label>
                <input type="email" id="customer_email" name="customer_email"
                       maxlength="<?= CustomerClass::MAX_EMAIL ?>" required>
                <span class="field-error" id="err-customer_email"></span>
            </div>

            <div class="form-row">
                <label for="customer_pass">Password</label>
                <input type="password" id="customer_pass" name="customer_pass" required>
                <span class="field-error" id="err-customer_pass"></span>
            </div>

            <button type="submit" class="btn">Log in</button>
        </form>

        <p class="muted">No account yet? <a href="<?= BASE_URL ?>views/register.php">Register here</a></p>
    </main>

</div>

<?php
require_once __DIR__ . '/layout/footer.php';
