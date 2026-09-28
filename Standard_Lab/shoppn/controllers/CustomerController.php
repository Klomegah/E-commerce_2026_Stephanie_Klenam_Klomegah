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
}
