<?php

/**
 * controllers/CustomerController.php - CONTROLLER layer.
 *
 * Sits between the action (which handles the form POST) and the model
 * (which owns the SQL). It orchestrates; it does not query and it does
 * not print.
 */

require_once __DIR__ . '/../classes/CustomerClass.php';


class CustomerController
{

    private $customer;

    public function __construct()
    {
        $this->customer = new CustomerClass();
    }

    /**
     * Register a new customer.
     */

    public function register($data)
    {
        // Step 1: is this email free?
        if ($this->customer->emailExists($data['email'])) {
            return ['success' => false, 'error' => 'Email already registered.'];
        }

        // Step 2: insert. addCustomer() hashes the password itself.
        $ok = $this->customer->addCustomer(
            $data['name'],
            $data['email'],
            $data['pass'],
            $data['country'],
            $data['city'],
            $data['contact']
        );

        if (!$ok) {
            return ['success' => false, 'error' => 'Could not create your account. Please try again.'];
        }

        // Step 3: hand back the id of the row just created

        $newUser = $this->customer->getCustomerByEmail($data['email']);

        return [
            'success' => true,
            'customer' => $newUser,
        ];
    }

    /**
     * Attempt a login.
     */
    public function login($email, $pass)
    {
        $row = $this->customer->login($email, $pass);

        if ($row === false) {
            // One generic message for both "no such email" and "wrong
            // password". Saying which one was wrong would let an
            // attacker discover which emails are registered, which is a privacy violation. 
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        return ['success' => true, 'customer' => $row];
    }

    /**
     * Re-reads a customer's details for the account page.
     * The id comes from the session, not from the URL, so a visitor
     * cannot ask for someone else's account by editing the address bar.
     *
     */
    public function getCustomerById($id)
    {
        return $this->customer->getCustomerById($id);
    }

    /**
     * Save edited profile details.
     */
    public function updateProfile($id, $data)
    {
        // The address must be free for everyone except this own account.
        if ($this->customer->emailExistsExcept($data['email'], $id)) {
            return ['success' => false, 'error' => 'That email is already used by another account.'];
        }

        $ok = $this->customer->updateCustomer(
            $id,
            $data['name'],
            $data['email'],
            $data['country'],
            $data['city'],
            $data['contact']
        );

        if (!$ok) {
            return ['success' => false, 'error' => 'Could not save your details. Please try again.'];
        }

        return ['success' => true];
    }

    /**
     * Change a password, but only after proving the current one.
     * Re-entering the old password stops a stolen session (or an unlocked
     * laptop) from locking the real owner out of their own account.
     */
    public function changePassword($id, $current, $new)
    {
        $row = $this->customer->getCustomerById($id);

        if ($row === null) {
            return ['success' => false, 'error' => 'Your account could not be found.'];
        }

        if (!password_verify($current, $row['customer_pass'])) {
            return ['success' => false, 'error' => 'Your current password is not correct.'];
        }

        $hash = password_hash($new, PASSWORD_BCRYPT);

        if (!$this->customer->updatePassword($id, $hash)) {
            return ['success' => false, 'error' => 'Could not change your password. Please try again.'];
        }

        return ['success' => true];
    }

    /**
     * Delete the signed-in account. Again the password must be re-entered.
     */
    public function deleteAccount($id, $password)
    {
        $row = $this->customer->getCustomerById($id);

        if ($row === null) {
            return ['success' => false, 'error' => 'Your account could not be found.'];
        }

        if (!password_verify($password, $row['customer_pass'])) {
            return ['success' => false, 'error' => 'Your password is not correct.'];
        }

        if (!$this->customer->deleteCustomer($id)) {
            if ($this->customer->lastError === 'in_use') {
                return [
                    'success' => false,
                    'error'   => 'This account has orders on record, so it cannot be deleted. Please contact support.',
                ];
            }

            return ['success' => false, 'error' => 'Could not delete your account. Please try again.'];
        }

        return ['success' => true];
    }
}
