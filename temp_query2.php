<?php
require 'config/database.php';
$res = $db->query('SELECT p.id, p.name, p.seller_id, u.id AS u_id, u.name AS seller_name FROM products p LEFT JOIN users u ON p.seller_id = u.id');
echo "Products:\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "Cart:\n";
$res = $db->query('SELECT * FROM cart');
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
