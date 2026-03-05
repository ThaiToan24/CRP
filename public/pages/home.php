<?php
/**
 * Home Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';
require_once __DIR__ . '/../../src/models/Product.php';
require_once __DIR__ . '/../../src/models/Category.php';

$auth = new Auth($db);
$productModel = new Product($db);
$categoryModel = new Category($db);

// Get banners
$bannerQuery = "SELECT * FROM banners WHERE is_active = 1 ORDER BY display_order ASC";
$banners = $db->query($bannerQuery)->fetch_all(MYSQLI_ASSOC);

// Get categories
$categories = $categoryModel->getAllActive();

// Get best-selling products
$bestSellers = $productModel->getBestSellers(8);

// Get all products (limit to first page)
$allProducts = $productModel->getAllActive(24, 0);
// Debug: uncomment to check
// echo "<!-- Total products: " . count($allProducts) . " -->";

$pageTitle = 'Home - DB eCommerce';
// $baseUrl removed; use BASE_URL constant from header

?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<main>
    <!-- Banners Section -->
    <section class="banners mt-6">
        <div class="container">
            <?php if (!empty($banners)): ?>
<div class="relative overflow-hidden rounded-lg h-96 flex items-center justify-center bg-gray-100">
                    <div class="flex transition-transform duration-500" id="bannerCarousel">
                        <?php foreach ($banners as $banner): ?>
                            <div class="min-w-full flex items-center justify-center">
                                <?php if ($banner['image'] && file_exists(__DIR__ . '/../../uploads/' . $banner['image'])): ?>
                                <img src="<?php echo $uploadUrl . '/' . htmlspecialchars($banner['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($banner['title']); ?>" 
                                     class="max-h-96 w-auto object-contain object-center">
                            <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Categories Section -->
    <section class="categories mt-12">
        <div class="container">
            <h2 class="text-2xl font-bold mb-6">Shop by Categories</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <?php foreach ($categories as $category): ?>
                    <a href="<?php echo BASE_URL; ?>/pages/categories.php?id=<?php echo $category['id']; ?>" 
                       class="bg-white p-4 rounded-lg text-center hover:shadow-lg transition-shadow">
                        <?php if ($category['image']): ?>
                            <img src="<?php echo $uploadUrl . '/' . htmlspecialchars($category['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                 class="w-20 h-20 mx-auto mb-2 object-cover rounded">
                        <?php endif; ?>
                        <p class="font-semibold text-sm"><?php echo htmlspecialchars($category['name']); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Best Sellers Section -->
    <section class="best-sellers mt-12">
        <div class="container">
            <h2 class="text-2xl font-bold mb-6">Best Sellers</h2>
            <div class="product-grid">
                <?php foreach ($bestSellers as $product): ?>
                    <div class="product-card">
                        <img src="<?php echo $uploadUrl . '/' . htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image">
                        <div class="product-info">
                            <a href="<?php echo BASE_URL; ?>/pages/product-detail.php?id=<?php echo $product['id']; ?>" 
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
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- All Products Section -->
    <section class="all-products mt-12 pb-12">
        <div class="container">
            <h2 class="text-2xl font-bold mb-6">All Products</h2>
            <div class="product-grid">
                <?php foreach ($allProducts as $product): ?>
                    <div class="product-card">
                        <img src="<?php echo $uploadUrl . '/' . htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image">
                        <div class="product-info">
                            <a href="<?php echo BASE_URL; ?>/pages/product-detail.php?id=<?php echo $product['id']; ?>" 
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
            
            <div class="text-center mt-8">
                <a href="<?php echo $baseUrl; ?>/pages/products.php" class="btn btn-outline">
                    View All Products
                </a>
            </div>
        </div>
    </section>
</main>

<script>
    // Simple banner carousel
    const carousel = document.getElementById('bannerCarousel');
    let currentSlide = 0;
    
    if (carousel) {
        setInterval(() => {
            const slides = carousel.children.length;
            currentSlide = (currentSlide + 1) % slides;
            carousel.style.transform = `translateX(-${currentSlide * 100}%)`;
        }, 5000);
    }
</script>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
