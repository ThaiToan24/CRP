<?php
session_start();
require_once 'config/database.php';
require_once 'src/utils/Auth.php';

$auth = new Auth($db);

// Simulate login as customer
$userId = 5; // From the test above
$_SESSION['user_id'] = $userId;
$_SESSION['role'] = 'customer';

// Test add to cart API
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['action'] = 'add';

// Simulate JSON input
$json = '{"product_id": 4, "quantity": 1}';
$data = json_decode($json, true);

echo "Data: ";
print_r($data);
echo "\n";
echo "product_id: " . ($data['product_id'] ?? 'not set') . "\n";

// Simulate API logic
$productId = $data['product_id'] ?? null;
$quantity = $data['quantity'] ?? 1;

echo "productId: $productId, quantity: $quantity\n";

if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit();
}

echo "Product ID is valid\n";

// Check if already in cart
$stmt = $db->prepare("SELECT id FROM cart WHERE customer_id = ? AND product_id = ?");
$stmt->bind_param("ii", $userId, $productId);
$stmt->execute();
$result = $stmt->get_result();

echo "Existing cart items: " . $result->num_rows . "\n";

if ($result->num_rows > 0) {
    // Update quantity
    $stmt = $db->prepare("UPDATE cart SET quantity = quantity + ? WHERE customer_id = ? AND product_id = ?");
    $stmt->bind_param("iii", $quantity, $userId, $productId);
    echo "Updating existing cart item\n";
} else {
    // Insert new item
    $stmt = $db->prepare("INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $userId, $productId, $quantity);
    echo "Inserting new cart item\n";
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Added to cart']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
}
?>