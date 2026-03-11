<?php
/**
 * Payment Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

// payment page doesn't need its own baseUrl; header will define BASE_URL
$pageTitle = 'Payment - DB eCommerce';
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8 pb-12">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg p-8 text-center">
            <div class="mb-6">
                <svg class="w-20 h-20 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            
            <h1 class="text-3xl font-bold mb-4">Order Placed Successfully!</h1>
            <p class="text-gray-600 mb-8">Thank you for your order. You will receive a confirmation email shortly.</p>
            
            <div class="bg-gray-100 p-6 rounded-lg mb-8">
                <p class="text-gray-700 mb-2">You can track your order status in <strong>My Orders</strong></p>
                <p class="text-sm text-gray-600">Order confirmation and details have been sent to your email address</p>
            </div>
            
            <div class="space-y-3">
                <a href="<?php echo $baseUrl; ?>/pages/orders.php" class="btn btn-primary block">
                    View My Orders
                </a>
                <a href="<?php echo $baseUrl; ?>/pages/home.php" class="btn btn-outline block">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
