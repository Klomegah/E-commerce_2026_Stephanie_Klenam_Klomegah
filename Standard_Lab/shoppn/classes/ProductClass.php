<?php

/* classes ProductClass.php - MODEL layer. */

require_once __DIR__ . '/../core/db_class.php';

class ProductClass extends Database
{
/* Set by addProduct() updateProduct() when the write is rejected because of the UNIQUE index on product_title. */
    public $lastError = null;

/* MySQL's error number for "duplicate key value violates a unique constraint". */
    const ERR_DUPLICATE_KEY = 1062;

    const MAX_BRAND_NAME    = 100;
    const MAX_CATEGORY_NAME = 100;
    const MAX_TITLE         = 200;
    const MAX_DESC          = 500;
    const MAX_KEYWORDS      = 100;

    const MAX_IMAGE_BYTES = 2097152;

    // The only image formats the admin form is allowed to upload.
    const ALLOWED_IMAGE_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    // Where uploaded product images are stored, relative to the.
    const IMAGE_DIR = 'images/products/';


/**
 * @param  string $name already sanitised by the Action
 * @return bool   true on success
 */
    public function addBrand($name)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO brands (brand_name) VALUES (?)"
        );

        $stmt->bind_param('s', $name);

        return $stmt->execute();
    }

    public function getAllBrands()
    {
        $result = $this->conn->query(
            "SELECT * FROM brands ORDER BY brand_name ASC"
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getBrandById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM brands WHERE brand_id = ? LIMIT 1"
        );

        $stmt->bind_param('i', $id);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();

        return $row ? $row : null;
    }

    public function updateBrand($id, $name)
    {
        $stmt = $this->conn->prepare(
            "UPDATE brands SET brand_name = ? WHERE brand_id = ?"
        );

        $stmt->bind_param('si', $name, $id);

        return $stmt->execute();
    }


    public function addCategory($name)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO categories (cat_name) VALUES (?)"
        );

        $stmt->bind_param('s', $name);

        return $stmt->execute();
    }


    public function getAllCategories()
    {
        $result = $this->conn->query(
            "SELECT * FROM categories ORDER BY cat_name ASC"
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

   

    public function getCategoryById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM categories WHERE cat_id = ? LIMIT 1"
        );

        $stmt->bind_param('i', $id);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();

        return $row ? $row : null;
    }


       public function updateCategory($id, $name)
    {
        $stmt = $this->conn->prepare(
            "UPDATE categories SET cat_name = ? WHERE cat_id = ?"
        );

        $stmt->bind_param('si', $name, $id);

        return $stmt->execute();
    }


   

    public function getIdByTitle($title, $excludeId = 0)
    {
        $stmt = $this->conn->prepare(
            'SELECT product_id
               FROM products
              WHERE product_title = ?
                AND product_id <> ?
              LIMIT 1'
        );

        $stmt->bind_param('si', $title, $excludeId);
        $stmt->execute();
        $stmt->store_result();


        if ($stmt->num_rows === 0) {
            $stmt->close();

            return null;
        }

        $stmt->bind_result($id);
        $stmt->fetch();
        $id = (int) $id;
        $stmt->close();

        return $id;
    }

/**
 * @param mysqli_stmt $stmt already prepared and bound
 * @return bool true on success
 */
    private function runWrite($stmt)
    {
        $this->lastError = null;

        try {
            $ok = $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === self::ERR_DUPLICATE_KEY) {
                $this->lastError = 'duplicate_title';
            } else {
                error_log('Product write failed: ' . $e->getMessage());
            }

            $stmt->close();

            return false;
        }

        if (!$ok) {
            if ($stmt->errno === self::ERR_DUPLICATE_KEY) {
                $this->lastError = 'duplicate_title';
            } else {
                error_log('Product write failed: ' . $stmt->error);
            }

            $stmt->close();

            return false;
        }

        $stmt->close();

        return true;
    }

    public function addProduct($cat, $brand, $title, $price, $desc, $image_filename, $keywords)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO products
                (product_cat, product_brand, product_title,
                 product_price, product_desc, product_image, product_keywords)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );


        $stmt->bind_param(
            'sisdsss',
            $cat,
            $brand,
            $title,
            $price,
            $desc,
            $image_filename,
            $keywords
        );

        return $this->runWrite($stmt);
    }

    public function updateProduct($id, $cat, $brand, $title, $price, $desc, $image_filename, $keywords)
    {
        $stmt = $this->conn->prepare(
            "UPDATE products
             SET product_cat      = ?,
                 product_brand    = ?,
                 product_title    = ?,
                 product_price    = ?,
                 product_desc     = ?,
                 product_image    = ?,
                 product_keywords = ?
             WHERE product_id = ?"
        );


        $stmt->bind_param(
            'sisdsssi',
            $cat,
            $brand,
            $title,
            $price,
            $desc,
            $image_filename,
            $keywords,
            $id
        );

        return $this->runWrite($stmt);
    }

    public function getAllProducts()
    {
        return $this->getLatestProducts(0);
    }

    public function getProductById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT p.*,
                    c.cat_name,
                    b.brand_name
             FROM products p
             LEFT JOIN categories c ON p.product_cat   = c.cat_id
             LEFT JOIN brands     b ON p.product_brand = b.brand_id
             WHERE p.product_id = ?
             LIMIT 1"
        );

        $stmt->bind_param('i', $id);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();

        return $row ? $row : null;
    }


    public function getCategories()
    {
        return $this->getAllCategories();
    }

    public function getBrands()
    {
        return $this->getAllBrands();
    }

    public function searchProducts($term)
    {
        $like = '%' . $term . '%';

        $stmt = $this->conn->prepare(
            "SELECT p.product_id,
                    p.product_title,
                    p.product_price,
                    p.product_image,
                    c.cat_name,
                    b.brand_name
             FROM products p
             LEFT JOIN categories c ON p.product_cat = c.cat_id
             LEFT JOIN brands     b ON p.product_brand = b.brand_id
             WHERE p.product_title   LIKE ?
                OR p.product_desc    LIKE ?
                OR p.product_keywords LIKE ?
             ORDER BY p.product_title"
        );

        $stmt->bind_param('sss', $like, $like, $like);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }


/**
 * @var string
 */
    const PRODUCT_SELECT = "SELECT p.*,
                                   c.cat_name,
                                   b.brand_name
                            FROM products p
                            LEFT JOIN categories c ON p.product_cat   = c.cat_id
                            LEFT JOIN brands     b ON p.product_brand = b.brand_id";

/**
 * @param int $limit  how many rows, or 0 for all of them
 * @return array list of rows
 */
    public function getLatestProducts($limit = 0)
    {
        

        $limit = (int) $limit;

        $sql = self::PRODUCT_SELECT . ' ORDER BY p.product_id DESC';

        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }

        $result = $this->conn->query($sql);

        return $result->fetch_all(MYSQLI_ASSOC);
    }

/**
 * @param string $column  'product_cat' or 'product_brand'
 * @param int    $id
 * @return array list of rows
 */
    private function getProductsWhereColumn($column, $id)
    {
        $stmt = $this->conn->prepare(
            self::PRODUCT_SELECT . "
             WHERE p.$column = ?
             ORDER BY p.product_title"
        );

        $stmt->bind_param('i', $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductsByCategory($catId)
    {
        return $this->getProductsWhereColumn('product_cat', (int) $catId);
    }

    public function getProductsByBrand($brandId)
    {
        return $this->getProductsWhereColumn('product_brand', (int) $brandId);
    }
}
