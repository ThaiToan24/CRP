<?php
$db = new mysqli('localhost','root','','DB-ecommerce');
if($db->connect_error){
    echo "connect error: {$db->connect_error}\n";
    exit;
}
$res = $db->query("SELECT id,title,image FROM banners");
if(!$res){
    echo "query error: {$db->error}\n";
    exit;
}
while($r = $res->fetch_assoc()){
    echo $r['id'] . ' | ' . $r['title'] . ' | ' . $r['image'] . "\n";
}
