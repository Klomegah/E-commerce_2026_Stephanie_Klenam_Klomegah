<?php


require_once __DIR__ . '/../classes/ProductClass.php';

class ProductController
{
    private $product;

    public function __construct()
    {
        $this->product = new ProductClass();
    }


    public function addBrand($name)
    {
        $name = trim(strip_tags($name));

        if ($name === '') {
            return ['success' => false, 'error' => 'Please enter a brand name.'];
        }

        if (mb_strlen($name) > ProductClass::MAX_BRAND_NAME) {
            return [
                'success' => false,
                'error'   => 'Brand name must be ' . ProductClass::MAX_BRAND_NAME . ' characters or fewer.',
            ];
        }

        if (!$this->product->addBrand($name)) {
            return ['success' => false, 'error' => 'Could not add the brand. Please try again.'];
        }

        return ['success' => true];
    }

    public function getAllBrands()
    {
        return $this->product->getAllBrands();
    }

    public function getBrandById($id)
    {
        return $this->product->getBrandById($id);
    }

    public function updateBrand($id, $name)
    {
        $name = trim(strip_tags($name));

        if (!is_numeric($id) || (int) $id < 1) {
            return ['success' => false, 'error' => 'Invalid brand id.'];
        }

        if ($name === '') {
            return ['success' => false, 'error' => 'Please enter a brand name.'];
        }

        if (mb_strlen($name) > ProductClass::MAX_BRAND_NAME) {
            return [
                'success' => false,
                'error'   => 'Brand name must be ' . ProductClass::MAX_BRAND_NAME . ' characters or fewer.',
            ];
        }

        if ($this->product->getBrandById((int) $id) === null) {
            return ['success' => false, 'error' => 'That brand no longer exists.'];
        }

        if (!$this->product->updateBrand((int) $id, $name)) {
            return ['success' => false, 'error' => 'Could not update the brand. Please try again.'];
        }

        return ['success' => true];
    }


    public function addCategory($name)
    {
        $name = trim(strip_tags($name));

        if ($name === '') {
            return ['success' => false, 'error' => 'Please enter a category name.'];
        }

        if (mb_strlen($name) > ProductClass::MAX_CATEGORY_NAME) {
            return [
                'success' => false,
                'error'   => 'Category name must be ' . ProductClass::MAX_CATEGORY_NAME . ' characters or fewer.',
            ];
        }

        if (!$this->product->addCategory($name)) {
            return ['success' => false, 'error' => 'Could not add the category. Please try again.'];
        }

        return ['success' => true];
    }

    public function getAllCategories()
    {
        return $this->product->getAllCategories();
    }

    public function getCategoryById($id)
    {
        return $this->product->getCategoryById($id);
    }

    public function updateCategory($id, $name)
    {
        $name = trim(strip_tags($name));

        if (!is_numeric($id) || (int) $id < 1) {
            return ['success' => false, 'error' => 'Invalid category id.'];
        }

        if ($name === '') {
            return ['success' => false, 'error' => 'Please enter a category name.'];
        }

        if (mb_strlen($name) > ProductClass::MAX_CATEGORY_NAME) {
            return [
                'success' => false,
                'error'   => 'Category name must be ' . ProductClass::MAX_CATEGORY_NAME . ' characters or fewer.',
            ];
        }

        if ($this->product->getCategoryById((int) $id) === null) {
            return ['success' => false, 'error' => 'That category no longer exists.'];
        }

        if (!$this->product->updateCategory((int) $id, $name)) {
            return ['success' => false, 'error' => 'Could not update the category. Please try again.'];
        }

        return ['success' => true];
    }


// Validate and save a new product.
    public function addProduct($cat, $brand, $title, $price, $desc, $image_filename, $keywords)
    {
        $error = $this->validateProductFields($cat, $brand, $title, $price, $desc, $keywords);

        if ($error !== null) {
            return ['success' => false, 'error' => $error];
        }

        if (!$this->product->addProduct(
            (int) $cat,
            (int) $brand,
            $title,
            (float) $price,
            $desc,
            $image_filename,
            $keywords
        )) {
            return ['success' => false, 'error' => $this->writeError($title, 'Could not save the product. Please try again.')];
        }

        return ['success' => true];
    }

/* Turn a failed write into a sentence the admin can act on. */
    private function writeError($title, $fallback)
    {
        if ($this->product->lastError === 'duplicate_title') {
            return 'Another product is already called "' . $title
                 . '". Please use a different title.';
        }

        return $fallback;
    }

// Validate and update an existing product.
    public function updateProduct($id, $cat, $brand, $title, $price, $desc, $image_filename, $keywords)
    {
        if (!is_numeric($id) || (int) $id < 1) {
            return ['success' => false, 'error' => 'Invalid product id.'];
        }

        // Pass the id so the duplicate-title check ignores the very row being.
        // would be reported as a duplicate of itself.
        $error = $this->validateProductFields(
            $cat, $brand, $title, $price, $desc, $keywords, (int) $id
        );

        if ($error !== null) {
            return ['success' => false, 'error' => $error];
        }

        if ($this->product->getProductById((int) $id) === null) {
            return ['success' => false, 'error' => 'That product no longer exists.'];
        }

        if (!$this->product->updateProduct(
            (int) $id,
            (int) $cat,
            (int) $brand,
            $title,
            (float) $price,
            $desc,
            $image_filename,
            $keywords
        )) {
            return ['success' => false, 'error' => $this->writeError($title, 'Could not update the product. Please try again.')];
        }

        return ['success' => true];
    }

