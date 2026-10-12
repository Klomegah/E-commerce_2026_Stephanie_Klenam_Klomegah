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

    /* MySQL errno 1451: cannot delete a parent row that a child still
       references (orders / payment keep a RESTRICT foreign key). */
    const ERR_ROW_REFERENCED = 1451;

    /* Set by the delete helpers so the controller can tell WHY a delete
       failed: 'in_use' (blocked by a foreign key) or null (real error). */
    public $lastError = null;

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



    /**
     * Does this email belong to a DIFFERENT account?
     * Used when editing, where keeping your own address must be allowed.
     */
    public function emailExistsExcept($email, $id)
    {
        $stmt = $this->conn->prepare(
            "SELECT customer_id FROM customer
              WHERE customer_email = ? AND customer_id <> ? LIMIT 1"
        );
        $stmt->bind_param('si', $email, $id);
        $stmt->execute();
        $stmt->store_result();

        return $stmt->num_rows > 0;
    }

    /**
     * Save edited profile fields. The email has already been checked by
     * the controller, so the UNIQUE index is a backstop, not the check.
     */
    public function updateCustomer($id, $name, $email, $country, $city, $contact)
    {
        $stmt = $this->conn->prepare(
            "UPDATE customer
                SET customer_name = ?, customer_email = ?, customer_country = ?,
                    customer_city = ?, customer_contact = ?
              WHERE customer_id = ?"
        );
        $stmt->bind_param('sssssi', $name, $email, $country, $city, $contact, $id);

        return $stmt->execute();
    }

    /**
     * Replace the stored password hash. The caller hashes; this only writes.
     */
    public function updatePassword($id, $hash)
    {
        $stmt = $this->conn->prepare(
            "UPDATE customer SET customer_pass = ? WHERE customer_id = ?"
        );
        $stmt->bind_param('si', $hash, $id);

        return $stmt->execute();
    }

    /**
     * Delete an account. Returns false and flags lastError='in_use' when
     * orders or payment rows still point at this customer (FK RESTRICT).
     */
    public function deleteCustomer($id)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM customer WHERE customer_id = ?"
        );
        $stmt->bind_param('i', $id);

        return $this->runDelete($stmt);
    }

    /**
     * Shared DELETE runner. Mirrors the INSERT/UPDATE guard in the product
     * model: catch the exception, read the errno, translate 1451 to 'in_use'.
     */
    private function runDelete($stmt)
    {
        $this->lastError = null;

        try {
            $ok = $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === self::ERR_ROW_REFERENCED) {
                $this->lastError = 'in_use';
            } else {
                error_log('Customer delete failed: ' . $e->getMessage());
            }
            $stmt->close();

            return false;
        }

        if (!$ok) {
            if ($stmt->errno === self::ERR_ROW_REFERENCED) {
                $this->lastError = 'in_use';
            } else {
                error_log('Customer delete failed: ' . $stmt->error);
            }
            $stmt->close();

            return false;
        }

        $stmt->close();

        return true;
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
