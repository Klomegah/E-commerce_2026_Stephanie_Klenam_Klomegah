<?php

/**
 * controllers/ProductController.php - CONTROLLER layer.
 *
 * Receives the request, decides what should be allowed, calls the
 * Model, and hands the result back to the View.
 */

require_once __DIR__ . '/../classes/ProductClass.php';

class ProductController
{
    /** @var ProductClass */
    private $product;

    public function __construct()
    {
        $this->product = new ProductClass();
    }

    public function getCategories()
    {
        return $this->product->getCategories();
    }

    public function getBrands()
    {
        return $this->product->getBrands();
    }

    /**
     * Validate the search term and delegate to the Model.
    
     */
    public function search($term)
    {
        $term = trim($term);

        if ($term === '') {
            return ['success' => false, 'error' => 'Please enter a search term.'];
        }

        // product_title is varchar(200); cap the term so a huge
        // string can never be used to hammer the database.
        if (mb_strlen($term) > 100) {
            return ['success' => false, 'error' => 'Search term is too long.'];
        }

        return ['success' => true, 'products' => $this->product->searchProducts($term)];
    }
}
