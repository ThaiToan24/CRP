<?php
/**
 * Product Detail Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';
require_once __DIR__ . '/../../src/models/Product.php';
require_once __DIR__ . '/../../src/models/Category.php';

$auth = new Auth($db);
$productModel = new Product($db);
$categoryModel = new Category($db);

$productId = $_GET['id'] ?? null;

require_once __DIR__ . '/../../src/utils/Url.php';
if (!$productId) {
    redirect('pages/products.php');
}


// Get product with images
$product = $productModel->getWithImages($productId);

require_once __DIR__ . '/../../src/utils/Url.php';
if (!$product) {
    redirect('pages/products.php');
}


// Get reviews
$reviewQuery = "SELECT r.*, u.name as customer_name FROM reviews r 
                JOIN users u ON r.customer_id = u.id 
                WHERE r.product_id = ? AND r.deleted_at IS NULL 
                ORDER BY r.created_at DESC";
$stmt = $db->prepare($reviewQuery);
$stmt->bind_param("i", $productId);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get average rating
$ratingQuery = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE product_id = ? AND deleted_at IS NULL";
$stmt = $db->prepare($ratingQuery);
$stmt->bind_param("i", $productId);
$stmt->execute();
$ratingData = $stmt->get_result()->fetch_assoc();

$pageTitle = htmlspecialchars($product['name']) . ' - DB eCommerce';
// $baseUrl provided by header include
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8 pb-12">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Product Images -->
        <div>
            <div class="bg-white rounded-lg p-4 mb-4">
                <img src="<?php echo $uploadUrl . '/' . htmlspecialchars($product['image']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     id="mainImage" class="w-full h-96 object-cover rounded">
            </div>
            
            <!-- Thumbnail images -->
            <?php if (!empty($product['images'])): ?>
                <div class="flex gap-2">
                    <?php foreach ($product['images'] as $img): ?>
                        <img src="<?php echo $uploadUrl . '/' . htmlspecialchars($img['image_url']); ?>" 
                             alt="thumb" class="w-20 h-20 object-cover rounded cursor-pointer border-2 border-transparent hover:border-primary"
                             onclick="document.getElementById('mainImage').src = this.src">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Product Details -->
        <div class="bg-white rounded-lg p-6">
            <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <div class="flex items-center gap-4 mb-4">
                <div class="flex items-center gap-1 text-yellow-400">
                    <?php 
                    $avgRating = $ratingData['avg_rating'] ?? 0;
                    $totalReviews = $ratingData['total_reviews'] ?? 0;
                    
                    for ($i = 1; $i <= 5; $i++): 
                    ?>
                        <span><?php echo $i <= round($avgRating) ? '★' : '☆'; ?></span>
                    <?php endfor; ?>
                </div>
                <span class="text-sm text-gray-600">(<?php echo $totalReviews; ?> reviews)</span>
            </div>
            
            <div class="text-3xl font-bold text-primary mb-4">
                $<?php echo number_format($product['price'], 2); ?>
            </div>
            
            <div class="bg-gray-100 p-3 rounded mb-4">
                <p><strong>In Stock:</strong> <?php echo $product['stock']; ?> items</p>
                <p><strong>Seller:</strong> <?php echo htmlspecialchars($product['seller_name'] ?? 'Unknown'); ?></p>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
            </div>
            
            <div class="flex gap-3 mb-6">
                <button onclick="addToCart(<?php echo $product['id']; ?>, 1, true)" class="btn btn-primary flex-1 py-3">
                    🛒 Buy Now
                </button>
                <button onclick="addToWishlist(<?php echo $product['id']; ?>)" class="btn btn-outline flex-1 py-3">
                    Add to Wishlist
                </button>
            </div>
            
            <div class="mt-6 pt-6 border-t">
                <h3 class="font-bold mb-2">Description</h3>
                <p class="text-gray-700"><?php echo htmlspecialchars($product['description']); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Reviews Section -->
    <div class="mt-12 bg-white rounded-lg p-6">
        <h2 class="text-2xl font-bold mb-6">Customer Reviews</h2>
        
        <?php if (empty($reviews)): ?>
            <p class="text-gray-500">No reviews yet. Be the first to review this product!</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($reviews as $review): ?>
                    <div class="border-b pb-4 last:border-b-0">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="font-bold"><?php echo htmlspecialchars($review['customer_name']); ?></p>
                                <div class="flex gap-1 text-yellow-400">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span><?php echo $i <= $review['rating'] ? '★' : '☆'; ?></span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <span class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                        </div>
                        <p class="text-gray-700"><?php echo htmlspecialchars($review['comment']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
