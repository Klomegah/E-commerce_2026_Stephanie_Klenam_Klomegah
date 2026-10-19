<?php

/* controllers/CartController.php - CONTROLLER layer for the cart.

   Thin on purpose, mirroring ProductController:
     - every public method takes RAW input (usually straight from GET/POST),
       validates it, and only then calls one CartClass method;
     - write methods return ['success' => bool, 'error' => string|null]
       so an Action can turn the failure into a message without ever
       seeing SQL;
     - read methods return the value itself (array / int / float / bool).

   Nothing here knows about headers, redirects or HTML - that is the
   Action's and View's job. Nothing here builds SQL - that is the
   Model's job. */

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
 *  Reads - no shape to agree on, the value itself is returned.
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

/* ------------------------------------------------------------------ *
 *  Writes - validated here, executed by the Model.
 * ------------------------------------------------------------------ */

/**
 * Add one unit of a product to this visitor's cart.
 *
 * @return array ['success' => bool, 'error' => string|null]
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
 *
 * @return array ['success' => bool, 'error' => string|null]
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
 *
 * @return array ['success' => bool, 'error' => string|null]
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

/* ------------------------------------------------------------------ *
 *  Shared helpers.
 * ------------------------------------------------------------------ */

/**
 * Strict "positive integer or nothing" for anything that arrives from a
 * query string or a form field.
 *
 * Deliberately stricter than is_numeric(): is_numeric('3.5') is true and
 * (int) '3.5' silently becomes 3, which would let a crafted form submit
 * a quantity nobody typed. '3.5', '3abc', '', '0', -2 and arrays all
 * return null; '3' and 3 return 3.
 *
 * Public so the Actions can use the one and only version of this rule
 * instead of each re-inventing (and possibly weakening) it.
 *
 * @param  mixed $value
 * @return int|null the value when it is a positive integer, else null
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
