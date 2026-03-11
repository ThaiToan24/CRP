# Shopping Cart & Wishlist Implementation

## Overview
Hệ thống đã được cập nhật với các chức năng sau:
- ✅ Chọn số lượng sản phẩm trước khi thêm vào giỏ hàng
- ✅ Tự động chuyển hướng đến giỏ hàng sau khi thêm sản phẩm
- ✅ Tính toán tổng tiền sản phẩm trong giỏ hàng
- ✅ Chuyển hướng đến danh mục Wishlist khi nhấn tim
- ✅ Kiểm tra xác thực (yêu cầu đăng nhập)

---

## Chi Tiết Các Thay Đổi

### 1. File: `assets/js/main.js`

**Thêm hàm `showQuantityModal()`**
- Hiển thị modal chọn số lượng khi thêm sản phẩm
- Cho phép tăng/giảm số lượng bằng nút +/-
- Kiểm tra giới hạn tồn kho
- Đóng modal khi nhấn bên ngoài

**Cập nhật hàm `addToCart()`**
- Thêm tham số `quantity` để chọn số lượng
- Thêm tham số `redirect = true` để automatic redirect
- Kiểm tra xác thực (401 error → redirect to login)
- Tự động chuyển hướng đến `/pages/cart.php` sau 1 giây khi `redirect = true`

**Cập nhật hàm `addToWishlist()`**
- Kiểm tra xác thực trước khi thêm
- Tự động chuyển hướng đến `/pages/wishlist.php` sau khi thêm thành công
- Nếu chưa đăng nhập → redirect to `/auth/login.php`

### 2. File: `public/pages/products.php`

**Thay đổi nút "Add"**
- Đổi từ: `onclick="addToCart(<?php echo $product['id']; ?>)"`
- Thành: `onclick="showQuantityModal(<?php echo $product['id']; ?>, <?php echo $product['stock']; ?>)"`
- Cập nhật text từ "Add" → "Add to Cart"

**Thêm Modal HTML**
```html
<div id="quantityModal" class="modal-overlay">
    <div class="modal-content">
        <!-- Modal header, body, footer -->
    </div>
</div>
```

Bao gồm:
- Selector số lượng với nút +/-
- Nút "Add to Cart" (xác nhận)
- Nút "Cancel" (đóng modal)

**Nút Wishlist**
- Thêm class `heart-btn` cho styling
- Thêm `title="Add to Wishlist"` cho tooltip

### 3. File: `assets/css/style.css`

**Thêm CSS cho Modal**
```css
.modal-overlay { /* Container overlay */ }
.modal-content { /* Modal box */ }
.modal-header { /* Header with close button */ }
.modal-body { /* Nội dung chính */ }
.modal-footer { /* Footer với buttons */ }
@keyframes slideUp { /* Animation */ }
```

**Thêm styling cho Heart button**
```css
.heart-btn { /* Styling nút tim */ }
.heart-btn:hover { /* Hover effect */ }
```

---

## Quy Trình Mua Hàng

### Trang Products (Danh sách sản phẩm)
1. Người dùng nhấn "Add to Cart"
2. Modal hiển thị với selector số lượng
3. Người dùng chọn số lượng (mặc định 1)
4. Nhấn "Add to Cart" trong modal
5. Sản phẩm được thêm vào giỏ hàng
6. Trang tự động chuyển hướng đến Cart (`/pages/cart.php`)
7. Thông báo thành công hiển thị

### Trang Cart (Giỏ hàng)
1. Hiển thị danh sách sản phẩm đã thêm
2. Hiển thị:
   - Hình ảnh sản phẩm
   - Tên, giá, bán bởi
   - Số lượng (có thể thay đổi bằng nút +/-)
   - Tổng tiền cho sản phẩm đó
3. Bên phải: "Order Summary"
   - Tính toán Subtotal (tự động)
   - Hiển thị Shipping, Tax (mặc định 0)
   - Tính toán Total
   - Nút "Proceed to Checkout"

### Trang Checkout (Thanh toán)
1. Nhập thông tin giao hàng:
   - Họ tên
   - Số điện thoại
   - Địa chỉ giao hàng
   - Ghi chú đơn hàng
2. Chọn phương thức thanh toán:
   - Cash on Delivery (COD)
   - VNPay
3. Nhấn "Place Order"
4. Tạo đơn hàng, cập nhật tồn kho
5. Xóa giỏ hàng
6. Chuyển hướng đến trang Payment

### Trang Payment (Xác nhận)
- Hiển thị thông báo "Order Placed Successfully!"
- Nút "View My Orders" → `/pages/orders.php`
- Nút "Continue Shopping" → `/pages/products.php`

---

## Wishlist Flow

### Từ Products Page
1. Nhấn nút ♡ (heart)
2. Sản phẩm được thêm vào wishlist
3. Trang tự động chuyển hướng đến `/pages/wishlist.php`

### Trang Wishlist
1. Hiển thị danh sách sản phẩm yêu thích
2. Mỗi sản phẩm có:
   - Nút "Add to Cart" → thêm vào giỏ hàng
   - Nút "Remove" → xóa khỏi wishlist

---

## Kiểm Tra Xác Thực

- Khi user chưa đăng nhập:
  - Nhấn "Add to Cart" → thông báo + redirect to login
  - Nhấn ♡ → thông báo + redirect to login

- Khi user đã đăng nhập:
  - Đầy đủ chức năng sẽ hoạt động

---

## Testing Checklist

- [ ] Trang Products: Nút "Add to Cart" hiển thị modal chọn số lượng
- [ ] Modal: Có thể tăng/giảm số lượng bằng +/- buttons
- [ ] Modal: Đóng được khi nhấn X hoặc Cancel
- [ ] Thêm sản phẩm: Redirect đến trang Cart
- [ ] Cart: Hiển thị sản phẩm vừa thêm
- [ ] Cart: Tính toán tổng tiền đúng
- [ ] Cart: Có thể thay đổi số lượng
- [ ] Nút ♡: Redirect đến Wishlist
- [ ] Wishlist: Hiển thị sản phẩm
- [ ] Checkout: Tạo order, cập nhật tồn kho
- [ ] Payment: Trang thành công

---

## API Endpoints

### Thêm vào Cart
```
POST /public/api/cart.php?action=add
Body: {
    "product_id": number,
    "quantity": number
}
Response: { "success": boolean, "message": string }
```

### Thêm vào Wishlist
```
POST /public/api/wishlist.php?action=add
Body: {
    "product_id": number
}
Response: { "success": boolean, "message": string }
```

---

## Ghi Chú

- Modal được styling bằng Tailwind CSS + custom CSS
- Animation smooth với `slideUp` keyframes
- Heart button (♡) có hover effect
- Responsive design cho mobile devices
- Session-based authentication
