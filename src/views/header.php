<?php
/**
 * Header Component
 * Shared header for all pages
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null;
$userName = $_SESSION['name'] ?? null;

// Base URL for links and redirects.  We delegate to the Url helper so
// the same logic is used across the application (and always forces a
// "/public" segment, which avoids cases where the server's document root
// differs).
require_once __DIR__ . '/../utils/Url.php';
if (!defined('BASE_URL')) {
    define('BASE_URL', getBaseUrl());
}
$baseUrl = BASE_URL;

// compute URL for the shared uploads directory (outside of /public)
// we intentionally strip the "/public" segment from BASE_URL so that
// links point to e.g. http://host/DB-ecommerce/uploads/...
if (!defined('UPLOAD_URL')) {
    $calc = preg_replace('#/public$#','', BASE_URL);
    define('UPLOAD_URL', rtrim($calc, '/') . '/uploads');
}
$uploadUrl = UPLOAD_URL;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'DB Ecommerce'; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- expose base URL to client-side scripts -->
    <script>const BASE_URL = '<?php echo BASE_URL; ?>';</script>
    <script>const UPLOAD_URL = '<?php echo UPLOAD_URL; ?>';</script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css">
</head>
<body>

<?php if (isset($_SESSION['login_location_alert'])): ?>
    <div id="login-alert" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 fixed top-4 right-4 z-[9999] shadow-lg rounded cursor-pointer" role="alert" onclick="this.style.display='none'">
        <p class="font-bold">Đăng nhập thành công!</p>
        <p>Vị trí nhận diện: <?php echo htmlspecialchars($_SESSION['login_location_alert']); ?></p>
        <p class="text-xs text-green-600 mt-1 italic">Click để đóng</p>
    </div>
    <?php unset($_SESSION['login_location_alert']); ?>
    <script>
        setTimeout(function() {
            var alert = document.getElementById('login-alert');
            if (alert) alert.style.display = 'none';
        }, 10000);
    </script>
<?php endif; ?>

<header>
    <div class="header-container">
        <a href="<?php echo $baseUrl; ?>" class="logo">DB eCommerce</a>
        
        <nav>
            <a href="<?php echo $baseUrl; ?>/pages/home.php">Home</a>
            
            <!-- Categories Dropdown (only for customer) -->
            <?php if ($userRole === 'customer'): ?>
            <div class="relative group">
                <button class="flex items-center gap-1 hover:text-primary">
                    Categories
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                
                <div class="absolute left-0 mt-0 w-48 bg-white rounded-lg shadow-xl invisible group-hover:visible transition-all duration-200 z-50">
                    <!-- Categories will be loaded here -->
                </div>
            </div>
            <?php endif; ?>
            
            <a href="<?php echo $baseUrl; ?>/pages/products.php">Products</a>
            
            <!-- Role-specific navigation -->
            <?php if ($isLoggedIn): ?>
                <!-- Cart link for all roles -->
                <a href="<?php echo $baseUrl; ?>/pages/cart.php" class="relative">
                    Cart
                    <span id="cart-count" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">0</span>
                </a>
                
                <?php if ($userRole === 'customer'): ?>
                    <a href="<?php echo $baseUrl; ?>/pages/orders.php">Orders</a>
                    <a href="<?php echo $baseUrl; ?>/pages/wishlist.php">Wishlist</a>
                <?php elseif ($userRole === 'seller'): ?>
                    <!-- Seller dashboard dropdown with management links -->
                    <div class="relative group">
                        <button class="flex items-center gap-1 hover:text-primary">
                            Seller Dashboard
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="absolute left-0 mt-0 w-48 bg-white rounded-lg shadow-xl invisible group-hover:visible transition-all duration-200 z-50">
                            <a href="<?php echo $baseUrl; ?>/seller/products.php" class="block px-4 py-2 hover:bg-gray-100 text-sm">My Products</a>
                            <a href="<?php echo $baseUrl; ?>/seller/orders.php" class="block px-4 py-2 hover:bg-gray-100 text-sm">My Orders</a>
                            <a href="<?php echo $baseUrl; ?>/seller/categories.php" class="block px-4 py-2 hover:bg-gray-100 text-sm">Categories</a>
                        </div>
                    </div>
                <?php elseif ($userRole === 'admin'): ?>
                    <!-- Admin dashboard dropdown with management links -->
                    <div class="relative group">
                        <button class="flex items-center gap-1 hover:text-primary">
                            Admin Dashboard
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="absolute left-0 mt-0 w-48 bg-white rounded-lg shadow-xl invisible group-hover:visible transition-all duration-200 z-50">
                            <a href="<?php echo $baseUrl; ?>/admin/dashboard.php?section=users" class="block px-4 py-2 hover:bg-gray-100 text-sm">Users</a>
                            <a href="<?php echo $baseUrl; ?>/admin/dashboard.php?section=categories" class="block px-4 py-2 hover:bg-gray-100 text-sm">Categories</a>
                            <a href="<?php echo $baseUrl; ?>/admin/dashboard.php?section=banners" class="block px-4 py-2 hover:bg-gray-100 text-sm">Banners</a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <a href="<?php echo $baseUrl; ?>/pages/contact.php">Contact</a>
        </nav>
        
        <div class="user-menu">
            <?php if ($isLoggedIn): ?>
                <div class="relative group">
                    <button class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100 rounded">
                        <img src="<?php echo $baseUrl; ?>/assets/images/default-avatar.png" alt="Avatar" class="w-6 h-6 rounded-full">
                        <span class="text-sm"><?php echo htmlspecialchars($userName); ?></span>
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    
                    <div class="absolute right-0 mt-0 w-48 bg-white rounded-lg shadow-xl invisible group-hover:visible transition-all duration-200 z-50">
                        <a href="<?php echo $baseUrl; ?>/pages/account.php" class="block px-4 py-2 hover:bg-gray-100 text-sm">My Account</a>
                        <?php if ($userRole === 'admin'): ?>
                            <a href="<?php echo $baseUrl; ?>/admin/dashboard.php" class="block px-4 py-2 hover:bg-gray-100 text-sm">Admin Dashboard</a>
                        <?php endif; ?>
                        <hr>
                        <a href="<?php echo $baseUrl; ?>/auth/logout.php" class="block px-4 py-2 hover:bg-gray-100 text-sm text-red-600">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo $baseUrl; ?>/auth/login.php" class="btn btn-outline">Login</a>
                <a href="<?php echo $baseUrl; ?>/auth/register.php" class="btn btn-primary">Register</a>
            <?php endif; ?>
        </div>
    </div>
</header>
