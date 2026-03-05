<?php
/**
 * Category Model
 */

require_once __DIR__ . '/BaseModel.php';

class Category extends BaseModel {
    public function __construct($database) {
        parent::__construct($database, 'categories');
    }
    
    /**
     * Get all active categories
     */
    public function getAllActive() {
        $query = "SELECT * FROM categories WHERE deleted_at IS NULL ORDER BY name ASC";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

/**
 * Order Model
 */
class Order extends BaseModel {
    public function __construct($database) {
        parent::__construct($database, 'orders');
    }
    
    /**
     * Get orders by customer
     */
    public function getByCustomer($customerId) {
        $query = "SELECT o.*, u.name as seller_name
                 FROM orders o 
                 JOIN users u ON o.seller_id = u.id 
                 WHERE o.customer_id = ? AND o.deleted_at IS NULL
                 ORDER BY o.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get orders by seller
     */
    public function getBySeller($sellerId) {
        $query = "SELECT o.*, u.name as customer_name
                 FROM orders o 
                 JOIN users u ON o.customer_id = u.id 
                 WHERE o.seller_id = ? AND o.deleted_at IS NULL
                 ORDER BY o.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get order with items
     */
    public function getWithItems($orderId) {
        $order = $this->getById($orderId);
        
        if (!$order) {
            return null;
        }
        
        // Get order items
        $stmt = $this->db->prepare("SELECT oi.*, p.name as product_name, p.image as product_image
                                    FROM order_items oi 
                                    JOIN products p ON oi.product_id = p.id 
                                    WHERE oi.order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        return $order;
    }
}

/**
 * Review Model
 */
class Review extends BaseModel {
    public function __construct($database) {
        parent::__construct($database, 'reviews');
    }
    
    /**
     * Get reviews by product
     */
    public function getByProduct($productId) {
        $query = "SELECT r.*, u.name as customer_name
                 FROM reviews r 
                 JOIN users u ON r.customer_id = u.id 
                 WHERE r.product_id = ? AND r.deleted_at IS NULL
                 ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get average rating for product
     */
    public function getAverageRating($productId) {
        $stmt = $this->db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
                                    FROM reviews 
                                    WHERE product_id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
