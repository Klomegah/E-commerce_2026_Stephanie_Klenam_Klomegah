<?php

/* controllers/CartController.php - cart controller. */

require_once __DIR__ . '/../classes/CartClass.php';

class CartController
{
/* One shared ceiling, used by addToCart() and updateQty() alike. */
    const MAX_QTY = CartClass::MAX_QTY;

    private $cart;

    public function __construct()
    {
        $this->cart = new CartClass();
    }

/* ------------------------------------------------------------------ *
 *  Reads -  the value itself is returned.
 * ------------------------------------------------------------------ */

    public function isInCart($product_id, $ip)
    {
        $product_id = self::asPositiveInt($product_id);

        if ($product_id === null || !$this->validIp($ip)) {
            return false;
        }

        return $this->cart->isInCart($product_id, $ip);
    }

    public function getCartItems($ip)
    {
        if (!$this->validIp($ip)) {
            return [];
        }

        return $this->cart->getCartItems($ip);
    }

    public function getCartCount($ip)
    {
        if (!$this->validIp($ip)) {
            return 0;
        }

        return $this->cart->getCartCount($ip);
    }

    public function getCartTotal($ip)
    {
        if (!$this->validIp($ip)) {
            return 0.0;
        }

        return $this->cart->getCartTotal($ip);
    }

/**
 * Return the invoice for one order.
 */
    public function getOrderById($order_id)
    {
        $order_id = self::asPositiveInt($order_id);

        if ($order_id === null) {
            return null;
        }

        return $this->cart->getOrderById($order_id);
    }

/**
 * Return the signed-in customer's order history.
 */
    public function getOrdersByCustomer($customer_id)
    {
        $customer_id = self::asPositiveInt($customer_id);

        if ($customer_id === null) {
            return [];
        }

        return $this->cart->getOrdersByCustomer($customer_id);
    }

/* ------------------------------------------------------------------ *
 *  Writes - validation happens here, executed by the Model.
 * ------------------------------------------------------------------ */

/**
 * Add one unit of a product to this visitor's cart.
 */
    public function addToCart($product_id, $qty, $ip, $customer_id = null)
    {
        $product_id = self::asPositiveInt($product_id);
        $qty        = self::asPositiveInt($qty);

        if ($product_id === null) {
            return ['success' => false, 'error' => 'Invalid product.'];
        }

        if ($qty === null || $qty > self::MAX_QTY) {
            return [
                'success' => false,
                'error'   => 'Quantity must be between 1 and ' . self::MAX_QTY . '.',
            ];
        }

        if (!$this->validIp($ip)) {
            return ['success' => false, 'error' => 'Could not identify your session. Please try again.'];
        }

        if (!$this->cart->addToCart($product_id, $qty, $ip, $customer_id)) {
            return [
                'success' => false,
                'error'   => $this->writeError('Could not add the item to your cart. Please try again.'),
            ];
        }

        return ['success' => true];
    }

/**
 * Remove one line from this visitor's cart.
 */
    public function removeFromCart($product_id, $ip)
    {
        $product_id = self::asPositiveInt($product_id);

        if ($product_id === null) {
            return ['success' => false, 'error' => 'Invalid product.'];
        }

        if (!$this->validIp($ip)) {
            return ['success' => false, 'error' => 'Could not identify your session. Please try again.'];
        }

        if (!$this->cart->removeFromCart($product_id, $ip)) {
            return ['success' => false, 'error' => 'Could not remove the item. Please try again.'];
        }

        return ['success' => true];
    }

/**
 * Change the quantity of one line in this visitor's cart.
 */
    public function updateQty($product_id, $new_qty, $ip)
    {
        $product_id = self::asPositiveInt($product_id);
        $new_qty    = self::asPositiveInt($new_qty);

        if ($product_id === null) {
            return ['success' => false, 'error' => 'Invalid product.'];
        }

        // >= 1 is also checked inside CartClass::updateQty(); repeating it
        // here means the visitor gets a sentence rather than silence.
        if ($new_qty === null || $new_qty > self::MAX_QTY) {
            return [
                'success' => false,
                'error'   => 'Quantity must be between 1 and ' . self::MAX_QTY . '.',
            ];
        }

        if (!$this->validIp($ip)) {
            return ['success' => false, 'error' => 'Could not identify your session. Please try again.'];
        }

        if (!$this->cart->updateQty($product_id, $new_qty, $ip)) {
            return ['success' => false, 'error' => 'Could not update the quantity. Please try again.'];
        }

        return ['success' => true];
    }

/**
 * Record a verified payment as an order.
 */
    public function processPayment($customer_id, $ip, $reference, $amount)
    {
        if (!$this->validIp($ip)) {
            return false;
        }

        if (!is_string($reference) || !preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $reference)) {
            return false;
        }

        if (!is_numeric($amount) || (float) $amount <= 0) {
            return false;
        }

        if ($customer_id !== null) {
            $customer_id = self::asPositiveInt($customer_id);

            if ($customer_id === null) {
                return false;
            }
        }

        return $this->cart->processPayment($customer_id, $ip, $reference, (float) $amount);
    }

/* ------------------------------------------------------------------ *
 *  Shared helpers.
 * ------------------------------------------------------------------ */

/**
 * Return a positive integer, or null for anything else.
 */
    public static function asPositiveInt($value)
    {
        if (is_int($value)) {
            $n = $value;
        } elseif (is_string($value) && preg_match('/^[0-9]{1,9}$/', $value)) {
            $n = (int) $value;
        } else {
            return null;
        }

        return $n >= 1 ? $n : null;
    }

/* get_ip() always returns something, but the guard keeps every cart
   query scoped to a real address instead of an empty string - an empty
   ip_add would match rows that have no IP at all. */
   
    private function validIp($ip)
    {
        return is_string($ip) && $ip !== '';
    }

/* Turn the model's duplicate-key errno into a sentence. */
    private function writeError($fallback)
    {
        if ($this->cart->lastError === 'duplicate') {
            return 'That item is already in your cart.';
        }

        return $fallback;
    }
}
