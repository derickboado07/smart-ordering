<?php
include 'backend/db_connect.php';

$res = $conn->query('SELECT id, name FROM menu WHERE LOWER(name) LIKE "%sisig%"');
if($res){
    while($r = $res->fetch_assoc()){
        echo 'ID: ' . $r['id'] . ' Name: ' . $r['name'] . PHP_EOL;
    }
}
?>
