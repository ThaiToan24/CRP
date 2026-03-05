# Developer Quick Reference

## 🚀 Quick Start

```bash
# 1. Start XAMPP (Apache + MySQL)
# 2. Visit: http://localhost/phpmyadmin
# 3. Import: database/schema.sql
# 4. Visit: http://localhost/DB-ecommerce/public/
# 5. Login: admin@gmail.com / admin123
```

---

## 📍 Key Files to Know

### Configuration
```
config/database.php    - Database connection
                       - Edit credentials here if needed
```

### Authentication
```
src/utils/Auth.php     - Core authentication class
  Methods:
  - register()         - Create new user
  - login()            - Verify credentials
  - logout()           - Destroy session
  - isLoggedIn()       - Check authentication
  - hasRole()          - Check user permission
```

### Models
```
src/models/Product.php - All product queries
  Methods:
  - getAllActive()     - All products with sellers/categories
  - getByCategory()    - Filter by category
  - getBySeller()      - Filter by seller
  - getBestSellers()   - Popular products
  - search()           - Full-text search
  - getWithImages()    - Get product + images

src/models/Category.php
  Methods:
  - getAllActive()     - All categories

src/models/Order.php
  Methods:
  - getByCustomer()    - Customer's orders
  - getBySeller()      - Seller's orders
  - getWithItems()     - Order + items
```

### Views
```
src/views/header.php   - Navigation & user menu
                       - Include on every page: include 'header.php'

src/views/footer.php   - Footer with links
                       - Include at end: include 'footer.php'
```

### JavaScript
```
assets/js/main.js      - Client utilities
  Functions:
  - addToCart()        - Add product to cart (AJAX)
  - addToWishlist()    - Save to wishlist (AJAX)
  - updateCartCount()  - Refresh cart badge
  - formatCurrency()   - Format money amounts
  - showAlert()        - Display notification
```

### API Endpoints
```
api/cart.php           - Cart management
  GET  ?action=count   - { count: 5 }
  POST ?action=add     - { product_id, quantity }
  POST ?action=update  - { cart_id, quantity }
  POST ?action=remove  - { cart_id }

api/wishlist.php       - Wishlist management
  POST ?action=add     - { product_id }
  POST ?action=remove  - { wishlist_id }
```

---

## 🌐 Page Structure Template

Every page should follow this structure:

```php
<?php
// 1. Include config & classes
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/utils/Auth.php';

// 2. Check permission if needed
$auth = new Auth($db);
if (!$auth->isLoggedIn()) {
    header('Location: /DB-ecommerce/auth/login.php');
    exit();
}

// 3. Get data
$user = $auth->getCurrentUser();
$data = $db->query("SELECT * FROM table")->fetch_all();

// 4. Set variables
$pageTitle = 'Page Title';
$baseUrl = 'http://localhost/DB-ecommerce';
?>

<!-- 5. Include header -->
<?php include __DIR__ . '/../src/views/header.php'; ?>

<!-- 6. Main content -->
<div class="container mt-8 pb-12">
    <!-- Content here -->
</div>

<!-- 7. Include footer -->
<?php include __DIR__ . '/../src/views/footer.php'; ?>
```

---

## 💾 Common Database Queries

### Get Current User
```php
$user = $auth->getCurrentUser();
// Returns: ['id' => 1, 'email' => 'email@example.com', 'name' => 'John', 'role' => 'customer']
```

### Get Product
```php
$productModel = new Product($db);
$product = $productModel->getWithImages($id);
// Returns: Full product with image array
```

### Get User's Orders
```php
$query = "SELECT * FROM orders WHERE customer_id = ? AND deleted_at IS NULL";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
```

### Check Stock
```php
$stmt = $db->prepare("SELECT stock FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if ($product['stock'] > 0) { /* available */ }
```

### Update Order Status
```php
$stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ? AND seller_id = ?");
$stmt->bind_param("sii", $newStatus, $orderId, $sellerId);
$stmt->execute();
```

---

## 🎨 CSS Classes Reference

### Buttons
```html
<button class="btn btn-primary">Primary Button</button>
<button class="btn btn-secondary">Secondary Button</button>
<button class="btn btn-outline">Outline Button</button>
<a href="#" class="btn btn-primary">Link Button</a>
```

### Alerts
```html
<div class="alert alert-success">Success message</div>
<div class="alert alert-danger">Error message</div>
<div class="alert alert-warning">Warning message</div>
<div class="alert alert-info">Info message</div>
```

### Badges
```html
<span class="badge badge-primary">Primary</span>
<span class="badge badge-success">Success</span>
<span class="badge badge-danger">Danger</span>
<span class="badge badge-warning">Warning</span>
```

### Forms
```html
<div class="form-group">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required>
</div>
```

### Cards/Containers
```html
<div class="bg-white rounded-lg p-6 shadow">
    <!-- Content -->
</div>
```

### Grid
```html
<!-- Responsive grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Items -->
</div>
```

---

## 🔐 Authorization Checks

### Check if Admin
```php
if (!$auth->hasRole('admin')) {
    header('Location: /DB-ecommerce/auth/login.php');
    exit();
}
```

