<?php
/**
 * Wishlist API Endpoints
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

header('Content-Type: application/json');

$auth = new Auth($db);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Check authentication and role
if (!$auth->isLoggedIn() || !$auth->hasRole('customer')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized action']);
    exit();
}

$user = $auth->getCurrentUser();
$customerId = $user['id'];

if ($method === 'POST' && $action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    $productId = $data['product_id'] ?? null;
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
        exit();
    }
    
    // Check if already in wishlist
    $stmt = $db->prepare("SELECT id FROM wishlist WHERE customer_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $customerId, $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Already in wishlist']);
        exit();
    }
    
    // Add to wishlist
    $stmt = $db->prepare("INSERT INTO wishlist (customer_id, product_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $customerId, $productId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add to wishlist']);
    }
}

elseif ($method === 'POST' && $action === 'remove') {
    $data = json_decode(file_get_contents('php://input'), true);
    $wishlistId = $data['wishlist_id'] ?? null;
    
    if (!$wishlistId) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }
    
    $stmt = $db->prepare("DELETE FROM wishlist WHERE id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $wishlistId, $customerId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Removed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove']);
    }
}

else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
