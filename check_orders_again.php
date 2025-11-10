<?php
include 'backend/db_connect.php';

$res = $conn->query('SELECT COUNT(*) as cnt FROM order_items WHERE menu_id = 37');
echo 'Order items referencing sisig: ' . $res->fetch_assoc()['cnt'] . PHP_EOL;
?>
