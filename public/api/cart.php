<?php
/**
 * Cart API Endpoints
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

header('Content-Type: application/json');

$auth = new Auth($db);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Check authentication
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user = $auth->getCurrentUser();
$customerId = $user['id'];

if ($method === 'POST' && $action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    $productId = $data['product_id'] ?? null;
    $quantity = $data['quantity'] ?? 1;
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
        exit();
    }
    
    // Check if already in cart
    $stmt = $db->prepare("SELECT id FROM cart WHERE customer_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $customerId, $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update quantity
        $stmt = $db->prepare("UPDATE cart SET quantity = quantity + ? WHERE customer_id = ? AND product_id = ?");
        $stmt->bind_param("iii", $quantity, $customerId, $productId);
    } else {
        // Insert new item
        $stmt = $db->prepare("INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $customerId, $productId, $quantity);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Added to cart']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
    }
}

elseif ($method === 'POST' && $action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    $cartId = $data['cart_id'] ?? null;
    $quantity = $data['quantity'] ?? 1;
    
    if (!$cartId || $quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }
    
    $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND customer_id = ?");
    $stmt->bind_param("iii", $quantity, $cartId, $customerId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update']);
    }
}

elseif ($method === 'POST' && $action === 'remove') {
    $data = json_decode(file_get_contents('php://input'), true);
    $cartId = $data['cart_id'] ?? null;
    
    if (!$cartId) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }
    
    $stmt = $db->prepare("DELETE FROM cart WHERE id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $cartId, $customerId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Removed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove']);
    }
}

elseif ($method === 'GET' && $action === 'count') {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM cart WHERE customer_id = ?");
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    echo json_encode(['count' => $result['count']]);
}

else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
