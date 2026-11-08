<?php

/* classes/CartClass.php - shopping cart model. */

require_once __DIR__ . '/../core/db_class.php';

class CartClass extends Database
{
/* MySQL errno 1062 means a duplicate key. */

    const ERR_DUPLICATE_KEY = 1062;

/* A sane ceiling on one line item. The number input in cart.php has a
   matching max attribute, but that is only a browser hint - the real
   check happens in CartController before anything reaches this class. */
    const MAX_QTY = 100;

/* Set by addToCart() when the INSERT is rejected by that UNIQUE index,
   so the Controller can turn a raw SQL failure into a readable sentence. */
    public $lastError = null;

/**
 * Check whether a product is already in this visitor's cart.
 */
    public function isInCart($product_id, $ip)
    {
        $stmt = $this->conn->prepare(
            "SELECT p_id
               FROM cart
              WHERE p_id = ?
                AND ip_add = ?
              LIMIT 1"
        );

        $stmt->bind_param('is', $product_id, $ip);
        $stmt->execute();
        $stmt->store_result();

        $found = $stmt->num_rows > 0;

        $stmt->close();

        return $found;
    }

/**
 * Insert one cart row.
 */
    public function addToCart($product_id, $qty, $ip, $customer_id = null)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO cart (p_id, ip_add, qty)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param('isi', $product_id, $ip, $qty);

        return $this->runWrite($stmt);
    }

/**
 * Return all cart rows for this visitor with product details.
 */
    public function getCartItems($ip)
    {
        $stmt = $this->conn->prepare(
            "SELECT cart.*,
                    products.product_title,
                    products.product_price,
                    products.product_image
               FROM cart
               JOIN products ON cart.p_id = products.product_id
              WHERE cart.ip_add = ?
              ORDER BY cart.p_id"
        );

        $stmt->bind_param('s', $ip);
        $stmt->execute();

        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $rows;
    }

/**
 * Count the rows in this visitor's cart.
 */
    public function getCartCount($ip)
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM cart WHERE ip_add = ?"
        );

        $stmt->bind_param('s', $ip);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();

        $stmt->close();

        return (int) $count;
    }

/**
 * Sum (qty * unit price) across this visitor's cart.
 */
    public function getCartTotal($ip)
    {
        $total = 0.0;

        foreach ($this->getCartItems($ip) as $item) {
            $total += (int) $item['qty'] * (float) $item['product_price'];
        }

        return $total;
    }

/**
 * Delete one line from this visitor's cart.
 */
    public function removeFromCart($product_id, $ip)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM cart
              WHERE p_id = ?
                AND ip_add = ?"
        );

        $stmt->bind_param('is', $product_id, $ip);

        return $this->runWrite($stmt);
    }

/**
 * Change the quantity of one line in this visitor's cart.
 */
    public function updateQty($product_id, $new_qty, $ip)
    {
        if (!is_numeric($new_qty) || (int) $new_qty < 1) {
            return false;
        }

        $new_qty = (int) $new_qty;

        $stmt = $this->conn->prepare(
            "UPDATE cart
                SET qty = ?
              WHERE p_id = ?
                AND ip_add = ?"
        );

        $stmt->bind_param('iis', $new_qty, $product_id, $ip);

        return $this->runWrite($stmt);
    }

/**
 * Record a verified payment as an order.
 */
    public function processPayment($customer_id, $ip, $reference, $amount)
    {
        if (!is_string($ip) || $ip === ''
            || !is_string($reference) || $reference === ''
            || !is_numeric($amount) || (float) $amount <= 0) {
            return false;
        }

        $customer_id = $customer_id === null ? null : (int) $customer_id;
        $amount      = (float) $amount;

        // 1. Invoice number, per the spec. Six digits keeps it readable
        //    on the invoice; the order_id is the true unique identity.
        $invoice_no = mt_rand(100000, 999999);
        $order_date = date('Y-m-d');
        $created_at = date('Y-m-d H:i:s');

        $this->conn->begin_transaction();

        try {
            // Re-check the cart total inside the transaction.

            $cart_total = $this->getCartTotal($ip);

            if (abs($cart_total - $amount) > 0.01) {
                throw new RuntimeException('Amount does not match the cart total.');
            }

            // 2. The order itself. order_status is set by US, not by the
            //    browser: only this method reaches this line, and only
            //    after server-side verification.
            $stmt = $this->conn->prepare(
                "INSERT INTO orders
                    (customer_id, invoice_no, order_date, order_status, created_at)
                 VALUES (?, ?, ?, 'paid', ?)"
            );
            $stmt->bind_param('iiss', $customer_id, $invoice_no, $order_date, $created_at);

            if (!$stmt->execute()) {
                throw new RuntimeException('Could not insert the order.');
            }

            $order_id = (int) $this->conn->insert_id;
            $stmt->close();

            // 3. Copy every cart row in ONE statement. INSERT ... SELECT
            //    keeps the copy atomic and avoids looping in PHP; cart's
            //    p_id becomes orderdetails.product_id.
            $stmt = $this->conn->prepare(
                "INSERT INTO orderdetails (order_id, product_id, qty)
                 SELECT ?, p_id, qty
                   FROM cart
                  WHERE ip_add = ?"
            );
            $stmt->bind_param('is', $order_id, $ip);

            if (!$stmt->execute()) {
                throw new RuntimeException('Could not copy the cart into order details.');
            }

            $copied = $stmt->affected_rows;
            $stmt->close();

            // An empty cart must never produce a paid order. Throwing here
            // rolls back the order row inserted above.
            if ($copied < 1) {
                throw new RuntimeException('Cart is empty; no order rows to copy.');
            }

            // 4. Empty this visitor's cart. Scoped by ip_add, exactly like
            //    the rest of the cart queries.
            $stmt = $this->conn->prepare(
                "DELETE FROM cart WHERE ip_add = ?"
            );
            $stmt->bind_param('s', $ip);

            if (!$stmt->execute()) {
                throw new RuntimeException('Could not clear the cart.');
            }

            $stmt->close();

            $this->conn->commit();

            // 5. The Action redirects to payment_success.php?order_id=N.
            return $order_id;
        } catch (Throwable $e) {
            $this->conn->rollback();

            // Never leak SQL details to the visitor; the Action shows a
            // generic message and logs the reference for support.
            error_log(
                'Payment processing failed for reference ' . $reference . ': ' . $e->getMessage()
            );

            return false;
        }
    }

