<?php
/**
 * Admin Users Management
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

$auth = new Auth($db);

// Check if admin
require_once __DIR__ . '/../../src/utils/Url.php';
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    redirect('auth/login.php');
}

// handle add/delete actions (editing is not permitted)
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_user') {
    // double-check that the current user is an admin before processing
    if (!$auth->hasRole('admin')) {
        // unauthorized attempt; just redirect back
        redirect('admin/users.php');
    }
    // only handle creation; ignore any id field
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'customer';
    $status = $_POST['status'] ?? 'active';

    $stmt = $db->prepare("INSERT INTO users (name, email, role, status, password) VALUES (?, ?, ?, ?, ?)");
    $defaultPassword = password_hash('password123', PASSWORD_BCRYPT);
    $stmt->bind_param("sssss", $name, $email, $role, $status, $defaultPassword);
    $stmt->execute();
    redirect('admin/users.php');
}

if ($action === 'delete' && isset($_GET['id'])) {
    // only admins can delete users
    if ($auth->hasRole('admin')) {
        $deleteId = intval($_GET['id']);
        // don't delete admins
        $row = $db->query("SELECT role FROM users WHERE id = $deleteId")->fetch_assoc();
        if ($row && $row['role'] !== 'admin') {
            $db->query("UPDATE users SET deleted_at = NOW() WHERE id = $deleteId");
        }
    }
    redirect('admin/users.php');
}

// we no longer support edit forms
$editUser = null;

// fetch users after possible changes
$users = $db->query("SELECT * FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'User Management - DB eCommerce Admin';
// $baseUrl provided by header include
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8 pb-12">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">User Management</h1>
        <a href="?action=add" class="btn btn-primary">Add New User</a>
    </div>
    
    <?php if ($action === 'add'): ?>
        <div class="bg-white rounded-lg p-6 mb-8">
            <h2 class="text-xl font-bold mb-4">Add New User</h2>
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
                <a href="admin/users.php" class="btn btn-outline">Cancel</a>
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
                            <!-- no edit link allowed -->
                            <?php if ($user['role'] === 'admin'): ?>
                                <span class="text-gray-500 text-sm mr-2">—</span>
                            <?php endif; ?>
                            <?php if ($user['role'] !== 'admin'): ?>
                                <a href="?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-outline text-sm text-red-600" onclick="return confirm('Delete user?');">Delete</a>
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

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
