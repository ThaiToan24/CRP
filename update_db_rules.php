<?php
require_once __DIR__ . '/config/database.php';

$sql = "CREATE TABLE IF NOT EXISTS failed_logins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP
)";

if ($db->query($sql)) {
    echo "Table failed_logins created successfully.\n";
} else {
    echo "Error creating table: " . $db->error . "\n";
}
