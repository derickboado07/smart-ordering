<?php
// Test double deduction prevention
include 'backend/db_connect.php';

$order_id = 118; // Same order we just completed

echo "Testing double deduction prevention for order ID: $order_id\n";

// Check inventory before second attempt
$inv_before = $conn->query("SELECT stock_quantity FROM menu_inventory WHERE menu_id = 21")->fetch_assoc()['stock_quantity'];
$ing_before = $conn->query("SELECT stock_quantity FROM ingredients WHERE id = 1")->fetch_assoc()['stock_quantity'];

echo "Menu inventory before second attempt: $inv_before\n";
echo "Espresso inventory before second attempt: $ing_before\n";

// Try to complete the same order again
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

echo "API Response for second attempt: $result\n";

// Check inventory after second attempt
$inv_after = $conn->query("SELECT stock_quantity FROM menu_inventory WHERE menu_id = 21")->fetch_assoc()['stock_quantity'];
$ing_after = $conn->query("SELECT stock_quantity FROM ingredients WHERE id = 1")->fetch_assoc()['stock_quantity'];

echo "Menu inventory after second attempt: $inv_after\n";
echo "Espresso inventory after second attempt: $ing_after\n";

// Check if inventory changed
$inv_changed = ($inv_before != $inv_after) ? 'YES' : 'NO';
$ing_changed = ($ing_before != $ing_after) ? 'YES' : 'NO';

echo "Menu inventory changed: $inv_changed\n";
echo "Ingredient inventory changed: $ing_changed\n";

$conn->close();
?>
