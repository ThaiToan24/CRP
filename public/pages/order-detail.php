<?php
/**
 * Order Detail Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

$auth = new Auth($db);

// Redirect if not logged in
require_once __DIR__ . '/../../src/utils/Url.php';
if (!$auth->isLoggedIn()) {
    redirect('auth/login.php');
}

$user = $auth->getCurrentUser();
$userId = $user['id'];
$orderId = $_GET['id'] ?? null;

require_once __DIR__ . '/../../src/utils/Url.php';
if (!$orderId) {
    redirect('pages/orders.php');
}

// Get order details
$stmt = $db->prepare("SELECT o.*, u.name as seller_name FROM orders o 
                      JOIN users u ON o.seller_id = u.id 
                      WHERE o.id = ? AND (o.customer_id = ? OR o.seller_id = ?)");
$stmt->bind_param("iii", $orderId, $userId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

require_once __DIR__ . '/../../src/utils/Url.php';
if (!$order) {
    redirect('pages/orders.php');
}

// Get order items
$stmt = $db->prepare("SELECT oi.*, p.name as product_name, p.image as product_image 
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE oi.order_id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$orderItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle status update (for seller/admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'seller') {
    $newStatus = $_POST['status'] ?? null;
    $validStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
    
    if (in_array($newStatus, $validStatuses) && $order['seller_id'] == $userId) {
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $orderId);
        $stmt->execute();
        
        // Refresh order
        $stmt = $db->prepare("SELECT o.*, u.name as seller_name FROM orders o 
                              JOIN users u ON o.seller_id = u.id 
                              WHERE o.id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
    }
}

// Handle order cancellation (for customer)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order' && $user['role'] === 'customer') {
    if ($order['status'] === 'pending' && $order['customer_id'] == $userId) {
        $stmt = $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        $stmt->bind_param("i", $orderId);
        if ($stmt->execute()) {
            $order['status'] = 'cancelled';
        }
    }
}

$pageTitle = 'Order #' . $orderId . ' - DB eCommerce';
// $baseUrl provided by header
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8 pb-12">
    <a href="<?php echo $baseUrl; ?>/pages/orders.php" class="btn btn-outline mb-4">← Back to Orders</a>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Order Header -->
            <div class="bg-white rounded-lg p-6">
                <h1 class="text-2xl font-bold mb-4">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h1>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-gray-600">Order Date</p>
                        <p class="font-semibold"><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Status</p>
                        <p class="font-semibold">
                            <span class="badge badge-<?php 
                                echo $order['status'] === 'delivered' ? 'success' : 
                                     ($order['status'] === 'cancelled' ? 'danger' : 'warning'); 
                            ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-600">Payment</p>
                        <p class="font-semibold"><?php echo ucfirst($order['payment_method']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Payment Status</p>
                        <p class="font-semibold">
                            <span class="badge badge-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="bg-white rounded-lg p-6">
                <h2 class="text-xl font-bold mb-4">Items</h2>
                
                <div class="space-y-4">
                    <?php foreach ($orderItems as $item): ?>
                        <div class="cart-item">
                            <img src="<?php echo $uploadUrl . '/' . htmlspecialchars($item['product_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                 class="cart-item-image">
                            
                            <div class="cart-item-info flex-1">
                                <p class="cart-item-name"><?php echo htmlspecialchars($item['product_name']); ?></p>
                                <p class="text-sm text-gray-600 mb-2">Quantity: <?php echo $item['quantity']; ?></p>
                                <p class="cart-item-price">$<?php echo number_format($item['unit_price'], 2); ?>/unit</p>
                            </div>
                            
                            <div class="text-right">
                                <p class="font-bold text-lg">
                                    $<?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Shipping Address -->
            <div class="bg-white rounded-lg p-6">
                <h2 class="text-xl font-bold mb-4">Shipping Information</h2>
                
                <div class="space-y-2">
                    <p><strong>Recipient:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                    <?php if ($order['notes']): ?>
                        <p><strong>Notes:</strong> <?php echo htmlspecialchars($order['notes']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="bg-white rounded-lg p-6 h-fit">
            <h3 class="text-xl font-bold mb-4">Order Summary</h3>
            
            <div class="space-y-2 mb-4 pb-4 border-b">
                <div class="flex justify-between">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($order['total_price'], 2); ?></span>
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
            
            <div class="flex justify-between font-bold text-lg mb-6">
                <span>Total:</span>
                <span>$<?php echo number_format($order['total_price'], 2); ?></span>
            </div>
            
            <!-- Status Update (for Seller) -->
            <?php if ($user['role'] === 'seller' && $order['seller_id'] == $userId): ?>
                <form method="POST" class="space-y-3">
                    <label class="block font-semibold">Update Status</label>
                    <select name="status" class="w-full border rounded px-3 py-2">
                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-primary w-full">Update Status</button>
                </form>
            <?php endif; ?>
            
            <!-- Cancel Action (for Customer) -->
            <?php if ($user['role'] === 'customer' && $order['customer_id'] == $userId && $order['status'] === 'pending'): ?>
                <form method="POST" class="mt-4" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                    <input type="hidden" name="action" value="cancel_order">
                    <button type="submit" class="w-full px-4 py-2 border border-red-500 text-red-500 rounded hover:bg-red-50 transition-colors font-semibold">
                        Cancel Order
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="mt-4 pt-4 border-t">
                <p class="text-sm text-gray-600">
                    <strong>Seller:</strong> <?php echo htmlspecialchars($order['seller_name']); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
