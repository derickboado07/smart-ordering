<?php
include 'backend/db_connect.php';

// Check all orders and their status
$result = $conn->query("SELECT id, payment_status, order_status, inventory_deducted FROM orders ORDER BY id DESC LIMIT 5");

echo "Recent orders:\n";
while($row = $result->fetch_assoc()) {
    echo "Order ID: {$row['id']}, Payment: {$row['payment_status']}, Status: {$row['order_status']}, Deducted: {$row['inventory_deducted']}\n";
}

// Check if there are any paid orders that are not completed
$paid_result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE payment_status = 'paid'");
$paid_row = $paid_result->fetch_assoc();
echo "\nTotal paid orders: {$paid_row['count']}\n";

$completed_result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'completed'");
$completed_row = $completed_result->fetch_assoc();
echo "Total completed orders: {$completed_row['completed']}\n";

$conn->close();
?>
