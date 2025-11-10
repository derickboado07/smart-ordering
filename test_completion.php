<?php
// Test script to simulate marking order as completed
include 'backend/db_connect.php';

$order_id = 118; // From our test

echo "Testing order completion for order ID: $order_id\n";

// Check inventory before
$inv_before = $conn->query("SELECT stock_quantity FROM menu_inventory WHERE menu_id = 21")->fetch_assoc()['stock_quantity'];
$ing_before = $conn->query("SELECT stock_quantity FROM ingredients WHERE id = 1")->fetch_assoc()['stock_quantity'];

echo "Menu inventory before: $inv_before\n";
echo "Espresso inventory before: $ing_before\n";

// Simulate the completion request
$url = 'http://localhost/smart-ordering/backend/delete_order.php';
$data = json_encode(['order_id' => $order_id]);

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data,
    ],
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "API Response: $result\n";

// Check inventory after
$inv_after = $conn->query("SELECT stock_quantity FROM menu_inventory WHERE menu_id = 21")->fetch_assoc()['stock_quantity'];
$ing_after = $conn->query("SELECT stock_quantity FROM ingredients WHERE id = 1")->fetch_assoc()['stock_quantity'];

echo "Menu inventory after: $inv_after\n";
echo "Espresso inventory after: $ing_after\n";

// Check order status
$order_status = $conn->query("SELECT order_status, inventory_deducted FROM orders WHERE id = $order_id")->fetch_assoc();
echo "Order status: {$order_status['order_status']}\n";
echo "Inventory deducted: {$order_status['inventory_deducted']}\n";

$conn->close();
?>
