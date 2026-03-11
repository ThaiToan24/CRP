<?php
// Emulate cart add
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/utils/Auth.php';
$auth = new Auth($db);
$auth->login('thaitoan@gmail.com', '123123');

$user = $auth->getCurrentUser();
if (!$user) die("Login failed\n");

echo "Logged in as " . $user['email'] . "\n";

// Emulate POST request to cart.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['action'] = 'add';
// We can't really set php://input data easily without stream wrappers, so we will include and modify variables or just test logic again.

$productId = 2;
$quantity = 1;
$customerId = $user['id'];

// Check if already in cart
$stmt = $db->prepare("SELECT id FROM cart WHERE customer_id = ? AND product_id = ?");
$stmt->bind_param("ii", $customerId, $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "Already in cart. Updating...\n";
    $stmt = $db->prepare("UPDATE cart SET quantity = quantity + ? WHERE customer_id = ? AND product_id = ?");
    $stmt->bind_param("iii", $quantity, $customerId, $productId);
} else {
    echo "New item. Inserting...\n";
    $stmt = $db->prepare("INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $customerId, $productId, $quantity);
}

if ($stmt->execute()) {
    echo "Success: Added to cart. Would redirect.\n";
} else {
    echo "Failed to add to cart.\n";
}
