<?php
/**
 * Categories Page
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';
require_once __DIR__ . '/../../src/models/Category.php';
require_once __DIR__ . '/../../src/models/Product.php';

$auth = new Auth($db);
$categoryModel = new Category($db);
$productModel = new Product($db);
// $baseUrl provided by header (computed dynamically)

$categoryId = $_GET['id'] ?? null;

// Get categories for navigation
$categories = $categoryModel->getAllActive();

if ($categoryId) {
    // Get specific category
    $category = $categoryModel->getById($categoryId);
    
    if (!$category) {
        require_once __DIR__ . '/../../src/utils/Url.php';
        redirect('pages/products.php');
    }
    
    // Get products in category
    $products = $productModel->getByCategory($categoryId);
    $pageTitle = htmlspecialchars($category['name']) . ' - DB eCommerce';
} else {
    // Show all categories
    $products = null;
    $category = null;
    $pageTitle = 'Categories - DB eCommerce';
}
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8 pb-12">
    <h1 class="text-3xl font-bold mb-8">
        <?php echo $category ? htmlspecialchars($category['name']) : 'All Categories'; ?>
    </h1>
    
    <!-- Category List -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
        <?php foreach ($categories as $cat): ?>
            <a href="<?php echo $baseUrl; ?>/pages/categories.php?id=<?php echo $cat['id']; ?>" 
               class="bg-white rounded-lg p-6 text-center hover:shadow-lg transition-shadow border-2 <?php echo $categoryId == $cat['id'] ? 'border-primary' : 'border-gray-200'; ?>">
                <?php if ($cat['image']): ?>
                    <img src="<?php echo $uploadUrl . '/' . htmlspecialchars($cat['image']); ?>" 
                         alt="<?php echo htmlspecialchars($cat['name']); ?>" 
                         class="w-24 h-24 mx-auto mb-4 object-cover rounded">
                <?php endif; ?>
                <h3 class="font-bold text-lg"><?php echo htmlspecialchars($cat['name']); ?></h3>
                <p class="text-sm text-gray-600 mt-2"><?php echo htmlspecialchars($cat['description'] ?? ''); ?></p>
            </a>
        <?php endforeach; ?>
    </div>
    
    <!-- Products in Category -->
    <?php if ($categoryId && $products !== null): ?>
        <div>
            <h2 class="text-2xl font-bold mb-6">Products in <?php echo htmlspecialchars($category['name']); ?></h2>
            
            <?php if (empty($products)): ?>
                <div class="bg-white p-8 rounded-lg text-center">
                    <p class="text-gray-500">No products in this category yet.</p>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo $uploadUrl . '/' . htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="product-image">
                            <div class="product-info">
                                <a href="<?php echo $baseUrl; ?>/pages/product-detail.php?id=<?php echo $product['id']; ?>" 
                                   class="product-name">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                                <div class="product-price">
                                    $<?php echo number_format($product['price'], 2); ?>
                                </div>
                                <div class="product-seller">
                                    By <?php echo htmlspecialchars($product['seller_name']); ?>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="addToCart(<?php echo $product['id']; ?>, 1, true)" class="btn btn-primary flex-1 text-sm">
                                        🛒 Buy Now
                                    </button>
                                    <button onclick="addToWishlist(<?php echo $product['id']; ?>)" class="btn btn-outline flex-1 text-sm">
                                        ♡
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
