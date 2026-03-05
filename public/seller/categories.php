<?php
/**
 * Seller Categories Management
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';
require_once __DIR__ . '/../../src/utils/Url.php';

$auth = new Auth($db);

// Check if seller
if (!$auth->isLoggedIn() || !$auth->hasRole('seller')) {
    redirect('auth/login.php');
}

// handle actions
$action = $_GET['action'] ?? '';
$editCategory = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_category') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $imagePath = null;

    // handle file upload if provided
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $targetDir = __DIR__ . '/../../uploads/';
        // ensure uploads directory exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        // create unique name to avoid overwrites
        $original = basename($_FILES['image']['name']);
        $ext = pathinfo($original, PATHINFO_EXTENSION);
        $filename = uniqid('cat_') . ($ext ? ".{$ext}" : '');
        $targetFile = $targetDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = $filename;
        } else {
            // move failed; keep imagePath null so we don't insert garbage
            $_SESSION['category_error'] = 'Failed to upload image file';
        }
    }

    if ($name !== '') {
        if ($id) {
            $sql = "UPDATE categories SET name = ?, description = ?";
            if ($imagePath) {
                $sql .= ", image = ?";
            }
            $sql .= " WHERE id = ?";
            $stmt = $db->prepare($sql);
            if ($imagePath) {
                $stmt->bind_param("sssi", $name, $description, $imagePath, $id);
            } else {
                $stmt->bind_param("ssi", $name, $description, $id);
            }
            $stmt->execute();
        } else {
            $stmt = $db->prepare("INSERT INTO categories (name, description, image) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $description, $imagePath);
            $stmt->execute();
        }
    }
    redirect('seller/categories.php');
}

if ($action === 'delete' && isset($_GET['id'])) {
    $did = intval($_GET['id']);
    // optionally remove associated file
    $row = $db->query("SELECT image FROM categories WHERE id = $did")->fetch_assoc();
    if ($row && $row['image']) {
        $filePath = __DIR__ . '/../../uploads/' . $row['image'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    $db->query("UPDATE categories SET deleted_at = NOW() WHERE id = $did");
    redirect('seller/categories.php');
}

if ($action === 'edit' && isset($_GET['id'])) {
    $eid = intval($_GET['id']);
    $editCategory = $db->query("SELECT * FROM categories WHERE id = $eid")->fetch_assoc();
}

// Get all categories
$categories = $db->query("SELECT * FROM categories WHERE deleted_at IS NULL ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Category Management - DB eCommerce Seller';
// baseUrl will be provided by header (BASE_URL constant)
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<?php if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!empty($_SESSION['category_error'])): ?>
    <div class="alert alert-danger mb-4">
        <?php echo htmlspecialchars($_SESSION['category_error']); unset($_SESSION['category_error']); ?>
    </div>
<?php endif; ?>

<div class="container mt-8 pb-12">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Category Management</h1>
        <a href="?action=add" class="btn btn-primary">Add New Category</a>
    </div>
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <div class="bg-white rounded-lg p-6 mb-8">
            <h2 class="text-xl font-bold mb-4"><?php echo $action === 'add' ? 'Add New Category' : 'Edit Category'; ?></h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
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

                <div class="form-group">
                    <label for="image">Image<?php echo $editCategory ? ' (leave blank to keep current)' : ''; ?></label>
                    <input type="file" id="image" name="image" <?php echo $editCategory ? '' : 'required'; ?>>
                </div>

                <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Create' : 'Update'; ?></button>
                <a href="seller/categories.php" class="btn btn-outline">Cancel</a>
            </form>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($categories as $category): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <?php if ($category['image'] && file_exists(__DIR__ . '/../../uploads/' . $category['image'])): ?>
                    <img src="<?php echo UPLOAD_URL . '/' . htmlspecialchars($category['image']); ?>"
                         alt="<?php echo htmlspecialchars($category['name']); ?>"
                         class="w-full h-32 object-cover">
                <?php endif; ?>
                <div class="p-4">
                    <h3 class="font-bold text-lg mb-2"><?php echo htmlspecialchars($category['name']); ?></h3>
                    <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars($category['description'] ?? ''); ?></p>
                    <div class="flex gap-2">
                        <a href="?action=edit&id=<?php echo $category['id']; ?>" class="btn btn-outline text-sm flex-1">Edit</a>
                        <a href="?action=delete&id=<?php echo $category['id']; ?>" class="btn btn-outline text-sm flex-1 text-red-600" onclick="return confirm('Delete category?');">Delete</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>