<?php
/**
 * Admin Categories Management
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';
require_once __DIR__ . '/../../src/utils/Url.php';

$auth = new Auth($db);

// Check if admin
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    redirect('auth/login.php');
}

// handle actions
$action = $_GET['action'] ?? '';
$editCategory = null;

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
    redirect('admin/categories.php');
}

if ($action === 'delete' && isset($_GET['id'])) {
    $did = intval($_GET['id']);
    $db->query("UPDATE categories SET deleted_at = NOW() WHERE id = $did");
    redirect('admin/categories.php');
}

if ($action === 'edit' && isset($_GET['id'])) {
    $eid = intval($_GET['id']);
    $editCategory = $db->query("SELECT * FROM categories WHERE id = $eid")->fetch_assoc();
}

// Get all categories
$categories = $db->query("SELECT * FROM categories WHERE deleted_at IS NULL ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Category Management - DB eCommerce Admin';
// baseUrl will be provided by header (BASE_URL constant)
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8 pb-12">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Category Management</h1>
        <a href="?action=add" class="btn btn-primary">Add New Category</a>
    </div>
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <div class="bg-white rounded-lg p-6 mb-8">
            <h2 class="text-xl font-bold mb-4"><?php echo $action === 'add' ? 'Add New Category' : 'Edit Category'; ?></h2>
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

                <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Create' : 'Update'; ?></button>
                <a href="admin/categories.php" class="btn btn-outline">Cancel</a>
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
                            <a href="?action=edit&id=<?php echo $category['id']; ?>" class="btn btn-outline text-sm mr-2">Edit</a>
                            <a href="?action=delete&id=<?php echo $category['id']; ?>" class="btn btn-outline text-sm text-red-600" onclick="return confirm('Delete category?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
