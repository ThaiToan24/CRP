# DB eCommerce - Quick Setup Guide

## 📝 Pre-Installation Checklist

- [ ] XAMPP installed and working
- [ ] MySQL service can be started
- [ ] Apache service can be started
- [ ] VS Code or preferred IDE installed
- [ ] Browser (Chrome/Firefox) ready

## 🚀 5-Minute Setup

### 1. Copy Project Files
Project is located in `c:\xampp\htdocs\DB-ecommerce`

### 2. Start XAMPP Services
```
Open XAMPP Control Panel
→ Click "Start" for Apache
→ Click "Start" for MySQL
(Wait for both to show green indicators)
```

### 3. Create Database
Visit: `http://localhost/phpmyadmin`
1. Click "SQL" tab
2. Copy all content from `database/schema.sql`
3. Paste into SQL editor
4. Click "Go"

### 4. Access Application
Visit: `http://localhost/DB-ecommerce/public/`

### 5. Login
- Email: `admin@gmail.com`
- Password: `admin123`

## ✅ Verify Everything Works

After login, you should see:
- [ ] Header with navigation
- [ ] Home page with banners
- [ ] Product listings
- [ ] Cart functionality
- [ ] User profile access

## 🔧 Troubleshooting Quick Fixes

### Database Error
```
Error: Connection failed
Solution: 
1. Start MySQL in XAMPP
2. Check config/database.php
3. Verify DB-ecommerce database exists in phpMyAdmin
```

### Page Not Found (404)
```
Error: PHP page not loading
Solution:
1. Make sure Apache is running
2. Verify file exists in /public/ folder
3. Check file permissions
4. Restart Apache
```

### API Endpoints Not Working
```
Error: AJAX calls failing
Solution:
1. Open browser Console (F12)
2. Check network tab for failed requests
3. Verify endpoint URLs match in main.js
4. Ensure you're logged in
```

### CSS Not Loading
```
Error: Page looks unstyled
Solution:
1. Hard refresh browser (Ctrl+Shift+R)
2. Check browser console for CSS errors
3. Verify /assets/css/style.css exists
4. Verify Tailwind CDN is loading
```

## 📚 Next Steps

After successful setup:

1. **Create test accounts**
   - Register as a Seller
   - Register as multiple Customers
   - Test shopping flow

2. **Add test data**
   - Create categories (Admin)
   - Add products (Seller)
   - Place orders (Customer)

3. **Test features**
   - Shopping cart
   - Checkout process
   - Order tracking
   - Review products

4. **Explore code**
   - Review database schema
   - Study authentication flow
   - Examine API endpoints
   - Understand MVC structure

## 🎯 Development Tips

### VS Code Extensions to install
- PHP Intelephense
- MySQL
- Thunder Client (for API testing)
- Tailwind CSS IntelliSense

### Live Server Setup (Optional)
1. Install "Live Server" extension in VS Code
2. Right-click on `.php` file
3. Click "Open with Live Server"

### Debug Mode
Add to `config/database.php` for error details:
```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

## 📞 Common Tasks

### Reset Admin Password
```sql
-- In phpMyAdmin → SQL
UPDATE users SET password = '$2y$10$VzKKGVWWrpqtvF5UqgxKEey1pVgPeKCqVvs5p8Q3V6pV6xY6K6yNi' 
WHERE email = 'admin@gmail.com';
-- Password will be: admin123
```

### Clear All Data (Keep Structure)
```sql
TRUNCATE TABLE cart;
TRUNCATE TABLE wishlist;
TRUNCATE TABLE reviews;
TRUNCATE TABLE order_items;
TRUNCATE TABLE orders;
TRUNCATE TABLE product_images;
TRUNCATE TABLE products;
```

### Backup Database
In phpMyAdmin:
1. Select "DB-ecommerce" database
2. Click "Export"
3. Click "Go" to download SQL file

## 🔐 Security Notes for Development

This is a development/educational build. For production:
- [ ] Change default admin password
- [ ] Implement CSRF tokens
- [ ] Add rate limiting
- [ ] Enable HTTPS
- [ ] Sanitize all user inputs
- [ ] Use environment variables for sensitive data
- [ ] Implement proper error logging
- [ ] Add input validation on backend

## 📞 Need Help?

1. Check the main README.md file
2. Review database schema comments
3. Check browser Console (F12) for client errors
4. Check Apache error log in XAMPP
5. Verify all files are in correct locations

---

**Happy Development!** 🎉
