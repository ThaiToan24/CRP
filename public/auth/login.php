<?php
/**
 * Login Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

$auth = new Auth($db);
$error = '';
$success = '';

// use Url helper for consistent base URL including /public
require_once __DIR__ . '/../../src/utils/Url.php';
$baseUrl = getBaseUrl();

// If already logged in, redirect
if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
    if ($user['role'] === 'admin') {
        redirect('admin/dashboard.php');
    } elseif ($user['role'] === 'seller') {
        redirect('seller/dashboard.php');
    } else {
        redirect('pages/home.php');
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = $auth->login($email, $password);
    
        if ($result['success']) {
        $user = $result['user'];
        if ($user['role'] === 'admin') {
            redirect('admin/dashboard.php?section=users');
        } elseif ($user['role'] === 'seller') {
            redirect('seller/dashboard.php');
        } else {
            redirect('pages/home.php');
        }
    } else {
        $error = $result['message'];
    }
}

$pageTitle = 'Login - DB eCommerce';
// $baseUrl already set by top-of-file logic
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8">
    <div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold mb-6 text-center">Login to DB eCommerce</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-full">Login</button>
        </form>
        
        <div class="text-center mt-6">
            <p>Don't have an account? <a href="register.php" class="text-primary font-bold">Register here</a></p>
        </div>
        
       
    </div>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
