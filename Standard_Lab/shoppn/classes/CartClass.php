<?php

/* classes/CartClass.php - MODEL layer for the shopping cart (Tasks 11 + 12).

   A cart row belongs to an IP ADDRESS, not to an account: `ip_add` is what
   identifies "this visitor's basket". Two consequences worth remembering:

   1. A guest can fill a cart without ever registering - no login required.
   2. When a guest registers or logs in, their IP does not change, so the
      very same rows are found again on the next page load. The guest cart
      "carries over" for free; there is no merge step to write.

   Every query below is a prepared statement. Nothing here builds SQL out of
   user input, so no cart query can ever be used for SQL injection. */

require_once __DIR__ . '/../core/db_class.php';

class CartClass extends Database
{
/* MySQL errno 1062 = "duplicate key". It only ever fires if the optional
   UNIQUE index on (p_id, ip_add) described in the notes is installed.
   Without the index, the PHP-side isInCart() check below is the only
   guard; with it, the database is a second line of defence. */
    const ERR_DUPLICATE_KEY = 1062;

/* A sane ceiling on one line item. The number input in cart.php has a
   matching max attribute, but that is only a browser hint - the real
   check happens in CartController before anything reaches this class. */
    const MAX_QTY = 100;

/* Set by addToCart() when the INSERT is rejected by that UNIQUE index,
   so the Controller can turn a raw SQL failure into a readable sentence. */
    public $lastError = null;

/**
 * Is this product already in this visitor's cart?
 *
 * The add action calls this BEFORE inserting, so clicking "Add to Cart"
 * five times on the same product produces five clicks but ONE cart row.
 * SELECT ... LIMIT 1 + store_result() is the cheapest way to ask a yes/no
 * question of MySQL: no rows are transferred, only the count.
 *
 * @param  int  $product_id
 * @param  string $ip
 * @return bool true when a row for this product + IP already exists
 */
    public function isInCart($product_id, $ip)
    {
        $stmt = $this->conn->prepare(
            // p_id, not cart_id: the course schema's cart table has no
            // cart_id column at all (it is keyed only by indexes), and
            // p_id exists in every version of the table.
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
 *
 * $customer_id is part of the required signature but is deliberately NOT
 * written: the course schema gives cart an OPTIONAL `c_id` column, yet
 * the spec's INSERT names only (p_id, ip_add, qty) - so c_id stays NULL
 * and every query keeps keying on ip_add (see the file header). It is
 * accepted so the call matches the specification, and so actually
 * recording it one day needs one edit here, not a new signature
 * everywhere.
 *
 * @param int      $product_id
 * @param int      $qty        quantity for the first add (the link adds 1)
 * @param string   $ip         the visitor's IP, from get_ip()
 * @param int|null $customer_id logged-in customer id, or null for a guest
 * @return bool    true on success
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
 * Every cart row for this visitor, joined with the product details.
 *
 * The JOIN (not the PHP side) is what pairs each row with its title,
 * price and image - one query instead of one query per product. INNER
 * JOIN is correct here: a cart row whose product was deleted from the
 * catalogue cannot be bought or displayed, so it is simply not returned.
 *
 * @param  string $ip
 * @return array  list of associative rows
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
 * How many rows are in this visitor's cart - the number the header shows.
 *
 * COUNT(*) is done inside MySQL rather than counting an array in PHP:
 * the database never sends the rows over, it just sends the answer.
 * bind_result() + fetch() reads that single scalar without building a
 * result set at all.
 *
 * @param  string $ip
 * @return int
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
 * Sum of (qty * unit price) across the visitor's cart.
 *
 * As specified, this iterates the cart rows and adds the line totals up
 * in PHP. It reuses getCartItems(), so the header bar and the cart page
 * total can never disagree with what the table displays - one source of
 * truth. (A pure SQL "SELECT SUM(qty * product_price)" would be one
 * round trip instead of one; the trade-off is discussed in
 * CART_MANAGEMENT_EXPLAINED.md.)
 *
 * Prices are cast to float on the way through: product_price arrives as
 * a string from mysqli, and "2" + "3" is fine but "2.50" + string maths
 * is where PHP's type juggling bites.
 *
 * @param  string $ip
 * @return float
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
 *
 * Both columns are in the WHERE clause on purpose: p_id alone would
 * delete the product from EVERY visitor's cart, and ip_add alone would
 * empty the whole basket. scoping to (p_id, ip_add) removes exactly one
 * row - the one this visitor can see.
 *
 * @return bool true on success (including "already gone")
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
 *
 * new_qty >= 1 is enforced here as well as in the Controller: a quantity
 * of 0 means "remove me", which is what the Remove button is for, and a
 * negative quantity would REDUCE the bill - a one-line path to paying
 * less than the goods cost. Defence in depth: the check exists at every
 * layer that could be bypassed.
 *
 * @return bool true on success
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
 * @param mysqli_stmt $stmt already prepared and bound
 * @return bool true on success
 *
 * The twin of ProductClass::runWrite(): execute once, translate the
 * duplicate-key errno into lastError, log anything unexpected instead of
 * leaking SQL details to the visitor, and always close the statement.
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
