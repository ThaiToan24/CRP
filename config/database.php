<?php
/**
 * Database Configuration
 * Connection settings for MySQL Database
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'DB_ecommerce');
define('DB_PORT', 3306);

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    if ($db->connect_error) {
        die("Database Connection Error: " . $db->connect_error);
    }
    
    // Set charset to UTF-8
    $db->set_charset("utf8mb4");
    
    // ensure required soft-delete columns exist; the schema/migration files add these but
    // some users may not have run the migration yet (see database/migrations/add_deleted_at.sql)
    $tables = ['users','products','orders','reviews','categories'];
    foreach ($tables as $table) {
        $colCheck = $db->query("SHOW COLUMNS FROM `$table` LIKE 'deleted_at'");
        if ($colCheck && $colCheck->num_rows === 0) {
            // silently add column
            $db->query("ALTER TABLE `$table` ADD COLUMN `deleted_at` TIMESTAMP NULL AFTER `updated_at`");
        }
    }

    // Ensure orders table has seller_id for seller-specific stats and workflows
    $ordersSellerCol = $db->query("SHOW COLUMNS FROM `orders` LIKE 'seller_id'");
    if ($ordersSellerCol && $ordersSellerCol->num_rows === 0) {
        $db->query("ALTER TABLE `orders` ADD COLUMN `seller_id` INT NULL AFTER `customer_id`");

        // Backfill existing records via order_items->products seller mapping
        $db->query(
            "UPDATE orders o
             JOIN order_items oi ON oi.order_id = o.id
             JOIN products p ON p.id = oi.product_id
             SET o.seller_id = p.seller_id
             WHERE o.seller_id IS NULL"
        );

        // if no records exist yet or mapping missing, keep nullable to avoid failures
        // if desired, enforce not-null after manual verification.
    }

    // Ensure orders table has notes so checkout can store order notes
    $ordersNotesCol = $db->query("SHOW COLUMNS FROM `orders` LIKE 'notes'");
    if ($ordersNotesCol && $ordersNotesCol->num_rows === 0) {
        $db->query("ALTER TABLE `orders` ADD COLUMN `notes` TEXT NULL AFTER `shipping_address`");
    }

    // Ensure order_items table has unit_price for line-item recording
    $orderItemsUnitPriceCol = $db->query("SHOW COLUMNS FROM `order_items` LIKE 'unit_price'");
    if ($orderItemsUnitPriceCol && $orderItemsUnitPriceCol->num_rows === 0) {
        $db->query("ALTER TABLE `order_items` ADD COLUMN `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `quantity`");

        // Backfill existing rows based on product price if needed
        $db->query(
            "UPDATE order_items oi
             JOIN products p ON p.id = oi.product_id
             SET oi.unit_price = p.price
             WHERE oi.unit_price = 0"
        );
    }

    // enforce one-row-per-user in login_history so the admin UI can just refresh
    // first remove any existing duplicates, keeping only the most recent entry per user
    $db->query(
        "DELETE lh1 FROM login_history lh1
         JOIN login_history lh2
           ON lh1.user_id = lh2.user_id AND lh1.login_time < lh2.login_time"
    );

    $idxCheck = $db->query("SHOW INDEX FROM login_history WHERE Key_name='unique_user_login'");
    if ($idxCheck && $idxCheck->num_rows === 0) {
        // wrap in @ to suppress duplicate-key error if something slipped through
        @$db->query("ALTER TABLE login_history ADD UNIQUE KEY unique_user_login (user_id)");
    }

} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}
