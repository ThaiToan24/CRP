<?php
$data = json_encode(['product_id' => 2, 'quantity' => 1]);
$ch = curl_init('http://localhost:8000/public/api/cart.php?action=add');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Cookie: PHPSESSID=' . (isset($argv[1]) ? $argv[1] : '')
));
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
$response = curl_exec($ch);
echo "Response: " . $response . "\n";
curl_close($ch);
