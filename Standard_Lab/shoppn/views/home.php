<?php
/**
 * views/home.php - the landing page content (VIEW layer).
 *
 * A PARTIAL, included by index.php. It does NOT load core.php -
 * index.php already did. That is the entry-point-vs-partial rule.
 */

if (!function_exists('is_logged_in')) {
    exit('Direct access forbidden.');
}
?>
<h1>Welcome to Shoppn</h1>
<p>Your one-stop online store for everything you need.</p>

<?php if (is_logged_in()): ?>
    <div class="notice">
        Signed in as <strong><?= htmlspecialchars($_SESSION['customer_name'] ?? 'User') ?></strong>.
    </div>
    <p><a class="btn" href="<?= BASE_URL ?>views/account/my_account.php">Go to My Account</a></p>
<?php else: ?>
    <div class="notice">You are browsing as a guest.</div>
    <p>
        <a class="btn" href="<?= BASE_URL ?>views/register.php">Create an account</a>
        <a class="btn" href="<?= BASE_URL ?>views/login.php">Log in</a>
    </p>
<?php endif; ?>