/**
 * Return one order with its line items and product names.
 */
    public function getOrderById($order_id)
    {
        $order_id = (int) $order_id;

        if ($order_id < 1) {
            return null;
        }

        $stmt = $this->conn->prepare(
            "SELECT orders.order_id,
                    orders.customer_id,
                    orders.invoice_no,
                    orders.order_date,
                    orders.order_status,
                    orderdetails.product_id,
                    orderdetails.qty,
                    products.product_title,
                    products.product_price
               FROM orders
          LEFT JOIN orderdetails ON orderdetails.order_id = orders.order_id
          LEFT JOIN products     ON products.product_id = orderdetails.product_id
              WHERE orders.order_id = ?
           ORDER BY orderdetails.product_id"
        );

        $stmt->bind_param('i', $order_id);
        $stmt->execute();

        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        if (empty($rows)) {
            return null;
        }

        // Every row repeats the same order columns; take them from the
        // first row once, then collect the product rows underneath.
        $order = [
            'order_id'      => $rows[0]['order_id'],
            'customer_id'   => $rows[0]['customer_id'],
            'invoice_no'    => $rows[0]['invoice_no'],
            'order_date'    => $rows[0]['order_date'],
            'order_status'  => $rows[0]['order_status'],
            'order_details' => [],
        ];

        foreach ($rows as $row) {
            if ($row['product_id'] === null) {
                continue;
            }

            $order['order_details'][] = [
                'product_id'    => $row['product_id'],
                'qty'           => $row['qty'],
                'product_title' => $row['product_title'],
                'product_price' => $row['product_price'],
            ];
        }

        return $order;
    }

/**
 * Return the order history for one customer.
 */
    public function getOrdersByCustomer($customer_id)
    {
        $customer_id = (int) $customer_id;

        if ($customer_id < 1) {
            return [];
        }

        $stmt = $this->conn->prepare(
            "SELECT orders.order_id,
                    orders.invoice_no,
                    orders.order_date,
                    orders.order_status,
                    COALESCE(SUM(orderdetails.qty), 0) AS item_count,
                    COALESCE(SUM(orderdetails.qty * products.product_price), 0) AS order_total
               FROM orders
          LEFT JOIN orderdetails ON orderdetails.order_id = orders.order_id
          LEFT JOIN products     ON products.product_id = orderdetails.product_id
              WHERE orders.customer_id = ?
           GROUP BY orders.order_id,
                    orders.invoice_no,
                    orders.order_date,
                    orders.order_status
           ORDER BY orders.order_date DESC, orders.order_id DESC"
        );

        $stmt->bind_param('i', $customer_id);
        $stmt->execute();

        $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $orders;
    }

/**
 * Execute an already prepared and bound statement.
 */
    private function runWrite($stmt)
    {
        $this->lastError = null;

        try {
            $ok = $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === self::ERR_DUPLICATE_KEY) {
                $this->lastError = 'duplicate';
            } else {
                error_log('Cart write failed: ' . $e->getMessage());
            }

            $stmt->close();

            return false;
        }

        if (!$ok) {
            if ($stmt->errno === self::ERR_DUPLICATE_KEY) {
                $this->lastError = 'duplicate';
            } else {
                error_log('Cart write failed: ' . $stmt->error);
            }

            $stmt->close();

            return false;
        }

        $stmt->close();

        return true;
    }
}
