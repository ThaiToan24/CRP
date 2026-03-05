<?php
/**
 * Contact Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

$auth = new Auth($db);

$pageTitle = 'Contact Us - DB eCommerce';
// base URL available via header
?>

<?php include __DIR__ . '/../../src/views/header.php'; ?>

<div class="container mt-8 pb-12">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
        <!-- Contact Form -->
        <div>
            <h1 class="text-3xl font-bold mb-6">Get in Touch</h1>
            
            <form class="bg-white rounded-lg p-6 space-y-4">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="6" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary w-full">Send Message</button>
            </form>
        </div>
        
        <!-- Contact Information -->
        <div class="bg-white rounded-lg p-6 h-fit">
            <h2 class="text-2xl font-bold mb-6">Contact Information</h2>
            
            <div class="space-y-6">
                <div>
                    <h3 class="font-bold mb-2">Email</h3>
                    <p class="text-gray-600">
                        <a href="mailto:support@dbecommerce.com" class="text-primary hover:underline">
                            support@dbecommerce.com
                        </a>
                    </p>
                </div>
                
                <div>
                    <h3 class="font-bold mb-2">Phone</h3>
                    <p class="text-gray-600">
                        <a href="tel:+1234567890" class="text-primary hover:underline">
                            +1 (234) 567-890
                        </a>
                    </p>
                </div>
                
                <div>
                    <h3 class="font-bold mb-2">Address</h3>
                    <p class="text-gray-600">
                        123 Business Street<br>
                        Commerce City, CC 12345<br>
                        United States
                    </p>
                </div>
                
                <div>
                    <h3 class="font-bold mb-2">Business Hours</h3>
                    <p class="text-gray-600">
                        Monday - Friday: 9:00 AM - 6:00 PM<br>
                        Saturday: 10:00 AM - 4:00 PM<br>
                        Sunday: Closed
                    </p>
                </div>
                
                <div>
                    <h3 class="font-bold mb-2">Follow Us</h3>
                    <div class="flex gap-4">
                        <a href="#" class="text-primary hover:underline">Facebook</a>
                        <a href="#" class="text-primary hover:underline">Twitter</a>
                        <a href="#" class="text-primary hover:underline">Instagram</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/views/footer.php'; ?>
