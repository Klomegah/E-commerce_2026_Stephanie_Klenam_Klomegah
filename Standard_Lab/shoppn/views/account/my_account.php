<?php
/**
 * views/account/my_account.php - the customer's account page.
 * An ENTRY POINT *and* a PROTECTED page.
 *
 */

require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/CustomerController.php';

require_login();   // <-- before any output

$page_title = 'My Account';

$customerController = new CustomerController();
$customer = $customerController->getCustomerById(core_get_user_id());

require_once __DIR__ . '/../layout/header.php';
?>

<div class="container">

    <?php require __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="main-content">
        <h1>My Account</h1>

        <?php if ($customer): ?>
            <table class="detail-table">
                <tr>
                    <th>Customer ID</th>
                    <td><?= (int) $customer['customer_id'] ?></td>
                </tr>
                <tr>
                    <th>Name</th>
                    <td><?= htmlspecialchars($customer['customer_name']) ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?= htmlspecialchars($customer['customer_email']) ?></td>
                </tr>
                <tr>
                    <th>Country</th>
                    <td><?= htmlspecialchars($customer['customer_country']) ?></td>
                </tr>
                <tr>
                    <th>City</th>
                    <td><?= htmlspecialchars($customer['customer_city']) ?></td>
                </tr>
                <tr>
                    <th>Contact</th>
                    <td><?= htmlspecialchars($customer['customer_contact']) ?></td>
                </tr>
                <tr>
                    <th>Account type</th>
                    <td><?= is_admin() ? 'Administrator' : 'Customer' ?></td>
                </tr>
            </table>

            <p>
                <a class="btn" href="<?= BASE_URL ?>views/account/edit_account.php">Edit details</a>
                <a class="btn" href="<?= BASE_URL ?>logout.php">Logout</a>
            </p>
        <?php else: ?>
            <div class="alert alert-error">Could not load your account details.</div>
        <?php endif; ?>
    </main>


</div>

<?php
require_once __DIR__ . '/../layout/footer.php';
