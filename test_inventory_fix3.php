<?php
include 'backend/db_connect.php';

// Find an unpaid order that we can test with
$result = $conn->query("SELECT id FROM orders WHERE payment_status = 'unpaid' AND order_status = 'pending' LIMIT 1");

if($row = $result->fetch_assoc()) {
    $order_id = $row['id'];
    echo "Found unpaid order ID: $order_id\n";

    // Get order items
    $items_result = $conn->query("SELECT item_name, quantity FROM order_items WHERE order_id = $order_id");
    echo "Order items:\n";
    while($item = $items_result->fetch_assoc()) {
        echo "- {$item['item_name']}: {$item['quantity']}\n";
    }

    // Check current inventory levels before completion
    echo "\nChecking inventory before completion...\n";
    $inv_result = $conn->query("SELECT mi.menu_id, mi.stock_quantity, m.name FROM menu_inventory mi JOIN menu m ON mi.menu_id = m.id");
    echo "Menu inventory before:\n";
    while($inv = $inv_result->fetch_assoc()) {
        echo "- {$inv['name']} (ID:{$inv['menu_id']}): {$inv['stock_quantity']}\n";
    }

    $ing_result = $conn->query("SELECT id, name, stock_quantity FROM ingredients");
    echo "Ingredients inventory before:\n";
    while($ing = $ing_result->fetch_assoc()) {
        echo "- {$ing['name']} (ID:{$ing['id']}): {$ing['stock_quantity']}\n";
    }

    // Check inventory_deducted flag
    $deduct_result = $conn->query("SELECT inventory_deducted FROM orders WHERE id = $order_id");
    $deduct_row = $deduct_result->fetch_assoc();
    echo "\nInventory already deducted: " . ($deduct_row['inventory_deducted'] ? 'Yes' : 'No') . "\n";

} else {
    echo "No unpaid pending order found for testing\n";
}

$conn->close();
?>
