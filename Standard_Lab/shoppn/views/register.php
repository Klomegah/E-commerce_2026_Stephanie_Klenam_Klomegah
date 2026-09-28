<?php
/**
 * views/register.php - the sign-up form (VIEW layer).
 */

require_once __DIR__ . '/../core/core.php';
// Loaded only for its column-limit constants (CustomerClass::MAX_*),

require_once __DIR__ . '/../classes/CustomerClass.php';

$page_title = 'Register';

// Pull back anything the failed attempt typed, so the form is not wiped.

$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);   

// A short list for the country dropdown. 
$countries = [
    'Ghana', 'Nigeria', 'Kenya', 'South Africa', 'United Kingdom',
    'United States', 'Canada', 'Germany', 'France', 'Other',
];

// Helper so each value attribute echoes the submitted text if present,


$v = function ($key) use ($old) {
    return htmlspecialchars($old[$key] ?? '');
};

require_once __DIR__ . '/layout/header.php';
?>
<div class="container">

    <?php require __DIR__ . '/layout/sidebar.php'; ?>

    <main class="main-content">
        <h1>Create your account</h1>

        <?php if ($error !== null): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>actions/register_action.php"
              method="POST"
              id="register-form"
              novalidate>

            <div class="form-row">
                <label for="customer_name">Full Name</label>


                <input type="text" id="customer_name" name="customer_name"
                       value="<?= $v('customer_name') ?>"
                       maxlength="<?= CustomerClass::MAX_NAME ?>" required>
                <span class="field-error" id="err-customer_name"></span>
            </div>

            <div class="form-row">
                <label for="customer_email">Email</label>
                <input type="email" id="customer_email" name="customer_email"
                       value="<?= $v('customer_email') ?>"
                       maxlength="<?= CustomerClass::MAX_EMAIL ?>" required>
                <span class="field-error" id="err-customer_email"></span>
            </div>

            <div class="form-row">
                <label for="customer_pass">Password</label>
                <input type="password" id="customer_pass" name="customer_pass"
                       minlength="6" required>
                <span class="field-error" id="err-customer_pass"></span>
            </div>

            <div class="form-row">
                <label for="customer_country">Country</label>
                <select id="customer_country" name="customer_country" required>
                    <option value="">-- Select a country --</option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?= htmlspecialchars($country) ?>"
                            <?= (($old['customer_country'] ?? '') === $country) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($country) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="field-error" id="err-customer_country"></span>
            </div>

            <div class="form-row">
                <label for="customer_city">City</label>
                <input type="text" id="customer_city" name="customer_city"
                       value="<?= $v('customer_city') ?>"
                       maxlength="<?= CustomerClass::MAX_CITY ?>" required>
                <span class="field-error" id="err-customer_city"></span>
            </div>

            <div class="form-row">
                <label for="customer_contact">Contact Number</label>
                <input type="tel" id="customer_contact" name="customer_contact"
                       value="<?= $v('customer_contact') ?>"
                       maxlength="<?= CustomerClass::MAX_CONTACT ?>" required>
                <span class="field-error" id="err-customer_contact"></span>
            </div>

            <button type="submit" class="btn">Create account</button>
        </form>

        <p class="muted">Already registered? <a href="<?= BASE_URL ?>views/login.php">Log in</a></p>
    </main>

</div>

<?php
require_once __DIR__ . '/layout/footer.php';
