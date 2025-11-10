<?php
include 'backend/db_connect.php';

// Function to check if foreign key exists
function foreignKeyExists($conn, $table, $constraintName) {
    $result = $conn->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND CONSTRAINT_NAME = '$constraintName'");
    return $result && $result->num_rows > 0;
}

// Add foreign key from menu_recipes.menu_id to menu.id
if (!foreignKeyExists($conn, 'menu_recipes', 'fk_menu_recipes_menu_id')) {
    $conn->query("ALTER TABLE menu_recipes ADD CONSTRAINT fk_menu_recipes_menu_id FOREIGN KEY (menu_id) REFERENCES menu(id) ON DELETE CASCADE");
    echo "Added foreign key fk_menu_recipes_menu_id to menu_recipes\n";
} else {
    echo "Foreign key fk_menu_recipes_menu_id already exists\n";
}

// Add foreign key from menu_recipes.ingredient_id to ingredients.id
if (!foreignKeyExists($conn, 'menu_recipes', 'fk_menu_recipes_ingredient_id')) {
    $conn->query("ALTER TABLE menu_recipes ADD CONSTRAINT fk_menu_recipes_ingredient_id FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE");
    echo "Added foreign key fk_menu_recipes_ingredient_id to menu_recipes\n";
} else {
    echo "Foreign key fk_menu_recipes_ingredient_id already exists\n";
}

// Add foreign key from menu_inventory.menu_id to menu.id
if (!foreignKeyExists($conn, 'menu_inventory', 'fk_menu_inventory_menu_id')) {
    $conn->query("ALTER TABLE menu_inventory ADD CONSTRAINT fk_menu_inventory_menu_id FOREIGN KEY (menu_id) REFERENCES menu(id) ON DELETE CASCADE");
    echo "Added foreign key fk_menu_inventory_menu_id to menu_inventory\n";
} else {
    echo "Foreign key fk_menu_inventory_menu_id already exists\n";
}

echo "ERD fix completed successfully!\n";
?>
