<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

try {
    // Delete inventory rows where the linked menu item category is not 'pastries'
    $sql = "DELETE mi FROM menu_inventory mi JOIN menu m ON mi.menu_id = m.id WHERE LOWER(m.category) <> 'pastries'";
    $res = $conn->query($sql);
    if ($res === false) {
        echo json_encode(['success' => false, 'message' => $conn->error]);
        exit;
    }
    $affected = $conn->affected_rows;
    echo json_encode(['success' => true, 'deleted_rows' => $affected]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();

?>
