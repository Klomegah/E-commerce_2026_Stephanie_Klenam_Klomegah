<?php
/**
 * views/account/edit_account.php - edit the signed-in customer's details.
 *
 * PROTECTED page: require_login() runs before any output.
 */

require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../core/validation.php';
require_once __DIR__ . '/../../controllers/CustomerController.php';

require_login();

$page_title = 'Edit Account';

$controller = new CustomerController();
$customer   = $controller->getCustomerById(core_get_user_id());

// No account to edit - send them back rather than half-render a form.
if (!$customer) {
    $_SESSION['error'] = 'Could not load your account details.';
    redirect('views/account/my_account.php');
}

$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

// Fall back to the stored value unless the Action handed back a rejected edit.
$value = function ($key) use ($old, $customer) {
    return htmlspecialchars($old[$key] ?? $customer[$key] ?? '');
};

$countries = Validator::countries();
$selectedCountry = $old['customer_country'] ?? $customer['customer_country'];

require_once __DIR__ . '/../layout/header.php';
?>

<div class="container">

    <?php require __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="main-content">
        <h1>Edit details</h1>

        <?php if ($error !== null): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>actions/update_account_action.php" method="POST">

            <!-- One-time token: a form forged on another site cannot know it. -->
            <?= csrf_field() ?>

            <div class="form-row">
                <label for="customer_name">Full Name</label>
                <input type="text" id="customer_name" name="customer_name"
                       value="<?= $value('customer_name') ?>"
                       maxlength="<?= CustomerClass::MAX_NAME ?>"
                       autocomplete="name" required>
            </div>

            <div class="form-row">
                <label for="customer_email">Email</label>
                <input type="email" id="customer_email" name="customer_email"
                       value="<?= $value('customer_email') ?>"
                       maxlength="<?= CustomerClass::MAX_EMAIL ?>"
                       autocomplete="email" required>
            </div>

            <div class="form-row">
                <label for="customer_country">Country</label>
                <select id="customer_country" name="customer_country" required>
                    <option value="">-- Select a country --</option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?= htmlspecialchars($country) ?>"
                            <?= ($selectedCountry === $country) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($country) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <label for="customer_city">City</label>
                <input type="text" id="customer_city" name="customer_city"
                       value="<?= $value('customer_city') ?>"
                       maxlength="<?= CustomerClass::MAX_CITY ?>"
                       autocomplete="address-level2" required>
            </div>

            <div class="form-row">
                <label for="customer_contact">Contact Number</label>
                <input type="tel" id="customer_contact" name="customer_contact"
                       value="<?= $value('customer_contact') ?>"
                       maxlength="<?= CustomerClass::MAX_CONTACT ?>"
                       autocomplete="tel" inputmode="tel"
                       placeholder="+233 24 123 4567" required>
                <p class="hint"><?= htmlspecialchars(Validator::phoneHint($selectedCountry)) ?></p>
            </div>

            <button type="submit" class="btn">Save changes</button>
            <a class="btn" href="<?= BASE_URL ?>views/account/my_account.php">Cancel</a>
        </form>
    </main>

</div>

<?php
require_once __DIR__ . '/../layout/footer.php';
