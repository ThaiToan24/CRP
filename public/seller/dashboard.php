<?php
/**
 * Seller Dashboard
 * Main seller control panel
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

$auth = new Auth($db);

// Check if seller
require_once __DIR__ . '/../../src/utils/Url.php';
if (!$auth->isLoggedIn() || !$auth->hasRole('seller')) {
    redirect('auth/login.php');
}

$user = $auth->getCurrentUser();
$sellerId = $user['id'];

// Get seller statistics
$productCount = $db->query("SELECT COUNT(*) as count FROM products WHERE seller_id = $sellerId AND deleted_at IS NULL")->fetch_assoc()['count'];

// If our DB schema is missing orders.seller_id (legacy installs), use product join fallback.
$orderSellerIdColumnCheck = $db->query("SHOW COLUMNS FROM orders LIKE 'seller_id'");
$orderSellerColumnExists = $orderSellerIdColumnCheck && $orderSellerIdColumnCheck->num_rows > 0;

if ($orderSellerColumnExists) {
    $orderCount = $db->query("SELECT COUNT(*) as count FROM orders WHERE seller_id = $sellerId")->fetch_assoc()['count'];
    $totalRevenue = $db->query("SELECT SUM(total_price) as total FROM orders WHERE seller_id = $sellerId AND payment_status = 'paid'")->fetch_assoc()['total'] ?? 0;
    $pendingOrders = $db->query("SELECT COUNT(*) as count FROM orders WHERE seller_id = $sellerId AND status = 'pending'")->fetch_assoc()['count'];
} else {
    $orderCount = $db->query("SELECT COUNT(DISTINCT o.id) AS count FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN products p ON p.id = oi.product_id
        WHERE p.seller_id = $sellerId")->fetch_assoc()['count'];
    $totalRevenue = $db->query("SELECT SUM(DISTINCT o.total_price) AS total FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN products p ON p.id = oi.product_id
        WHERE p.seller_id = $sellerId AND o.payment_status = 'paid'")->fetch_assoc()['total'] ?? 0;
    $pendingOrders = $db->query("SELECT COUNT(DISTINCT o.id) AS count FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN products p ON p.id = oi.product_id
        WHERE p.seller_id = $sellerId AND o.status = 'pending'")->fetch_assoc()['count'];
}

$pageTitle = 'Seller Dashboard - DB eCommerce';
// baseUrl available via header constant
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8 pb-12">
    <h1 class="text-3xl font-bold mb-8">Seller Dashboard</h1>
    
    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg p-6 shadow">
            <h3 class="text-gray-600 text-sm font-semibold mb-2">Total Products</h3>
            <p class="text-3xl font-bold text-primary"><?php echo $productCount; ?></p>
        </div>
        
        <div class="bg-white rounded-lg p-6 shadow">
            <h3 class="text-gray-600 text-sm font-semibold mb-2">Total Orders</h3>
            <p class="text-3xl font-bold text-primary"><?php echo $orderCount; ?></p>
        </div>
        
        <div class="bg-white rounded-lg p-6 shadow">
            <h3 class="text-gray-600 text-sm font-semibold mb-2">Pending Orders</h3>
            <p class="text-3xl font-bold text-primary"><?php echo $pendingOrders; ?></p>
        </div>
        
        <div class="bg-white rounded-lg p-6 shadow">
            <h3 class="text-gray-600 text-sm font-semibold mb-2">Total Revenue</h3>
            <p class="text-3xl font-bold text-primary">$<?php echo number_format($totalRevenue, 2); ?></p>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg p-6 shadow">
            <h3 class="font-bold text-lg mb-4">Product Management</h3>
            <a href="<?php echo $baseUrl; ?>/seller/products.php" class="btn btn-primary w-full">
                Manage Products
            </a>
        </div>
        
        <div class="bg-white rounded-lg p-6 shadow">
            <h3 class="font-bold text-lg mb-4">Order Management</h3>
            <a href="<?php echo $baseUrl; ?>/seller/orders.php" class="btn btn-primary w-full">
                Manage Orders
            </a>
        </div>
        
        <div class="bg-white rounded-lg p-6 shadow">
            <h3 class="font-bold text-lg mb-4">Analytics</h3>
            <a href="<?php echo $baseUrl; ?>/seller/analytics.php" class="btn btn-primary w-full">
                View Analytics
            </a>
        </div>
    </div>
    
    <!-- Coming Soon -->
    <div class="mt-12 bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded">
        <h3 class="font-bold text-lg mb-2">🚀 Features Coming Soon</h3>
        <ul class="space-y-2 text-gray-700">
            <li>✓ Advanced product management interface</li>
            <li>✓ Real-time discount configuration</li>
            <li>✓ Inventory tracking</li>
            <li>✓ Sales analytics and reports</li>
            <li>✓ Customer reviews management</li>
            <li>✓ Promotion campaigns</li>
        </ul>
    </div>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
