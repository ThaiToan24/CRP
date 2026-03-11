<?php
/**
 * Checkout Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

$auth = new Auth($db);

// Redirect if not logged in
require_once __DIR__ . '/../../src/utils/Url.php';
if (!$auth->isLoggedIn() || !$auth->hasRole('customer')) {
    redirect('auth/login.php');
}

$user = $auth->getCurrentUser();
$customerId = $user['id'];
$error = '';
$success = '';

// Get cart items
$cartQuery = "SELECT c.*, p.name, p.price, p.seller_id, u.name as seller_name 
              FROM cart c 
              JOIN products p ON c.product_id = p.id 
              JOIN users u ON p.seller_id = u.id 
              WHERE c.customer_id = ?";
$stmt = $db->prepare($cartQuery);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user details for form
$stmt = $db->prepare("SELECT name, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $customerId);
$stmt->execute();
$userDetails = $stmt->get_result()->fetch_assoc();

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Handle checkout form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = $_POST['customer_name'] ?? '';
    $customerPhone = $_POST['customer_phone'] ?? '';
    $shippingAddress = $_POST['shipping_address'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? 'cod';
    $notes = $_POST['notes'] ?? '';

    $ordersNotesCol = $db->query("SHOW COLUMNS FROM `orders` LIKE 'notes'");
    $ordersNotesExists = $ordersNotesCol && $ordersNotesCol->num_rows > 0;

    // Validate
    if (empty($customerName) || empty($customerPhone) || empty($shippingAddress)) {
        $error = 'All required fields must be filled';
    } elseif (empty($cartItems)) {
        $error = 'Cart is empty';
    } else {
        // Group items by seller for multiple orders
        $ordersBySeller = [];
        foreach ($cartItems as $item) {
            if (!isset($ordersBySeller[$item['seller_id']])) {
                $ordersBySeller[$item['seller_id']] = [];
            }
            $ordersBySeller[$item['seller_id']][] = $item;
        }
        
        // Create orders for each seller
        $ordersCreated = 0;
        foreach ($ordersBySeller as $sellerId => $items) {
            $totalPrice = 0;
            foreach ($items as $item) {
                $totalPrice += $item['price'] * $item['quantity'];
            }
            
            // Create order
            if ($ordersNotesExists) {
                $stmt = $db->prepare("INSERT INTO orders (customer_id, seller_id, total_price, payment_method, 
                                                           customer_name, customer_phone, shipping_address, notes) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iidsssss", $customerId, $sellerId, $totalPrice, $paymentMethod, 
                                $customerName, $customerPhone, $shippingAddress, $notes);
            } else {
                // Fallback for older schema that does not yet have notes column
                $stmt = $db->prepare("INSERT INTO orders (customer_id, seller_id, total_price, payment_method, 
                                                           customer_name, customer_phone, shipping_address) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iidssss", $customerId, $sellerId, $totalPrice, $paymentMethod, 
                                $customerName, $customerPhone, $shippingAddress);
            }

            if ($stmt->execute()) {
                $orderId = $db->insert_id;
                
                // If old schema has no unit_price, insert only basic order_items columns.
                $orderItemUnitPriceCol = $db->query("SHOW COLUMNS FROM order_items LIKE 'unit_price'");
                $orderItemUnitPriceExists = $orderItemUnitPriceCol && $orderItemUnitPriceCol->num_rows > 0;

                // Create order items
                foreach ($items as $item) {
                    if ($orderItemUnitPriceExists) {
                        $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) 
                                              VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("iiii", $orderId, $item['product_id'], $item['quantity'], $item['price']);
                    } else {
                        $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity) 
                                              VALUES (?, ?, ?)");
                        $stmt->bind_param("iii", $orderId, $item['product_id'], $item['quantity']);
                    }
                    $stmt->execute();
                    
                    // Update product stock
                    $stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                    $stmt->execute();
                }
                
                $ordersCreated++;
            }
        }
        
        if ($ordersCreated > 0) {
            // Clear cart
            $stmt = $db->prepare("DELETE FROM cart WHERE customer_id = ?");
            $stmt->bind_param("i", $customerId);
            $stmt->execute();
            
            // Redirect to payment
            redirect('pages/payment.php?success=1');
        } else {
            $error = 'Failed to create order. Please try again.';
        }
    }
}

$pageTitle = 'Checkout - DB eCommerce';
// $baseUrl provided by header include
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8 pb-12">
    <h1 class="text-3xl font-bold mb-8">Checkout</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Checkout Form -->
        <div class="lg:col-span-2">
            <form method="POST" class="bg-white rounded-lg p-6 space-y-6">
                <!-- Customer Information -->
                <div>
                    <h2 class="text-xl font-bold mb-4">Customer Information</h2>
                    
                    <div class="space-y-4">
                        <div class="form-group">
                            <label for="customer_name">Full Name *</label>
                            <input type="text" id="customer_name" name="customer_name" 
                                   value="<?php echo htmlspecialchars($userDetails['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="customer_phone">Phone Number *</label>
                            <input type="tel" id="customer_phone" name="customer_phone" 
                                   value="<?php echo htmlspecialchars($userDetails['phone']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="shipping_address">Shipping Address *</label>
                            <textarea id="shipping_address" name="shipping_address" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Order Notes (Optional)</label>
                            <textarea id="notes" name="notes" rows="3" placeholder="Any special instructions?"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div>
                    <h2 class="text-xl font-bold mb-4">Payment Method</h2>
                    
                    <div class="space-y-3">
                        <label class="flex items-center p-3 border rounded cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="payment_method" value="cod" checked class="mr-3">
                            <div>
                                <p class="font-semibold">Cash on Delivery (COD)</p>
                                <p class="text-sm text-gray-600">Pay when you receive the order</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-3 border rounded cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="payment_method" value="vnpay" class="mr-3">
                            <div>
                                <p class="font-semibold">VNPay</p>
                                <p class="text-sm text-gray-600">Pay online using VNPay gateway</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-full py-3">Place Order</button>
            </form>
        </div>
        
        <!-- Order Summary -->
        <div>
            <div class="bg-white rounded-lg p-6 h-fit sticky top-24">
                <h3 class="text-xl font-bold mb-4">Order Summary</h3>
                
                <div class="space-y-3 mb-4 pb-4 border-b">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="flex justify-between text-sm">
                            <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                            <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Shipping:</span>
                        <span>$0.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Tax:</span>
                        <span>$0.00</span>
                    </div>
                </div>
                
                <div class="flex justify-between font-bold text-lg">
                    <span>Total:</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
