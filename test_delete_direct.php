<?php
include 'backend/db_connect.php';

$id = 37;

// Check if menu item is referenced in order_items
$checkStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM order_items WHERE menu_id = ?");
$checkStmt->bind_param('i', $id);
$checkStmt->execute();
$count = $checkStmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$checkStmt->close();

echo "Order references for menu_id $id: $count\n";

if ($count > 0) {
    echo json_encode(['success'=>false, 'message'=>'Cannot delete product that has been ordered. It has ' . $count . ' order references.']);
} else {
    echo "Would delete the product\n";
}
?>
