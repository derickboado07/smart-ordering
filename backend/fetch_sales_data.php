<?php
header('Content-Type: application/json');
include 'db_connect.php';

$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// If no dates provided, show today's data
if (!$start_date && !$end_date) {
    // Today's summary: gross sales and ingredient cost computed from paid orders today
    // NOTE: Ingredient cost previously failed for items without menu_id. We now LEFT JOIN menu by name to resolve missing menu_id.
    // Gross sales uses total_amount (already discounted). If you want pre-discount, replace with subtotal.
    $query = "SELECT
        CURDATE() as date,
        COUNT(DISTINCT CASE WHEN DATE(o.created_at) = CURDATE() THEN o.id END) as orders_today,
        SUM(CASE WHEN DATE(o.created_at) = CURDATE() THEN o.total_amount ELSE 0 END) as gross_sales,
        COALESCE((
            SELECT SUM(oi.quantity * mr.quantity_required * i.price)
            FROM order_items oi
            JOIN orders ord ON oi.order_id = ord.id AND ord.payment_status = 'paid' AND DATE(ord.created_at) = CURDATE()
            LEFT JOIN menu m ON LOWER(m.name) = LOWER(oi.item_name)
            JOIN menu_recipes mr ON mr.menu_id = COALESCE(oi.menu_id, m.id)
            JOIN ingredients i ON i.id = mr.ingredient_id
        ), 0) as ingredient_cost,
        (SUM(CASE WHEN DATE(o.created_at) = CURDATE() THEN o.total_amount ELSE 0 END) - COALESCE((
            SELECT SUM(oi.quantity * mr.quantity_required * i.price)
            FROM order_items oi
            JOIN orders ord ON oi.order_id = ord.id AND ord.payment_status = 'paid' AND DATE(ord.created_at) = CURDATE()
            LEFT JOIN menu m ON LOWER(m.name) = LOWER(oi.item_name)
            JOIN menu_recipes mr ON mr.menu_id = COALESCE(oi.menu_id, m.id)
            JOIN ingredients i ON i.id = mr.ingredient_id
        ), 0)) as net_income,
        (SELECT oi.item_name FROM order_items oi
         JOIN orders ord ON oi.order_id = ord.id
         WHERE DATE(ord.created_at) = CURDATE()
         AND ord.payment_status = 'paid'
         GROUP BY oi.item_name
         ORDER BY SUM(oi.quantity) DESC
         LIMIT 1) as top_product
    FROM orders o
    WHERE o.payment_status = 'paid'";
} else {
    // If dates provided, show aggregated data for the period
    $query = "SELECT
        CONCAT('$start_date', ' to ', '$end_date') as date,
        COUNT(DISTINCT o.id) as orders_today,
        SUM(o.total_amount) as gross_sales,
        COALESCE((
            SELECT SUM(oi.quantity * mr.quantity_required * i.price)
            FROM order_items oi
            JOIN orders ord ON oi.order_id = ord.id AND ord.payment_status = 'paid' AND DATE(ord.created_at) BETWEEN '$start_date' AND '$end_date'
            LEFT JOIN menu m ON m.name = oi.item_name
            JOIN menu_recipes mr ON mr.menu_id = COALESCE(oi.menu_id, m.id)
            JOIN ingredients i ON i.id = mr.ingredient_id
        ), 0) as ingredient_cost,
        (SUM(o.total_amount) - COALESCE((
            SELECT SUM(oi.quantity * mr.quantity_required * i.price)
            FROM order_items oi
            JOIN orders ord ON oi.order_id = ord.id AND ord.payment_status = 'paid' AND DATE(ord.created_at) BETWEEN '$start_date' AND '$end_date'
            LEFT JOIN menu m ON m.name = oi.item_name
            JOIN menu_recipes mr ON mr.menu_id = COALESCE(oi.menu_id, m.id)
            JOIN ingredients i ON i.id = mr.ingredient_id
        ), 0)) as net_income,
        (SELECT oi.item_name FROM order_items oi
         JOIN orders ord ON oi.order_id = ord.id
         WHERE DATE(ord.created_at) BETWEEN '$start_date' AND '$end_date'
         AND ord.payment_status = 'paid'
         GROUP BY oi.item_name
         ORDER BY SUM(oi.quantity) DESC
         LIMIT 1) as top_product
    FROM orders o
    WHERE o.payment_status = 'paid'
    AND DATE(o.created_at) BETWEEN '$start_date' AND '$end_date'";
}

$result = $conn->query($query);

if ($result) {
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $data]);
} else {
    echo json_encode(["status" => "error", "message" => "Query failed: " . $conn->error]);
}

$conn->close();
?>
