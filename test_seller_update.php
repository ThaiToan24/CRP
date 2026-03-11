<?php
require_once 'config/database.php';
require_once 'src/utils/Auth.php';

$auth = new Auth($db);
$auth->login('hongmy@gmail.com', '123123'); // Login as seller

$sessionId = session_id();
session_write_close(); // Unlock session

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/DB-ecommerce/public/pages/order-detail.php?id=1'); // Assuming order 1 exists
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['status' => 'shipped']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . $sessionId);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpcode\n";

if (strpos($response, 'Shipped') !== false) {
    echo "Status updated to Shipped successfully.\n";
} else {
    echo "Status update failed or order not found.\n";
}

$res = $db->query('SELECT id, status FROM orders');
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
