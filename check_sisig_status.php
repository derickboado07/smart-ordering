<?php
require_once 'backend/db_connect.php';

$res = $conn->query("SELECT id, name, status FROM menu WHERE name LIKE '%Sisig%'");
if($res){
    while($r = $res->fetch_assoc()){
        echo 'ID: ' . $r['id'] . ', Name: ' . $r['name'] . ', Status: ' . $r['status'] . "\n";
    }
} else {
    echo 'Query failed.' . "\n";
}

$conn->close();
?>
