<?php
/**
 * Product Model
 * Handles all product-related database operations
 */

require_once __DIR__ . '/BaseModel.php';

class Product extends BaseModel {
    public function __construct($database) {
        parent::__construct($database, 'products');
    }
    
    /**
     * Get all active products with seller information
     */
    public function getAllActive($limit = null, $offset = 0) {
        $query = "SELECT p.*, u.name as seller_name, c.name as category_name 
                 FROM products p 
                 JOIN users u ON p.seller_id = u.id 
                 JOIN categories c ON p.category_id = c.id 
                 WHERE p.status = 'active' AND p.deleted_at IS NULL 
                 AND c.deleted_at IS NULL AND u.status = 'active'
                 ORDER BY p.created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get products by category
     */
    public function getByCategory($categoryId, $limit = null, $offset = 0) {
        $query = "SELECT p.*, u.name as seller_name, c.name as category_name 
                 FROM products p 
                 JOIN users u ON p.seller_id = u.id 
                 JOIN categories c ON p.category_id = c.id 
                 WHERE p.category_id = ? AND p.status = 'active' AND p.deleted_at IS NULL
                 AND c.deleted_at IS NULL AND u.status = 'active'
                 ORDER BY p.created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get products by seller
     */
    public function getBySeller($sellerId, $limit = null, $offset = 0) {
        $query = "SELECT p.*, c.name as category_name 
                 FROM products p 
                 JOIN categories c ON p.category_id = c.id 
                 WHERE p.seller_id = ? AND p.deleted_at IS NULL";
        
        if ($limit) {
            $query .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get best-selling products
     */
    public function getBestSellers($limit = 10) {
        $query = "SELECT p.*, u.name as seller_name, c.name as category_name, SUM(oi.quantity) as total_sold
                 FROM products p 
                 JOIN users u ON p.seller_id = u.id 
                 JOIN categories c ON p.category_id = c.id 
                 LEFT JOIN order_items oi ON p.id = oi.product_id
                 WHERE p.status = 'active' AND p.deleted_at IS NULL
                 AND c.deleted_at IS NULL AND u.status = 'active'
                 GROUP BY p.id
                 ORDER BY total_sold DESC
                 LIMIT ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Search products by name
     */
    public function search($keyword, $limit = null, $offset = 0) {
        $keyword = '%' . $keyword . '%';
        $query = "SELECT p.*, u.name as seller_name, c.name as category_name 
                 FROM products p 
                 JOIN users u ON p.seller_id = u.id 
                 JOIN categories c ON p.category_id = c.id 
                 WHERE (p.name LIKE ? OR p.description LIKE ?) 
                 AND p.status = 'active' AND p.deleted_at IS NULL
                 AND c.deleted_at IS NULL AND u.status = 'active'
                 ORDER BY p.created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $keyword, $keyword);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get product with images
     */
    public function getWithImages($productId) {
        $product = $this->getById($productId);
        
        if (!$product) {
            return null;
        }
        
        // Get product images
        $stmt = $this->db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order ASC");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $product['images'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        return $product;
    }
    
    /**
     * Get effective price considering discount
     */
    public function getEffectivePrice($productId) {
        $product = $this->getById($productId);
        
        if (!$product) {
            return null;
        }
        
        $discount = 0;
        if ($product['discount_percentage'] > 0 && $product['discount_start_time'] && $product['discount_end_time']) {
            $now = date('Y-m-d H:i:s');
            if ($now >= $product['discount_start_time'] && $now <= $product['discount_end_time']) {
                $discount = ($product['price'] * $product['discount_percentage']) / 100;
            }
        }
        
        return $product['price'] - $discount;
    }
}