    public function getAllProducts()
    {
        return $this->product->getAllProducts();
    }

    public function getProductById($id)
    {
        return $this->product->getProductById($id);
    }

/* Shared checks for add and update, so both forms reject exactly the same input. */
    private function validateProductFields($cat, $brand, $title, $price, $desc, $keywords, $excludeId = 0)
    {
        if (!is_numeric($cat) || (int) $cat < 1) {
            return 'Please choose a category.';
        }

        if (!is_numeric($brand) || (int) $brand < 1) {
            return 'Please choose a brand.';
        }

        $title = trim($title);

        if ($title === '') {
            return 'Please enter a product title.';
        }

        if (mb_strlen($title) > ProductClass::MAX_TITLE) {
            return 'Title must be ' . ProductClass::MAX_TITLE . ' characters or fewer.';
        }

        // name has to be unique.
        // readable message; the UNIQUE index added by.
        // database/add_unique_product_title.sql is the actual guarantee,.
        $existingId = $this->product->getIdByTitle($title, $excludeId);

        if ($existingId !== null) {
            return 'Another product is already called "' . $title . '" (product #'
                 . $existingId . '). Please use a different title.';
        }

        if (!is_numeric($price) || (float) $price < 0) {
            return 'Please enter a valid price, for example 19.99.';
        }

        if (trim($desc) === '') {
            return 'Please enter a product description.';
        }

        if (mb_strlen($desc) > ProductClass::MAX_DESC) {
            return 'Description must be ' . ProductClass::MAX_DESC . ' characters or fewer.';
        }

        if (mb_strlen($keywords) > ProductClass::MAX_KEYWORDS) {
            return 'Keywords must be ' . ProductClass::MAX_KEYWORDS . ' characters or fewer.';
        }

        return null;
    }


    public function getCategories()
    {
        return $this->product->getCategories();
    }

    public function getBrands()
    {
        return $this->product->getBrands();
    }

    public function search($term)
    {
        $term = trim(strip_tags($term));

        if ($term === '') {
            return ['success' => false, 'error' => 'Please enter a search term.'];
        }

        if (mb_strlen($term) > 100) {
            return ['success' => false, 'error' => 'Search term is too long.'];
        }

        return ['success' => true, 'products' => $this->product->searchProducts($term)];
    }


/**
 * @param int $limit how many to return; 0 means all of them
 */
    public function getLatestProducts($limit = 0)
    {
        return $this->product->getLatestProducts($limit);
    }

    public function getProductsByCategory($catId)
    {
        if (!is_numeric($catId) || (int) $catId < 1) {
            return [];
        }

        return $this->product->getProductsByCategory((int) $catId);
    }

    public function getProductsByBrand($brandId)
    {
        if (!is_numeric($brandId) || (int) $brandId < 1) {
            return [];
        }

        return $this->product->getProductsByBrand((int) $brandId);
    }

    public function getPublicProductById($id)
    {
        if (!is_numeric($id) || (int) $id < 1) {
            return null;
        }

        return $this->product->getProductById((int) $id);
    }
}
