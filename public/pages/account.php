<?php
/**
 * Account/Profile Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

$auth = new Auth($db);

// Redirect if not logged in
require_once __DIR__ . '/../../src/utils/Url.php';
if (!$auth->isLoggedIn()) {
    redirect('auth/login.php');
}

$user = $auth->getCurrentUser();
$userId = $user['id'];
$success = '';
$error = '';

// Get user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userDetails = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    if (empty($name) || empty($phone)) {
        $error = 'Name and phone are required';
    } else {
        $stmt = $db->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $phone, $userId);
        
        if ($stmt->execute()) {
            $_SESSION['name'] = $name;
            $success = 'Profile updated successfully';
            // Refresh user details
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $userDetails = $stmt->get_result()->fetch_assoc();
        } else {
            $error = 'Failed to update profile';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (!password_verify($currentPassword, $userDetails['password'])) {
        $error = 'Current password is incorrect';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters';
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        
        if ($stmt->execute()) {
            $success = 'Password changed successfully';
        } else {
            $error = 'Failed to change password';
        }
    }
}

$pageTitle = 'My Account - DB eCommerce';
// $baseUrl provided by header include
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8 pb-12">
    <h1 class="text-3xl font-bold mb-8">My Account</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Sidebar Menu -->
        <aside class="bg-white rounded-lg p-6 h-fit">
            <nav class="space-y-2">
                <a href="#profile" class="block px-4 py-2 hover:bg-gray-100 rounded">Profile</a>
                <a href="#password" class="block px-4 py-2 hover:bg-gray-100 rounded">Change Password</a>
                <a href="<?php echo $baseUrl; ?>/pages/orders.php" class="block px-4 py-2 hover:bg-gray-100 rounded">My Orders</a>
                <a href="<?php echo $baseUrl; ?>/pages/wishlist.php" class="block px-4 py-2 hover:bg-gray-100 rounded">Wishlist</a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <div class="md:col-span-2 space-y-6">
            <!-- Success/Error Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Profile Section -->
            <div id="profile" class="bg-white rounded-lg p-6">
                <h2 class="text-2xl font-bold mb-6">Profile Information</h2>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userDetails['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userDetails['email']); ?>" disabled>
                        <small class="text-gray-500">Email cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($userDetails['phone']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Account Type</label>
                        <input type="text" value="<?php echo ucfirst($userDetails['role']); ?>" disabled>
                        <small class="text-gray-500">Cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Member Since</label>
                        <input type="text" value="<?php echo date('M d, Y', strtotime($userDetails['created_at'])); ?>" disabled>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-full">Save Changes</button>
                </form>
            </div>
            
            <!-- Password Section -->
            <div id="password" class="bg-white rounded-lg p-6">
                <h2 class="text-2xl font-bold mb-6">Change Password</h2>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-full">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
