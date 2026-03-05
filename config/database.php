<?php
/**
 * Database Configuration
 * Connection settings for MySQL Database
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'DB-ecommerce');
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