### Check if Seller
```php
if (!$auth->hasRole('seller')) {
    header('Location: /DB-ecommerce/auth/login.php');
    exit();
}
```

### Check if Customer
```php
if (!$auth->hasRole('customer')) {
    header('Location: /DB-ecommerce/auth/login.php');
    exit();
}
```

### Check if Logged In
```php
if (!$auth->isLoggedIn()) {
    header('Location: /DB-ecommerce/auth/login.php');
    exit();
}
```

---

## 🎯 JavaScript Common Tasks

### Add Product to Cart
```javascript
addToCart(productId, quantity);
// Shows success alert and updates cart count
```

### Add to Wishlist
```javascript
addToWishlist(productId);
// Shows success alert
```

### Format Money
```javascript
const formatted = formatCurrency(1000);
// Returns: "₫1,000"
```

### Show Alert
```javascript
showAlert('Success!', 'success');
showAlert('Error occurred', 'danger');
showAlert('Warning', 'warning');
// Auto-dismisses after 3 seconds
```

---

## 📊 Default Values

### User Roles
- `admin` - Full system access
- `seller` - Can manage products & orders
- `customer` - Can browse & purchase

### Order Status
- `pending` - Just created
- `confirmed` - Seller approved
- `shipped` - In transit
- `delivered` - Order received
- `cancelled` - Order cancelled

### Payment Method
- `cod` - Cash on Delivery
- `vnpay` - Online payment

### Payment Status
- `unpaid` - Not yet paid
- `paid` - Successfully paid
- `failed` - Payment failed

### User Status
- `active` - Account active
- `inactive` - Deactivated
- `blocked` - Prevented from login

---

## 🛠️ Common Tasks

### Add New Page
1. Create file in `public/pages/page-name.php`
2. Include header: `<?php include '../src/views/header.php'; ?>`
3. Add content
4. Include footer: `<?php include '../src/views/footer.php'; ?>`

### Add New Model
1. Create class in `src/models/Model.php`
2. Extend `BaseModel`
3. Implement specific queries
4. Use in controllers

### Update Database Schema
1. Modify `database/schema.sql`
2. Drop existing database (or tables)
3. Re-run schema.sql in phpMyAdmin
4. Update models accordingly

### Enable/Disable User
```php
// Admin only
$status = 'inactive'; // or 'active', 'blocked'
$stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $userId);
$stmt->execute();
```

### Delete (Soft Delete)
```php
// Set deleted_at timestamp
$stmt = $db->prepare("UPDATE products SET deleted_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
```

### Search Products
```php
$model = new Product($db);
$results = $model->search('laptop', 10, 0); // keyword, limit, offset
```

---

## 🔍 Debugging Tips

### Check Errors
```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Print debug info
var_dump($variable);
echo "Debug: " . print_r($data, true);
```

### Check Session
```php
// See what's in session
var_dump($_SESSION);

// Manual session start
session_start();
echo $_SESSION['user_id']; // Should be number if logged in
```

### Test Database
```php
// Try a simple query
$result = $db->query("SELECT * FROM users LIMIT 1");
if ($result) {
    echo "Database connected!";
} else {
    echo "Error: " . $db->error;
}
```

### Test API
Use browser console:
```javascript
fetch('/DB-ecommerce/api/cart.php?action=count')
    .then(r => r.json())
    .then(d => console.log(d));
```

### Browser Console
- Press F12
- Go to Console tab
- Check for JavaScript errors
- Test AJAX requests

---

## 📱 Responsive Classes (Tailwind)

```html
<!-- Grid columns: mobile(1) > tablet(2) > desktop(3) -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

<!-- Flex with direction change -->
<div class="flex flex-col md:flex-row gap-4">

<!-- Hidden on mobile, visible on tablet -->
<div class="hidden md:block">

<!-- Font sizes responsive -->
<h1 class="text-2xl md:text-3xl lg:text-4xl">Title</h1>

<!-- Padding responsive -->
<div class="p-4 md:p-6 lg:p-8">Content</div>
```

---

## 📞 Quick Support

### Database Not Connecting
1. Check `config/database.php` credentials
2. Verify MySQL is running in XAMPP
3. Check database name is `DB-ecommerce`

### Pages Not Loading
1. Verify file exists
2. Check Apache is running
3. Verify URL is correct
4. Check PHP syntax errors

### AJAX Requests Failing
1. Check API endpoint URL
2. Verify you're logged in
3. Check browser Network tab
4. Make sure JSON is valid

### Styling Issues
1. Hard refresh: Ctrl+Shift+R
2. Clear browser cache
3. Check CSS file exists
4. Verify Tailwind CDN is loaded

---

## 🎓 Learning Path

1. **Start Here** - Read README.md
2. **Setup** - Follow SETUP_GUIDE.md
3. **Explore** - Browse DIRECTORY_STRUCTURE.md
4. **Code Review** - Study IMPLEMENTATION_SUMMARY.md
5. **Development** - Use this Quick Reference
6. **Extend** - Add your own features

---

**Good luck with development!** 🚀

For detailed info, check the main README.md or IMPLEMENTATION_SUMMARY.md
