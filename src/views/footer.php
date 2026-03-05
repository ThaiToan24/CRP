<?php
/**
 * Footer Component
 */

// ensure there is a base URL available for assets
$baseUrl = defined('BASE_URL') ? BASE_URL : getBaseUrl();
?>

<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h4>About DB eCommerce</h4>
                <p>Your trusted online marketplace for quality products and seamless shopping experience.</p>
            </div>
            
            <div class="footer-section">
                <h4>Quick Links</h4>
                <a href="<?php echo $baseUrl; ?>/pages/home.php">Home</a>
                <a href="<?php echo $baseUrl; ?>/pages/products.php">Products</a>
                <a href="<?php echo $baseUrl; ?>/pages/categories.php">Categories</a>
                <a href="<?php echo $baseUrl; ?>/pages/contact.php">Contact Us</a>
            </div>
            
            <div class="footer-section">
                <h4>Customer Service</h4>
                <a href="#">Track Order</a>
                <a href="#">Returns & Exchanges</a>
                <a href="#">Shipping Info</a>
                <a href="#">FAQ</a>
            </div>
            
            <div class="footer-section">
                <h4>Seller Center</h4>
                <a href="<?php echo $baseUrl; ?>/auth/register.php">Become a Seller</a>
                <a href="<?php echo $baseUrl; ?>/seller/dashboard.php">Seller Dashboard</a>
                <a href="#">Seller Rules</a>
            </div>
            
            <div class="footer-section">
                <h4>Legal</h4>
                <a href="#">Terms of Service</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Cookie Policy</a>
                <a href="#">Contact Us</a>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2026 DB eCommerce. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="<?php echo $baseUrl; ?>/assets/js/main.js"></script>
</body>
</html>
