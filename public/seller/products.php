<?php
/**
 * Seller Products Management
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';
require_once __DIR__ . '/../../src/models/Product.php';
require_once __DIR__ . '/../../src/models/Category.php';

$auth = new Auth($db);
$productModel = new Product($db);
$categoryModel = new Category($db);

// Check if seller
require_once __DIR__ . '/../../src/utils/Url.php';
if (!$auth->isLoggedIn() || !$auth->hasRole('seller')) {
    redirect('auth/login.php');
}

$user = $auth->getCurrentUser();
$sellerId = $user['id'];

// handle actions
$action = $_GET['action'] ?? '';
$editProduct = null;

// Get categories for dropdown
$categories = $categoryModel->getAllActive();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_product') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $categoryId = intval($_POST['category_id'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $mainImagePath = null;

    // handle main image upload
    if (!empty($_FILES['main_image']['name']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $targetDir = __DIR__ . '/../../uploads/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $original = basename($_FILES['main_image']['name']);
        $ext = pathinfo($original, PATHINFO_EXTENSION);
        $filename = uniqid('prod_') . ($ext ? ".{$ext}" : '');
        $targetFile = $targetDir . $filename;
        if (move_uploaded_file($_FILES['main_image']['tmp_name'], $targetFile)) {
            $mainImagePath = $filename;
        } else {
            $_SESSION['product_error'] = 'Failed to upload main image';
        }
    }

    if ($name && $price > 0 && $categoryId > 0) {
        if ($id) {
            // Update existing
            $sql = "UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, status = ?";
            $params = [$name, $description, $price, $stock, $categoryId, $status];
            $types = ['s','s','d','i','i','s'];
            if ($mainImagePath) {
                $sql .= ", image = ?";
                $params[] = $mainImagePath;
                $types[] = 's';
            }
            $sql .= " WHERE id = ? AND seller_id = ?";
            $params[] = $id;
            $params[] = $sellerId;
            $types[] = 'i';
            $types[] = 'i';
            $stmt = $db->prepare($sql);
            $stmt->bind_param(implode('', $types), ...$params);
            if ($stmt->execute()) {
                // Success
            } else {
                $_SESSION['product_error'] = 'Failed to update product';
            }
        } else {
            // Insert new
            $stmt = $db->prepare("INSERT INTO products (name, description, price, stock, category_id, status, image, seller_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(implode('', ['s','s','d','i','i','s','s','i']), $name, $description, $price, $stock, $categoryId, $status, $mainImagePath, $sellerId);
            if ($stmt->execute()) {
                $id = $db->insert_id;
            } else {
                $_SESSION['product_error'] = 'Failed to create product';
            }
        }

        // Handle additional images if editing and no error
        if ($id && empty($_SESSION['product_error']) && !empty($_FILES['additional_images']['name'][0])) {
            foreach ($_FILES['additional_images']['name'] as $key => $filename) {
                if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $original = basename($filename);
                    $ext = pathinfo($original, PATHINFO_EXTENSION);
                    $imgFilename = uniqid('prod_img_') . ($ext ? ".{$ext}" : '');
                    $targetFile = $targetDir . $imgFilename;
                    if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$key], $targetFile)) {
                        $stmt = $db->prepare("INSERT INTO product_images (product_id, image_url, display_order) VALUES (?, ?, 0)");
                        $stmt->bind_param("is", $id, $imgFilename);
                        $stmt->execute();
                    }
                }
            }
        }
    } else {
        $_SESSION['product_error'] = 'Please fill all required fields correctly';
    }
    redirect('seller/products.php');
}

if ($action === 'delete' && isset($_GET['id'])) {
    $did = intval($_GET['id']);
    // Check ownership
    $stmt = $db->prepare("SELECT image FROM products WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("ii", $did, $sellerId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    if ($product) {
        // Delete main image
        if ($product['image']) {
            $filePath = __DIR__ . '/../../uploads/' . $product['image'];
            if (file_exists($filePath)) unlink($filePath);
        }
        // Delete additional images
        $stmt = $db->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
        $stmt->bind_param("i", $did);
        $stmt->execute();
        $images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach ($images as $img) {
            $filePath = __DIR__ . '/../../uploads/' . $img['image_url'];
            if (file_exists($filePath)) unlink($filePath);
        }
        $stmt = $db->prepare("DELETE FROM product_images WHERE product_id = ?");
        $stmt->bind_param("i", $did);
        $stmt->execute();
        // Soft delete product
        $stmt = $db->prepare("UPDATE products SET deleted_at = NOW() WHERE id = ? AND seller_id = ?");
        $stmt->bind_param("ii", $did, $sellerId);
        $stmt->execute();
    }
    redirect('seller/products.php');
}

if ($action === 'edit' && isset($_GET['id'])) {
    $eid = intval($_GET['id']);
    $editProduct = $productModel->getWithImages($eid);
    if (!$editProduct || $editProduct['seller_id'] != $sellerId) {
        redirect('seller/products.php');
    }
}

// Get seller products
$products = $productModel->getBySeller($sellerId);

$pageTitle = 'My Products - DB eCommerce';
// baseUrl available via header constant
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<?php if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!empty($_SESSION['product_error'])): ?>
    <div class="alert alert-danger mb-4">
        <?php echo htmlspecialchars($_SESSION['product_error']); unset($_SESSION['product_error']); ?>
    </div>
<?php endif; ?>

<div class="container mt-8 pb-12">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">My Products</h1>
        <a href="?action=add" class="btn btn-primary">Add New Product</a>
    </div>

    <?php if ($action === 'add' || $action === 'edit'): ?>
        <div class="bg-white rounded-lg p-6 mb-8">
            <h2 class="text-xl font-bold mb-4"><?php echo $action === 'add' ? 'Add New Product' : 'Edit Product'; ?></h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="save_product">
                <?php if ($editProduct): ?>
                    <input type="hidden" name="id" value="<?php echo $editProduct['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($editProduct['name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="w-full" rows="4"><?php echo htmlspecialchars($editProduct['description'] ?? ''); ?></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="price">Price ($)</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required value="<?php echo htmlspecialchars($editProduct['price'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="stock">Stock</label>
                        <input type="number" id="stock" name="stock" min="0" required value="<?php echo htmlspecialchars($editProduct['stock'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($editProduct && $editProduct['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="active" <?php echo (!$editProduct || $editProduct['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($editProduct && $editProduct['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="main_image">Main Image<?php echo $editProduct ? ' (leave blank to keep current)' : ''; ?></label>
                    <input type="file" id="main_image" name="main_image" accept="image/*" <?php echo $editProduct ? '' : 'required'; ?>>
                </div>

                <?php if ($editProduct): ?>
                <div class="form-group">
                    <label for="additional_images">Additional Images (optional)</label>
                    <input type="file" id="additional_images" name="additional_images[]" accept="image/*" multiple>
                    <small class="text-gray-600">Select multiple images to add more photos</small>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Create Product' : 'Update Product'; ?></button>
                <a href="seller/products.php" class="btn btn-outline">Cancel</a>
            </form>
        </div>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <div class="bg-white p-8 rounded-lg text-center">
            <p class="text-gray-500 mb-4">You haven't added any products yet.</p>
            <a href="?action=add" class="btn btn-primary">Add Your First Product</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($products as $product): ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <?php if ($product['image'] && file_exists(__DIR__ . '/../../uploads/' . $product['image'])): ?>
                        <img src="<?php echo UPLOAD_URL . '/' . htmlspecialchars($product['image']); ?>"
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="w-full h-40 object-cover">
                    <?php endif; ?>
                    <div class="p-4">
                        <h3 class="font-bold text-lg mb-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($product['category_name']); ?></p>
                        <p class="font-semibold text-primary mb-2">$<?php echo number_format($product['price'], 2); ?></p>
                        <p class="text-sm mb-4">Stock: <?php echo $product['stock']; ?> | Status: <span class="badge badge-<?php echo $product['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($product['status']); ?></span></p>
                        <div class="flex gap-2">
                            <a href="?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-outline text-sm flex-1">Edit</a>
                            <a href="?action=delete&id=<?php echo $product['id']; ?>" class="btn btn-outline text-sm flex-1 text-red-600" onclick="return confirm('Delete product?');">Delete</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
