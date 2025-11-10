<?php
include 'backend/db_connect.php';

// Make image column nullable in menu table
$conn->query("ALTER TABLE menu MODIFY COLUMN image VARCHAR(255) NULL");

// Add price column to ingredients if missing
$colCheck = $conn->query("SHOW COLUMNS FROM ingredients LIKE 'price'");
if ($colCheck->num_rows == 0) {
    $conn->query("ALTER TABLE ingredients ADD COLUMN price DECIMAL(10,2) DEFAULT 0.00");
}

// Add last_updated column to menu_inventory if missing
$colCheck2 = $conn->query("SHOW COLUMNS FROM menu_inventory LIKE 'last_updated'");
if ($colCheck2->num_rows == 0) {
    $conn->query("ALTER TABLE menu_inventory ADD COLUMN last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
}

echo "Database fixes applied.";
?>
