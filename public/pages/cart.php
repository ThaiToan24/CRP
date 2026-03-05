<?php
/**
 * Shopping Cart Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

$auth = new Auth($db);

// Redirect to login if not logged in
require_once __DIR__ . '/../../src/utils/Url.php';
if (!$auth->isLoggedIn()) {
    redirect('auth/login.php');
}

$user = $auth->getCurrentUser();
$customerId = $user['id'];

// Get cart items
$cartQuery = "SELECT c.*, p.name, p.price, p.image, p.seller_id, u.name as seller_name 
              FROM cart c 
              JOIN products p ON c.product_id = p.id 
              JOIN users u ON p.seller_id = u.id 
              WHERE c.customer_id = ? 
              ORDER BY c.created_at DESC";
$stmt = $db->prepare($cartQuery);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$pageTitle = 'Shopping Cart - DB eCommerce';
// $baseUrl provided by header include
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8 pb-12">
    <h1 class="text-3xl font-bold mb-8">Shopping Cart</h1>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cart Items -->
        <div class="lg:col-span-2">
            <?php if (empty($cartItems)): ?>
                <div class="bg-white p-8 rounded-lg text-center">
                    <p class="text-gray-500 mb-4">Your cart is empty</p>
                    <a href="<?php echo $baseUrl; ?>/pages/products.php" class="btn btn-primary">
                        Continue Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item">
                            <img src="<?php echo $uploadUrl . '/' . htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="cart-item-image">
                            
                            <div class="cart-item-info">
                                <p class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></p>
                                <p class="text-sm text-gray-600 mb-2">by <?php echo htmlspecialchars($item['seller_name']); ?></p>
                                <p class="cart-item-price">$<?php echo number_format($item['price'], 2); ?></p>
                                
                                <div class="quantity-control mt-2">
                                    <button onclick="updateCartQuantity(<?php echo $item['id']; ?>, -1)" class="btn btn-outline">-</button>
                                    <input type="number" value="<?php echo $item['quantity']; ?>" 
                                           data-cart-id="<?php echo $item['id']; ?>" 
                                           class="cart-quantity" min="1">
                                    <button onclick="updateCartQuantity(<?php echo $item['id']; ?>, 1)" class="btn btn-outline">+</button>
                                </div>
                            </div>
                            
                            <div class="text-right">
                                <p class="font-bold text-lg mb-4">
                                    $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </p>
                                <button onclick="removeFromCart(<?php echo $item['id']; ?>)" class="btn btn-outline text-red-600 text-sm">
                                    Remove
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Order Summary -->
        <div class="bg-white rounded-lg p-6 h-fit sticky top-24">
            <h3 class="text-xl font-bold mb-4">Order Summary</h3>
            
            <div class="space-y-2 mb-4 pb-4 border-b">
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
            
            <div class="flex justify-between font-bold text-lg mb-6">
                <span>Total:</span>
                <span>$<?php echo number_format($subtotal, 2); ?></span>
            </div>
            
            <a href="<?php echo $baseUrl; ?>/pages/checkout.php" class="btn btn-primary w-full py-3 block text-center">
                Proceed to Checkout
            </a>
            
            <a href="<?php echo $baseUrl; ?>/pages/products.php" class="btn btn-outline w-full py-2 block text-center mt-2">
                Continue Shopping
            </a>
        </div>
    </div>
</div>

<script>
function updateCartQuantity(cartId, change) {
    const input = document.querySelector(`input[data-cart-id="${cartId}"]`);
    const newQty = Math.max(1, parseInt(input.value) + change);
    
    fetch('<?php echo $baseUrl; ?>/api/cart.php?action=update', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({cart_id: cartId, quantity: newQty})
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            input.value = newQty;
            location.reload();
        }
    });
}

function removeFromCart(cartId) {
    if (confirm('Remove from cart?')) {
        fetch('<?php echo $baseUrl; ?>/api/cart.php?action=remove', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({cart_id: cartId})
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                location.reload();
            }
        });
    }
}
</script>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
