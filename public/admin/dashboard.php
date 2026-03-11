<?php
/**
 * Admin Dashboard
 * Main admin control panel
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

$auth = new Auth($db);

// Check if admin
require_once __DIR__ . '/../../src/utils/Url.php';
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    redirect('auth/login.php');
}

// handle management sections (users/categories/banners) when requested
$section = $_GET['section'] ?? '';
action:
$action = $_GET['action'] ?? '';

// user operations
$editUser = null;
if ($section === 'users') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_user') {
        // same logic as users.php
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'customer';
        $status = $_POST['status'] ?? 'active';

        $stmt = $db->prepare("INSERT INTO users (name, email, role, status, password) VALUES (?, ?, ?, ?, ?)");
        $defaultPassword = password_hash('password123', PASSWORD_BCRYPT);
        $stmt->bind_param("sssss", $name, $email, $role, $status, $defaultPassword);
        $stmt->execute();
        redirect('admin/dashboard.php?section=users');
    }
    if ($action === 'delete' && isset($_GET['id'])) {
        $deleteId = intval($_GET['id']);
        $row = $db->query("SELECT role FROM users WHERE id = $deleteId")->fetch_assoc();
        if ($row && $row['role'] !== 'admin') {
            $db->query("UPDATE users SET deleted_at = NOW() WHERE id = $deleteId");
        }
        redirect('admin/dashboard.php?section=users');
    }
    $users = $db->query("SELECT * FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
}

// category operations
$editCategory = null;
if ($section === 'categories') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_category') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : null;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($name !== '') {
            if ($id) {
                $stmt = $db->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $description, $id);
                $stmt->execute();
            } else {
                $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $description);
                $stmt->execute();
            }
        }
        redirect('admin/dashboard.php?section=categories');
    }
    if ($action === 'delete' && isset($_GET['id'])) {
        $did = intval($_GET['id']);
        $db->query("UPDATE categories SET deleted_at = NOW() WHERE id = $did");
        redirect('admin/dashboard.php?section=categories');
    }
    if ($action === 'edit' && isset($_GET['id'])) {
        $eid = intval($_GET['id']);
        $editCategory = $db->query("SELECT * FROM categories WHERE id = $eid")->fetch_assoc();
    }
    $categories = $db->query("SELECT * FROM categories WHERE deleted_at IS NULL ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
}

// banner operations
$editBanner = null;
if ($section === 'banners') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_banner') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : null;
        $title = trim($_POST['title'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $imagePath = null;
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $targetDir = __DIR__ . '/../../uploads/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $original = basename($_FILES['image']['name']);
            $ext = pathinfo($original, PATHINFO_EXTENSION);
            $filename = uniqid('bnr_') . ($ext ? ".{$ext}" : '');
            $targetFile = $targetDir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = $filename;
            } else {
                $_SESSION['banner_error'] = 'Failed to upload image file';
            }
        }
        if ($id) {
            $sql = "UPDATE banners SET title = ?, display_order = ?, is_active = ?";
            if ($imagePath) {
                $sql .= ", image = ?";
            }
            $sql .= " WHERE id = ?";
            $stmt = $db->prepare($sql);
            if ($imagePath) {
                $stmt->bind_param("siisi", $title, $display_order, $is_active, $imagePath, $id);
            } else {
                $stmt->bind_param("siii", $title, $display_order, $is_active, $id);
            }
            $stmt->execute();
        } else {
            if (!$imagePath) {
                $_SESSION['banner_error'] = 'Please choose an image for the banner';
            } else {
                $stmt = $db->prepare("INSERT INTO banners (title, image, display_order, is_active) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssii", $title, $imagePath, $display_order, $is_active);
                $stmt->execute();
            }
        }
        redirect('admin/dashboard.php?section=banners');
    }
    if ($action === 'delete' && isset($_GET['id'])) {
        $did = intval($_GET['id']);
        $row = $db->query("SELECT image FROM banners WHERE id = $did")->fetch_assoc();
        if ($row && $row['image']) {
            $filePath = __DIR__ . '/../../uploads/' . $row['image'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        $db->query("DELETE FROM banners WHERE id = $did");
        redirect('admin/dashboard.php?section=banners');
    }
    if ($action === 'edit' && isset($_GET['id'])) {
        $eid = intval($_GET['id']);
        $editBanner = $db->query("SELECT * FROM banners WHERE id = $eid")->fetch_assoc();
    }
    // get current banners
    $banners = $db->query("SELECT * FROM banners ORDER BY display_order ASC")->fetch_all(MYSQLI_ASSOC);
}

// Get system statistics
// various tables may or may not have a soft‑delete column depending on how the database
// was initialised, so check for the column before referencing it in WHERE clauses.
function countTable($db, $table) {
    $sql = "SELECT COUNT(*) as count FROM $table";
    $colCheck = $db->query("SHOW COLUMNS FROM $table LIKE 'deleted_at'");
    if ($colCheck && $colCheck->num_rows > 0) {
        $sql .= " WHERE deleted_at IS NULL";
    }
    return $db->query($sql)->fetch_assoc()['count'];
}

$userCount = countTable($db, 'users');
$productCount = countTable($db, 'products');
$orderCount = countTable($db, 'orders');
$totalRevenue = $db->query("SELECT SUM(total_price) as total FROM orders WHERE payment_status = 'paid'")->fetch_assoc()['total'] ?? 0;

$pageTitle = 'Admin Dashboard - DB eCommerce';
// baseUrl comes from header (BASE_URL constant)
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8 pb-12">
    <h1 class="text-3xl font-bold mb-8">Admin Dashboard</h1>
    
    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg p-6 shadow">
            <h3 class="text-gray-600 text-sm font-semibold mb-2">Total Users</h3>
            <p class="text-3xl font-bold text-primary"><?php echo $userCount; ?></p>
        </div>
        
        <div class="bg-white rounded-lg p-6 shadow">
            <h3 class="text-gray-600 text-sm font-semibold mb-2">Total Products</h3>
            <p class="text-3xl font-bold text-primary"><?php echo $productCount; ?></p>
        </div>
        
        <div class="bg-white rounded-lg p-6 shadow">
            <h3 class="text-gray-600 text-sm font-semibold mb-2">Total Orders</h3>
            <p class="text-3xl font-bold text-primary"><?php echo $orderCount; ?></p>
        </div>
        
        <div class="bg-white rounded-lg p-6 shadow">
            <h3 class="text-gray-600 text-sm font-semibold mb-2">Total Revenue</h3>
            <p class="text-3xl font-bold text-primary">$<?php echo number_format($totalRevenue, 2); ?></p>
        </div>
    </div>
    
    <!-- Quick Actions (navigate to dashboard sections) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg p-6 shadow">
            <h3 class="font-bold text-lg mb-4">User Management</h3>
            <a href="<?php echo $baseUrl; ?>/admin/dashboard.php?section=users" class="btn btn-primary w-full">
                Manage Users
            </a>
        </div>
        
        <div class="bg-white rounded-lg p-6 shadow">
            <h3 class="font-bold text-lg mb-4">Category Management</h3>
            <a href="<?php echo $baseUrl; ?>/admin/dashboard.php?section=categories" class="btn btn-primary w-full">
                Manage Categories
            </a>
        </div>
        
        <div class="bg-white rounded-lg p-6 shadow">
            <h3 class="font-bold text-lg mb-4">Banner Management</h3>
            <a href="<?php echo $baseUrl; ?>/admin/dashboard.php?section=banners" class="btn btn-primary w-full">
                Manage Banners
            </a>
        </div>
    </div>
    
    <!-- Management Sections -->
    <?php if ($section === 'users'): ?>
        <div class="mt-12 bg-white rounded-lg p-6 shadow">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold">User Management</h2>
                <a href="?section=users&action=add" class="btn btn-primary">Add New User</a>
            </div>
            <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
                <div class="bg-white rounded-lg p-6 mb-8">
                    <h3 class="text-xl font-bold mb-4">Add New User</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="save_user">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" class="form-control">
                                <option value="customer">Customer</option>
                                <option value="seller">Seller</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="blocked">Blocked</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Create</button>
                        <a href="?section=users" class="btn btn-outline">Cancel</a>
                    </form>
                </div>
            <?php endif; ?>
            <div class="bg-white rounded-lg overflow-hidden">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span class="badge badge-primary"><?php echo ucfirst($user['role']); ?></span></td>
                                <td><span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['role'] !== 'admin'): ?>
                                        <a href="?section=users&action=delete&id=<?php echo $user['id']; ?>" class="btn btn-outline text-sm text-red-600" onclick="return confirm('Delete user?');">Delete</a>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">No action</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($section === 'categories'): ?>
        <div class="mt-12 bg-white rounded-lg p-6 shadow">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold">Category Management</h2>
                <a href="?section=categories&action=add" class="btn btn-primary">Add New Category</a>
            </div>
            <?php if (isset($_GET['action']) && ($_GET['action'] === 'add' || $_GET['action'] === 'edit')): ?>
                <div class="bg-white rounded-lg p-6 mb-8">
                    <h3 class="text-xl font-bold mb-4"><?php echo $_GET['action'] === 'add' ? 'Add New Category' : 'Edit Category'; ?></h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="save_category">
                        <?php if ($editCategory): ?>
                            <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($editCategory['name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="w-full" rows="3"><?php echo htmlspecialchars($editCategory['description'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><?php echo $_GET['action'] === 'add' ? 'Create' : 'Update'; ?></button>
                        <a href="?section=categories" class="btn btn-outline">Cancel</a>
                    </form>
                </div>
            <?php endif; ?>
            <div class="bg-white rounded-lg overflow-hidden">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($category['description'] ?? '', 0, 50)) . '...'; ?></td>
                                <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                <td>
                                    <a href="?section=categories&action=edit&id=<?php echo $category['id']; ?>" class="btn btn-outline text-sm mr-2">Edit</a>
                                    <a href="?section=categories&action=delete&id=<?php echo $category['id']; ?>" class="btn btn-outline text-sm text-red-600" onclick="return confirm('Delete category?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($section === 'banners'): ?>
        <?php if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!empty($_SESSION['banner_error'])): ?>
            <div class="alert alert-danger mb-4">
                <?php echo htmlspecialchars($_SESSION['banner_error']); unset($_SESSION['banner_error']); ?>
            </div>
        <?php endif; ?>
        <div class="mt-12 bg-white rounded-lg p-6 shadow">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold">Banner Management</h2>
                <a href="?section=banners&action=add" class="btn btn-primary">Add New Banner</a>
            </div>
            <?php if (isset($_GET['action']) && ($_GET['action'] === 'add' || $_GET['action'] === 'edit')): ?>
                <div class="bg-white rounded-lg p-6 mb-8">
                    <h3 class="text-xl font-bold mb-4"><?php echo $_GET['action'] === 'add' ? 'Add New Banner' : 'Edit Banner'; ?></h3>
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="save_banner">
                        <?php if ($editBanner): ?>
                            <input type="hidden" name="id" value="<?php echo $editBanner['id']; ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" required
                                   value="<?php echo htmlspecialchars($editBanner['title'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="image">Image<?php echo $editBanner ? ' (leave blank to keep current)' : ''; ?></label>
                            <input type="file" id="image" name="image" <?php echo $editBanner ? '' : 'required'; ?>>
                        </div>
                        <div class="form-group">
                            <label for="display_order">Order</label>
                            <input type="number" id="display_order" name="display_order" value="<?php echo htmlspecialchars($editBanner['display_order'] ?? 0); ?>" class="w-20">
                        </div>
                        <div class="form-group">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" <?php echo (!isset($editBanner) || $editBanner['is_active']) ? 'checked' : ''; ?> class="mr-2">
                                Active
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary"><?php echo $_GET['action'] === 'add' ? 'Create' : 'Update'; ?></button>
                        <a href="?section=banners" class="btn btn-outline">Cancel</a>
                    </form>
                </div>
            <?php endif; ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($banners as $banner): ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <?php if ($banner['image'] && file_exists(__DIR__ . '/../../uploads/' . $banner['image'])): ?>
                            <div class="h-40 flex items-center justify-center bg-gray-100">
                                <img src="<?php echo UPLOAD_URL . '/' . htmlspecialchars($banner['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($banner['title']); ?>" 
                                     class="max-h-full w-auto object-contain object-center">
                            </div>
                        <?php endif; ?>
                        <div class="p-4">
                            <h3 class="font-bold text-lg mb-2"><?php echo htmlspecialchars($banner['title']); ?></h3>
                            <p class="text-sm text-gray-600 mb-4">Order: <?php echo $banner['display_order']; ?></p>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" <?php echo $banner['is_active'] ? 'checked' : ''; ?> class="mr-2" disabled>
                                    <span class="text-sm">Active</span>
                                </label>
                                <div class="flex gap-2">
                                    <a href="?section=banners&action=edit&id=<?php echo $banner['id']; ?>" class="btn btn-outline text-sm flex-1">Edit</a>
                                    <a href="?section=banners&action=delete&id=<?php echo $banner['id']; ?>" class="btn btn-outline text-sm flex-1 text-red-600" onclick="return confirm('Delete banner?');">Delete</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Logins -->
    <div class="mt-12 bg-white rounded-lg p-6 shadow">
        <h3 class="font-bold text-lg mb-4">Recent Logins</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-100 border-b">
                        <th class="p-4 rounded-tl-lg font-semibold text-gray-700">User</th>
                        <th class="p-4 font-semibold text-gray-700">IP Address</th>
                        <th class="p-4 font-semibold text-gray-700">Location</th>
                        <th class="p-4 rounded-tr-lg font-semibold text-gray-700">Login Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // fetch only the most recent login per user
                    $sql =
                        "SELECT lh.*, u.name, u.email FROM login_history lh " .
                        "JOIN users u ON lh.user_id = u.id " .
                        "JOIN (SELECT user_id, MAX(login_time) AS max_time FROM login_history GROUP BY user_id) latest " .
                        "ON latest.user_id = lh.user_id AND latest.max_time = lh.login_time " .
                        "ORDER BY lh.login_time DESC LIMIT 10";
                    $logins = $db->query($sql);
                    if ($logins->num_rows > 0):
                        while($row = $logins->fetch_assoc()):
                    ?>
                    <tr class="border-b">
                        <td class="p-4">
                            <span class="font-semibold"><?php echo htmlspecialchars($row['name']); ?></span><br>
                            <span class="text-sm text-gray-500"><?php echo htmlspecialchars($row['email']); ?></span>
                        </td>
                        <td class="p-4 font-mono text-sm"><?php echo htmlspecialchars($row['ip_address']); ?></td>
                        <td class="p-4"><?php echo htmlspecialchars($row['location']); ?></td>
                        <td class="p-4 text-sm text-gray-600"><?php echo date('M d, Y H:i', strtotime($row['login_time'])); ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="4" class="p-4 text-center text-gray-500">No login records found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <!-- AI Anomaly Detection Alerts -->
    <div class="mt-8 bg-red-50 border-l-4 border-red-500 rounded-lg p-6 shadow">
        <h3 class="font-bold text-lg text-red-700 mb-4">🚨 AI Suspicious Login Alerts</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left bg-white">
                <thead>
                    <tr class="bg-red-100 border-b border-red-200">
                        <th class="p-4 rounded-tl-lg font-semibold text-red-800">User</th>
                        <th class="p-4 font-semibold text-red-800">Device / IP</th>
                        <th class="p-4 font-semibold text-red-800">Time / Location</th>
                        <th class="p-4 rounded-tr-lg font-semibold text-red-800">Anomaly Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sqlAnomaly = 
                        "SELECT lf.*, u.name, u.email FROM login_fingerprints lf " .
                        "JOIN users u ON lf.user_id = u.id " .
                        "WHERE lf.is_anomaly = 1 " .
                        "ORDER BY lf.login_time DESC LIMIT 10";
                    $anomalies = $db->query($sqlAnomaly);
                    if ($anomalies && $anomalies->num_rows > 0):
                        while($row = $anomalies->fetch_assoc()):
                    ?>
                    <tr class="border-b border-red-100">
                        <td class="p-4">
                            <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($row['name']); ?></span><br>
                            <span class="text-sm text-gray-600"><?php echo htmlspecialchars($row['email']); ?></span>
                        </td>
                        <td class="p-4">
                            <span class="font-mono text-sm block"><?php echo htmlspecialchars($row['ip_address']); ?></span>
                            <span class="text-xs text-gray-500" title="<?php echo htmlspecialchars($row['user_agent']); ?>"><?php echo htmlspecialchars($row['device_type']); ?></span>
                        </td>
                        <td class="p-4">
                            <div class="text-sm text-gray-700"><?php echo date('M d, Y H:i', strtotime($row['login_time'])); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['geo_location']); ?> • <?php echo htmlspecialchars($row['time_category']); ?></div>
                        </td>
                        <td class="p-4 font-mono font-bold text-red-600">
                            <?php echo number_format($row['anomaly_score'], 4); ?>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="4" class="p-4 text-center text-gray-500">No anomalous logins detected</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Coming Soon -->
    <div class="mt-8 bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded">
        <h3 class="font-bold text-lg mb-2">🚀 Features Coming Soon</h3>
        <ul class="space-y-2 text-gray-700">
            <li>✓ Full user management interface</li>
            <li>✓ Product category management</li>
            <li>✓ Banner upload and management</li>
            <li>✓ Sales analytics and reports</li>
            <li>✓ Transaction fee configuration</li>
            <li>✓ System settings management</li>
        </ul>
    </div>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
