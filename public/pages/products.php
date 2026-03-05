<?php
/**
 * Products Listing Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';
require_once __DIR__ . '/../../src/models/Product.php';
require_once __DIR__ . '/../../src/models/Category.php';

$auth = new Auth($db);
$productModel = new Product($db);
$categoryModel = new Category($db);

// Get categories for filter
$categories = $categoryModel->getAllActive();

// Search and filter
$search = $_GET['search'] ?? '';
$categoryId = $_GET['category'] ?? null;
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Get products
if ($search) {
    $products = $productModel->search($search, $perPage, $offset);
} elseif ($categoryId) {
    $products = $productModel->getByCategory($categoryId, $perPage, $offset);
} else {
    $products = $productModel->getAllActive($perPage, $offset);
}

$pageTitle = 'Products - DB eCommerce';
// baseUrl provided by header
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Sidebar -->
        <aside class="bg-white p-6 rounded-lg shadow h-fit">
            <h3 class="text-lg font-bold mb-4">Filter</h3>
            
            <form method="GET" class="space-y-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block font-semibold mb-2">Search</label>
                    <input type="text" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search products...">
                </div>
                
                <!-- Category Filter -->
                <div>
                    <label for="category" class="block font-semibold mb-2">Category</label>
                    <select id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo $categoryId == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary w-full">Apply Filter</button>
            </form>
        </aside>
        
        <!-- Products -->
        <div class="md:col-span-3">
            <?php if (empty($products)): ?>
                <div class="bg-white p-8 rounded-lg text-center">
                    <p class="text-gray-500">No products found. Try adjusting your filters.</p>
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
                                    <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-primary flex-1 text-sm">
                                        Add
                                    </button>
                                    <button onclick="addToWishlist(<?php echo $product['id']; ?>)" class="btn btn-outline flex-1 text-sm">
                                        ♡
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <div class="flex justify-center gap-2 mt-8">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="btn btn-outline">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <span class="self-center">Page <?php echo $page; ?></span>
                    
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="btn btn-outline">
                        Next
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
