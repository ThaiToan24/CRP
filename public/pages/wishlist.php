<?php
/**
 * Wishlist Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

$auth = new Auth($db);

// Redirect if not logged in or not customer
require_once __DIR__ . '/../../src/utils/Url.php';
if (!$auth->isLoggedIn() || !$auth->hasRole('customer')) {
    redirect('auth/login.php');
}

$user = $auth->getCurrentUser();
$userId = $user['id'];

// Get wishlist items
$wishlistQuery = "SELECT w.*, p.name, p.price, p.image, u.name as seller_name 
                  FROM wishlist w 
                  JOIN products p ON w.product_id = p.id 
                  JOIN users u ON p.seller_id = u.id 
                  WHERE w.customer_id = ? 
                  ORDER BY w.created_at DESC";
$stmt = $db->prepare($wishlistQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$wishlistItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'My Wishlist - DB eCommerce';
// $baseUrl is supplied by header include
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8 pb-12">
    <h1 class="text-3xl font-bold mb-8">My Wishlist</h1>
    
    <?php if (empty($wishlistItems)): ?>
        <div class="bg-white p-8 rounded-lg text-center">
            <p class="text-gray-500 mb-4">Your wishlist is empty</p>
            <a href="<?php echo $baseUrl; ?>/pages/products.php" class="btn btn-primary">
                Continue Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($wishlistItems as $item): ?>
                <div class="product-card">
                    <img src="<?php echo $uploadUrl . '/' . htmlspecialchars($item['image']); ?>" 
                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                         class="product-image">
                    <div class="product-info">
                        <a href="<?php echo $baseUrl; ?>/pages/product-detail.php?id=<?php echo $item['product_id']; ?>" 
                           class="product-name">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </a>
                        <div class="product-price">
                            $<?php echo number_format($item['price'], 2); ?>
                        </div>
                        <div class="product-seller">
                            By <?php echo htmlspecialchars($item['seller_name']); ?>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="addToCart(<?php echo $item['product_id']; ?>)" class="btn btn-primary flex-1 text-sm">
                                Add to Cart
                            </button>
                            <button onclick="removeFromWishlist(<?php echo $item['id']; ?>)" class="btn btn-outline flex-1 text-sm text-red-600">
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function removeFromWishlist(wishlistId) {
    if (confirm('Remove from wishlist?')) {
        fetch('<?php echo $baseUrl; ?>/api/wishlist.php?action=remove', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({wishlist_id: wishlistId})
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
