<?php
/**
 * Register Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

$auth = new Auth($db);
$error = '';
$success = '';

// If already logged in, redirect
require_once __DIR__ . '/../../src/utils/Url.php';
if ($auth->isLoggedIn()) {
    redirect('pages/home.php');
}

// Handle register form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($phone)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $result = $auth->register($email, $password, $name, $phone, $role);
        
        if ($result['success']) {
            $success = 'Registration successful! Please login with your credentials.';
            // Redirect to login after 2 seconds
            header('Refresh: 2; url=login.php');
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Register - DB eCommerce';
// $baseUrl provided by header include
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8">
    <div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold mb-6 text-center">Create Your Account</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label for="role">Account Type</label>
                <select id="role" name="role" required>
                    <option value="customer" selected>Customer</option>
                    <option value="seller">Seller</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary w-full">Create Account</button>
        </form>
        
        <div class="text-center mt-6">
            <p>Already have an account? <a href="login.php" class="text-primary font-bold">Login here</a></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
