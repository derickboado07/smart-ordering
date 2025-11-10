<?php
header('Content-Type: application/json');
include 'db_connect.php';

$start_date = isset($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : null;
$end_date = isset($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : null;

// Build common WHERE clause parts (used for both grouped counts and total)
$where_clauses = ["payment_status = 'paid'", "payment_method IS NOT NULL", "payment_method != ''"];
if ($start_date && $end_date) {
    $where_clauses[] = "DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
} elseif ($end_date) {
    $where_clauses[] = "DATE(created_at) = '$end_date'";
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Subquery that groups counts by normalized payment method
$grouped_sql = "(
    SELECT
        CASE
            WHEN LOWER(payment_method) LIKE 'gcash%' THEN 'GCash'
            WHEN LOWER(payment_method) = 'qrph' THEN 'QRPH'
            WHEN LOWER(payment_method) = 'cash' THEN 'Cash'
            ELSE 'Other'
        END AS payment_method,
        COUNT(*) AS cnt
    FROM orders
    $where_sql
    GROUP BY payment_method
) AS pm";

// Total count for the same filters
$total_sql = "(
    SELECT COUNT(*) AS total_count FROM orders $where_sql
) AS total_count_tbl";

$query = "SELECT pm.payment_method, ROUND(pm.cnt * 100.0 / GREATEST(total_count_tbl.total_count,1), 2) AS percentage
FROM $grouped_sql
CROSS JOIN $total_sql
ORDER BY percentage DESC";

$result = $conn->query($query);

if ($result) {
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // make sure percentage is numeric
        $row['percentage'] = isset($row['percentage']) ? (float)$row['percentage'] : 0.0;
        $data[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $data]);
} else {
    echo json_encode(["status" => "error", "message" => "Query failed: " . $conn->error]);
}

$conn->close();
?>
