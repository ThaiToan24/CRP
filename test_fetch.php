<?php
require_once 'config/database.php';
require_once 'src/utils/Auth.php';
$auth = new Auth($db);
$auth->login('thaitoan@gmail.com', '123123'); // Login to get session cookie

$sessionId = session_id();
session_write_close(); // Unlock session file for cURL

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/DB-ecommerce/public/api/cart.php?action=add');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['product_id' => 2, 'quantity' => 1]));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . $sessionId);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpcode . "\n";
echo "Response: " . $response . "\n";
