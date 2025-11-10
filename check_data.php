<?php
include 'backend/db_connect.php';

$res = $conn->query('SELECT COUNT(*) as cnt FROM menu');
echo 'Menu items count: ' . $res->fetch_assoc()['cnt'] . PHP_EOL;

$res = $conn->query('SELECT COUNT(*) as cnt FROM ingredients');
echo 'Ingredients count: ' . $res->fetch_assoc()['cnt'] . PHP_EOL;

$res = $conn->query('SELECT COUNT(*) as cnt FROM menu_recipes');
echo 'Recipes count: ' . $res->fetch_assoc()['cnt'] . PHP_EOL;
?>
