<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/utils/Auth.php';

$auth = new Auth($db);

$usersToTest = [
    'admin@gmail.com',
    'hongmy@gmail.com',
    'thaitoan@gmail.com'
];

$res = $db->query("SELECT id FROM products LIMIT 1");
if ($res->num_rows == 0) {
    die("No products found");
}
$productId = $res->fetch_assoc()["id"];
echo "Using Product ID: $productId\n";

foreach ($usersToTest as $email) {
    echo "Testing for: $email\n";
    $result = $auth->login($email, '123123');
    if (!$result['success']) {
        echo "Login failed for $email: " . $result['message'] . "\n";
        continue;
    }
    
    $user = $auth->getCurrentUser();
    $customerId = $user['id'];
    $role = $user['role'];
    echo "Logged in as $role (ID: $customerId)\n";
    
    // Simulate Add To Cart
    $quantity = 1;
    $stmt = $db->prepare("SELECT id FROM cart WHERE customer_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $customerId, $productId);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $stmt = $db->prepare("UPDATE cart SET quantity = quantity + ? WHERE customer_id = ? AND product_id = ?");
        $stmt->bind_param("iii", $quantity, $customerId, $productId);
    } else {
        $stmt = $db->prepare("INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $customerId, $productId, $quantity);
    }
    
    if ($stmt->execute()) {
        echo "Cart Add: SUCCESS\n";
    } else {
        echo "Cart Add: FAILED - " . $db->error . "\n";
    }
    
    // Simulate Add to Wishlist
    $stmt = $db->prepare("SELECT id FROM wishlist WHERE customer_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $customerId, $productId);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        echo "Wishlist Add: Already in wishlist\n";
    } else {
        $stmt = $db->prepare("INSERT INTO wishlist (customer_id, product_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $customerId, $productId);
        if ($stmt->execute()) {
            echo "Wishlist Add: SUCCESS\n";
        } else {
            echo "Wishlist Add: FAILED - " . $db->error . "\n";
        }
    }
    
    $auth->logout();
    echo "-------------------\n";
}
