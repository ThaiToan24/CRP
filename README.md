# DB eCommerce - Shopee-like E-Commerce Platform

A modern, full-featured e-commerce platform built with PHP, MySQL, HTML/CSS, and JavaScript using Tailwind CSS. Designed for seamless online shopping with real-time anomaly detection capabilities for login systems.

## 🚀 Features

### Core Features
- ✅ User Authentication (Login, Register, Logout) with password hashing
- ✅ Role-based access control (Admin, Seller, Customer)
- ✅ Product catalog with search and filtering
- ✅ Shopping cart with quantity management
- ✅ Order management and tracking
- ✅ Wishlist functionality
- ✅ Product reviews and ratings
- ✅ Multiple payment methods (COD, VNPay)
- ✅ Responsive design with Tailwind CSS

### Admin Features
- Dashboard with system statistics
- User management (Create, Read, Update, Delete)
- Category management
- Banner management with auto-rotation
- Platform fee and transaction fee configuration
- System settings management

### Seller Features
- Product management (CRUD operations)
- Real-time discount configuration
- Inventory management
- Order management and status updates
- Sales analytics (coming soon)

### Customer Features
- Browse products by category
- Advanced product search
- Add/remove items from cart
- Create orders with shipping address
- Track order status
- Save items to wishlist
- Cancel orders
- View order history

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ (via XAMPP)
- **Frontend**: HTML5, CSS3, JavaScript
- **CSS Framework**: Tailwind CSS
- **Server**: Apache (via XAMPP)
- **Development Environment**: VS Code

## 📋 Requirements

- XAMPP (with PHP 7.4+ and MySQL)
- VS Code or any PHP-compatible IDE
- Modern web browser (Chrome, Firefox, Safari, Edge)
- Git (optional)

## ⚙️ Installation and Setup

### Step 1: Extract/Clone the Project

```bash
# Navigate to XAMPP htdocs directory
cd c:\xampp\htdocs

# The project is already in DB-ecommerce folder
# If cloning: git clone <repo-url> DB-ecommerce
```

### Step 2: Start XAMPP Services

1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL** services
3. Verify both are running (green indicators)

### Step 3: Create Database

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click on "SQL" tab
3. Copy and paste the contents of `database/schema.sql`
4. Click "Go" to execute

Alternatively, from command line:
```bash
mysql -u root -p < database\schema.sql
```

### Step 4: Verify Configuration

1. Open `config/database.php`
2. Verify database connection settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Usually empty for XAMPP
   define('DB_NAME', 'DB-ecommerce');
   ```

### Step 5: Access the Application

Open your browser and navigate to:
```
http://localhost/DB-ecommerce/public/
```

## 🔐 Default Credentials

### Admin Account
- **Email**: `admin@gmail.com`
- **Password**: `admin123`

Create additional accounts by registering through the application.

## 📁 Project Structure

```
DB-ecommerce/
├── config/                 # Configuration files
│   └── database.php       # Database connection
├── database/
│   └── schema.sql         # Database schema and migrations
├── public/                # Web root
│   ├── index.php          # Main entry point
│   ├── auth/              # Authentication pages
│   │   ├── login.php
│   │   ├── register.php
│   │   └── logout.php
│   ├── pages/             # Customer-facing pages
│   │   ├── home.php
│   │   ├── products.php
│   │   ├── product-detail.php
│   │   ├── categories.php
│   │   ├── cart.php
│   │   ├── checkout.php
│   │   ├── orders.php
│   │   ├── order-detail.php
│   │   ├── account.php
│   │   ├── wishlist.php
│   │   ├── payment.php
│   │   └── contact.php
│   ├── api/               # API endpoints
│   │   ├── cart.php
│   │   └── wishlist.php
│   ├── admin/             # Admin dashboard (to be implemented)
│   └── seller/            # Seller dashboard (to be implemented)
├── src/
│   ├── models/            # Database models
│   │   ├── BaseModel.php
│   │   ├── Product.php
│   │   └── Category.php
│   ├── controllers/       # Controllers (to be implemented)
│   ├── utils/             # Utility classes
│   │   └── Auth.php
│   └── views/             # View components
│       ├── header.php
│       └── footer.php
├── assets/
│   ├── css/               # Stylesheets
│   │   └── style.css
│   ├── js/                # JavaScript files
│   │   └── main.js
│   └── images/            # Static images
├── uploads/               # User and product uploads
└── README.md              # Project documentation
```

## 🔄 Database Schema

### Core Tables
- **users**: Admin, Seller, Customer accounts
- **categories**: Product categories
- **products**: Product information
- **product_images**: Multiple product images
- **cart**: Shopping cart items
- **orders**: Order records
- **order_items**: Items within orders
- **reviews**: Product reviews and ratings
- **wishlist**: Favorite items
- **banners**: Marketing banners
- **seller_categories**: Seller category mappings
- **system_settings**: Platform configuration

## 🚀 Getting Started

### Create Your First Product (As Seller)

1. Register as a Seller account
2. Navigate to Seller Dashboard
3. Add categories and products
4. Set prices and discounts
5. Manage inventory

### Make Your First Order (As Customer)

1. Register as a Customer
2. Browse products
3. Add items to cart
4. Proceed to checkout
5. Choose payment method
6. Track order in "My Orders"

### Manage System (As Admin)

1. Login with admin credentials
2. Access Admin Dashboard
3. Manage users, categories, banners
4. Configure platform fees
5. View system statistics

## 🔧 Configuration

### Database Connection

Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');      // MySQL host
define('DB_USER', 'root');           // MySQL username
define('DB_PASS', '');               // MySQL password
define('DB_NAME', 'DB-ecommerce');   // Database name
define('DB_PORT', 3306);             // MySQL port
```

