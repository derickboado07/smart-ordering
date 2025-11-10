<?php
include 'backend/db_connect.php';

$id = 37;
$stmt = $conn->prepare('DELETE FROM menu WHERE id = ?');
$stmt->bind_param('i', $id);
$result = $stmt->execute();
echo 'Delete result: ' . ($result ? 'success' : 'failed') . PHP_EOL;
echo 'Affected rows: ' . $stmt->affected_rows . PHP_EOL;
?>
