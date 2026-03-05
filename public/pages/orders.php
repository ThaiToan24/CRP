<?php
/**
 * Orders Page
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
$role = $user['role'];

// Get orders based on role
if ($role === 'customer') {
    $ordersQuery = "SELECT o.*, u.name as seller_name FROM orders o 
                    JOIN users u ON o.seller_id = u.id 
                    WHERE o.customer_id = ? 
                    ORDER BY o.created_at DESC";
} elseif ($role === 'seller') {
    $ordersQuery = "SELECT o.*, u.name as customer_name FROM orders o 
                    JOIN users u ON o.customer_id = u.id 
                    WHERE o.seller_id = ? 
                    ORDER BY o.created_at DESC";
}

$stmt = $db->prepare($ordersQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'My Orders - DB eCommerce';
// $baseUrl provided by header include
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8 pb-12">
    <h1 class="text-3xl font-bold mb-8">My Orders</h1>
    
    <?php if (empty($orders)): ?>
        <div class="bg-white p-8 rounded-lg text-center">
            <p class="text-gray-500 mb-4">You have no orders yet</p>
            <a href="<?php echo $baseUrl; ?>/pages/products.php" class="btn btn-primary">
                Start Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg overflow-hidden">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th><?php echo $role === 'customer' ? 'Seller' : 'Customer'; ?></th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($role === 'customer' ? $order['seller_name'] : $order['customer_name']); ?></td>
                            <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $order['status'] === 'delivered' ? 'success' : 
                                         ($order['status'] === 'cancelled' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="<?php echo $baseUrl; ?>/pages/order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-outline text-sm">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
