<?php

require_once __DIR__ . '/../core/core.php';

require_once __DIR__ . '/../classes/CustomerClass.php';
// Validator is loaded as well so the country list, the password.

require_once __DIR__ . '/../core/validation.php';

$page_title = 'Register';


$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);   


$countries = Validator::countries();

// The checklist shown under the password box.

$passwordRules = Validator::passwordRules();


$selectedCountry = $old['customer_country'] ?? '';


$phoneHints = [];
foreach ($countries as $country) {
    $phoneHints[$country] = Validator::phoneHint($country);
}



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

        <!--
            The validation rules are handed to validate.js as JSON in a data
            attribute, rather than typed into a <script> block. validate.js
            then checks against the very same numbers the server uses, so a
            password or phone number can never be accepted in the browser
            and rejected by the action (or worse, the other way round).
        -->
        <form action="<?= BASE_URL ?>actions/register_action.php"
              method="POST"
              id="register-form"
              data-rules="<?= htmlspecialchars(json_encode([
                  'password' => [
                      'min'      => Validator::PASS_MIN,
                      'max'      => Validator::PASS_MAX,
                      'specials' => Validator::PASS_SPECIALS,
                      'common'   => Validator::PASS_COMMON,
                  ],
                  'phone' => [
                      'maxChars'  => CustomerClass::MAX_CONTACT,
                      'dialPlan'  => Validator::COUNTRY_DIAL_PLAN,
                  ],
                  'hints' => $phoneHints,
              ], JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>"
              novalidate>

            <div class="form-row">
                <label for="customer_name">Full Name</label>


                <input type="text" id="customer_name" name="customer_name"
                       value="<?= $v('customer_name') ?>"
                       maxlength="<?= CustomerClass::MAX_NAME ?>"
                       autocomplete="name" required>
                <span class="field-error" id="err-customer_name"></span>
            </div>

            <div class="form-row">
                <label for="customer_email">Email</label>
                <input type="email" id="customer_email" name="customer_email"
                       value="<?= $v('customer_email') ?>"
                       maxlength="<?= CustomerClass::MAX_EMAIL ?>"
                       autocomplete="email" required>
                <span class="field-error" id="err-customer_email"></span>
            </div>

            <div class="form-row">
                <label for="customer_pass">Password</label>
                <input type="password" id="customer_pass" name="customer_pass"
                       minlength="<?= Validator::PASS_MIN ?>"
                       maxlength="<?= Validator::PASS_MAX ?>"
                       autocomplete="new-password"
                       aria-describedby="password-rules" required>

                <!--
                    The checklist is printed from Validator::passwordRules(),
                    the same array the server validates against, so the
                    shopper is never told "no" about a password that would
                    actually have been accepted.

                    validate.js toggles .met on each <li> as the shopper
                    types, and fills in #password-rules-count.
                -->
                <div class="password-meter" aria-live="polite">
                    <span class="password-meter-row">
                        <span id="password-rules-count"><?= count($passwordRules) ?> rules to meet</span>
                        <span id="password-length-count" class="password-length"></span>
                    </span>
                    <div class="password-meter-track">
                        <div class="password-meter-fill" id="password-meter-fill"></div>
                    </div>
                </div>

                <ul id="password-rules" class="password-rules">
                    <?php foreach ($passwordRules as $rule): ?>
                        <li data-rule="<?= htmlspecialchars($rule['key']) ?>">
                            <span class="tick" aria-hidden="true"></span>
                            <span class="rule-text"><?= htmlspecialchars($rule['label']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <span class="field-error" id="err-customer_pass"></span>
            </div>

            <div class="form-row">
                <label for="customer_pass_confirm">Confirm Password</label>
                <input type="password" id="customer_pass_confirm" name="customer_pass_confirm"
                       maxlength="<?= Validator::PASS_MAX ?>"
                       autocomplete="new-password" required>
                <span class="field-error" id="err-customer_pass_confirm"></span>
            </div>

            <div class="form-row">
                <label for="customer_country">Country</label>
                <select id="customer_country" name="customer_country"
                        aria-describedby="phone-hint" required>
                    <option value="">-- Select a country --</option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?= htmlspecialchars($country) ?>"
                            <?= ($selectedCountry === $country) ? 'selected' : '' ?>>
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
                       maxlength="<?= CustomerClass::MAX_CITY ?>"
                       autocomplete="address-level2" required>
                <span class="field-error" id="err-customer_city"></span>
            </div>

            <div class="form-row">
                <label for="customer_contact">Contact Number</label>
                <input type="tel" id="customer_contact" name="customer_contact"
                       value="<?= $v('customer_contact') ?>"
                       maxlength="<?= CustomerClass::MAX_CONTACT ?>"
                       autocomplete="tel" inputmode="tel"
                       placeholder="+233 24 123 4567"
                       aria-describedby="phone-hint" required>
                <!--
                    Rewritten by validate.js whenever the country changes,
                    so the expected calling code and digit count always match
                    the country actually selected.
                -->
                <p id="phone-hint" class="hint">
                    <?= htmlspecialchars(Validator::phoneHint($selectedCountry)) ?>
                </p>
                <span class="field-error" id="err-customer_contact"></span>
            </div>

            <button type="submit" class="btn">Create account</button>
        </form>

        <p class="muted">Already registered? <a href="<?= BASE_URL ?>views/login.php">Log in</a></p>
    </main>

</div>

<?php
require_once __DIR__ . '/layout/footer.php';
