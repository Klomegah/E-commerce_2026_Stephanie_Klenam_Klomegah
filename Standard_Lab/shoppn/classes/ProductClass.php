<?php

/**
 * classes/ProductClass.php - MODEL layer.
 */

require_once __DIR__ . '/../core/db_class.php';

class ProductClass extends Database
{
    /**
        * All categories, alphabetically.
     */
    public function getCategories()
    {
        $result = $this->conn->query(
            "SELECT cat_id, cat_name
             FROM categories
             ORDER BY cat_name"
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * All brands, alphabetically.
     */

    public function getBrands()
    {
        $result = $this->conn->query(
            "SELECT brand_id, brand_name
             FROM brands
             ORDER BY brand_name"
        );

        return $result->fetch_all(MYSQLI_ASSOC);
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
}
