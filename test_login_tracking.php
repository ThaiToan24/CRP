<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/utils/Auth.php';

// First, ensure the table exists
$sql = "CREATE TABLE IF NOT EXISTS login_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  location VARCHAR(255) DEFAULT 'Unknown',
  login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$db->query($sql);
echo "Table ensured.\n";

$auth = new Auth($db);

// Register a test user
$email = 'testuser_' . time() . '@example.com';
$password = 'password123';
$reg = $auth->register($email, $password, 'Test User', '1234567890', 'customer');

// Login
$login = $auth->login($email, $password);
print_r($login);

echo "\nSession alert: " . ($_SESSION['login_location_alert'] ?? 'None') . "\n";

$logins = $db->query("SELECT * FROM login_history ORDER BY login_time DESC LIMIT 2");
echo "\nLogin History in DB:\n";
while($row = $logins->fetch_assoc()) {
    print_r($row);
}
