<?php

/**
 * classes/CustomerClass.php - MODEL layer.
 *
 * Owns every SQL statement that touches the `customer` table.
 *
*/

require_once __DIR__ . '/../core/db_class.php';

class CustomerClass extends Database
{
    const MAX_NAME    = 100;
    const MAX_EMAIL   = 50;
    const MAX_COUNTRY = 30;
    const MAX_CITY    = 30;
    const MAX_CONTACT = 15;
    const ROLE_ADMIN    = 1;
    const ROLE_CUSTOMER = 2;

    /**
     * Does this email already belong to an account?
     */
    public function emailExists($email)
    {
        $stmt = $this->conn->prepare(
            "SELECT customer_email FROM customer WHERE customer_email = ? LIMIT 1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        // num_rows > 0 means a row came back, i.e. the email is taken.
        return $stmt->num_rows > 0;
    }

    /**
     * Insert a new customer.
    
     */
    public function addCustomer($name, $email, $pass, $country, $city, $contact)
    {
        
        $hash = password_hash($pass, PASSWORD_BCRYPT);

        $role = self::ROLE_CUSTOMER;

        $stmt = $this->conn->prepare(
            "INSERT INTO customer
                (customer_name, customer_email, customer_pass,
                 customer_country, customer_city, customer_contact, user_role)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

       
        $stmt->bind_param(
            'ssssssi',
            $name,
            $email,
            $hash,
            $country,
            $city,
            $contact,
            $role
        );

        return $stmt->execute();
    }

  
    public function getCustomerByEmail($email)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM customer WHERE customer_email = ? LIMIT 1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();

        // fetch_assoc() returns null (not false) when nothing matched.
        return $row ? $row : null;
    }

 

    public function getCustomerById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM customer WHERE customer_id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();

        return $row ? $row : null;
    }



    public function login($email, $pass)
    {
        $row = $this->getCustomerByEmail($email);

        if ($row === null) {
            return false;
        }

        if (!password_verify($pass, $row['customer_pass'])) {
            return false;
        }

        return $row;
    }
}