### System Settings

System settings can be updated in the database or through the Admin Dashboard:
- `platform_fee_percentage`: Fee charged to sellers (default: 5%)
- `transaction_fee_percentage`: Transaction fee (default: 2%)
- VNPay API credentials

## 📝 API Endpoints

### Cart Management
- `POST /api/cart.php?action=add` - Add product to cart
- `POST /api/cart.php?action=update` - Update cart item quantity
- `POST /api/cart.php?action=remove` - Remove from cart
- `GET /api/cart.php?action=count` - Get cart item count

### Wishlist Management
- `POST /api/wishlist.php?action=add` - Add to wishlist
- `POST /api/wishlist.php?action=remove` - Remove from wishlist

## 🎨 Styling and UI

The project uses:
- **Tailwind CSS** for responsive design
- **Custom CSS** in `assets/css/style.css`
- **Bootstrap-like components** (buttons, forms, alerts)
- **Material Design icons** via inline SVG

## 🐛 Troubleshooting

### Database Connection Error
- Ensure MySQL service is running in XAMPP
- Verify `config/database.php` settings
- Check database exists: `phpMyAdmin` → Databases

### Pages Not Loading
- Verify Apache service is running
- Check file paths are correct
- Ensure PHP short tags are enabled: `<?php` not `<? ?>`

### Images Not Displaying
- Ensure `uploads/` directory exists and is writable
- Check file paths in `$baseUrl` variable

### Cart Not Working
- Ensure you're logged in as Customer
- Check browser console for JavaScript errors
- Verify API endpoints are accessible

## 📚 Development Notes

### Adding New Pages
1. Create file in `public/pages/` directory
2. Include header and footer: `include '../src/views/header.php'`
3. Use existing models for database operations
4. Follow naming conventions

### Extending Database
1. Modify `database/schema.sql`
2. Drop existing tables if needed
3. Re-execute SQL script
4. Create corresponding model class

### Authentication
- Uses PHP `$_SESSION` for user management
- Passwords hashed with `password_hash()` (BCRYPT)
- Auth class handles login/logout logic

## 🔒 Security Considerations

- ✅ Password hashing with BCRYPT
- ✅ SQL prepared statements to prevent injection
- ✅ Session-based authentication
- ✅ Input validation and sanitization
- ⚠️ TODO: CSRF token implementation
- ⚠️ TODO: Rate limiting on login attempts
- ⚠️ TODO: Anomaly detection for suspicious login patterns

## 📈 Performance Optimization

- Database indexes on frequently queried columns
- Pagination for product listings
- Image optimization recommended
- Caching strategy (to be implemented)

## 🤝 Contributing

To contribute to this project:
1. Create a feature branch
2. Make your changes
3. Test thoroughly
4. Submit pull request

## 📄 License

This project is for educational purposes.

## 📞 Support

For issues or questions:
1. Check troubleshooting section
2. Review database schema
3. Verify all files are in correct locations
4. Check PHP error logs

## 🎯 Future Enhancements

- [ ] Admin Dashboard with charts/statistics
- [ ] Seller Analytics and Analytics Dashboard
- [ ] Real-time notification system
- [ ] Advanced anomaly detection for login
- [ ] Email notification system
- [ ] Product recommendation engine
- [ ] Review moderation system
- [ ] Multi-language support
- [ ] Mobile app API
- [ ] Advanced search with filters

---

**Last Updated**: February 5, 2026  
**Version**: 1.0.0
