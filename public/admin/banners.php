<?php
/**
 * Admin Banners Management
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

$auth = new Auth($db);

// Check if admin
require_once __DIR__ . '/../../src/utils/Url.php';
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    redirect('auth/login.php');
}

// handle add/edit/delete actions
$action = $_GET['action'] ?? '';
$editBanner = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save_banner') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : null;
        $title = trim($_POST['title'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
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
            $filename = uniqid('bnr_') . ($ext ? ".{$ext}" : '');
            $targetFile = $targetDir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = $filename;
            } else {
                // move failed; keep imagePath null so we don't insert garbage
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
            // image is required when creating
            if (!$imagePath) {
                $_SESSION['banner_error'] = 'Please choose an image for the banner';
            } else {
                $stmt = $db->prepare("INSERT INTO banners (title, image, display_order, is_active) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssii", $title, $imagePath, $display_order, $is_active);
                $stmt->execute();
            }
        }
    }
}

// handle delete after POST block so we can redirect appropriately
if ($action === 'delete' && isset($_GET['id'])) {
    $did = intval($_GET['id']);
    // optionally remove associated file
    $row = $db->query("SELECT image FROM banners WHERE id = $did")->fetch_assoc();
    if ($row && $row['image']) {
        $filePath = __DIR__ . '/../../uploads/' . $row['image'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    $db->query("DELETE FROM banners WHERE id = $did");
    redirect('admin/banners.php');
}

if ($action === 'edit' && isset($_GET['id'])) {
    $eid = intval($_GET['id']);
    $editBanner = $db->query("SELECT * FROM banners WHERE id = $eid")->fetch_assoc();
}

// Get all banners
$banners = $db->query("SELECT * FROM banners ORDER BY display_order ASC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Banner Management - DB eCommerce Admin';
// $baseUrl provided by header include
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<?php if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!empty($_SESSION['banner_error'])): ?>
    <div class="alert alert-danger mb-4">
        <?php echo htmlspecialchars($_SESSION['banner_error']); unset($_SESSION['banner_error']); ?>
    </div>
<?php endif; ?>

<div class="container mt-8 pb-12">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Banner Management</h1>
        <a href="?action=add" class="btn btn-primary">Add New Banner</a>
    </div>
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <div class="bg-white rounded-lg p-6 mb-8">
            <h2 class="text-xl font-bold mb-4"><?php echo $action === 'add' ? 'Add New Banner' : 'Edit Banner'; ?></h2>
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

                <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Create' : 'Update'; ?></button>
                <a href="admin/banners.php" class="btn btn-outline">Cancel</a>
            </form>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($banners as $banner): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <?php if ($banner['image'] && file_exists(__DIR__ . '/../../uploads/' . $banner['image'])): ?>
                    <div class="flex items-center justify-center bg-gray-100 h-40">
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
                            <a href="?action=edit&id=<?php echo $banner['id']; ?>" class="btn btn-outline text-sm flex-1">Edit</a>
                            <a href="?action=delete&id=<?php echo $banner['id']; ?>" class="btn btn-outline text-sm flex-1 text-red-600" onclick="return confirm('Delete banner?');">Delete</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
