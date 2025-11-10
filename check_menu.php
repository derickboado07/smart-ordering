<?php
include 'backend/db_connect.php';

$res = $conn->query('DESCRIBE menu');
if($res){
    echo 'menu table structure:' . PHP_EOL;
    while($r = $res->fetch_assoc()){
        echo '  ' . $r['Field'] . ' ' . $r['Type'] . ' ' . ($r['Null']=='NO'?'NOT NULL':'NULL') . ' ' . ($r['Key']?'KEY':'') . ' ' . ($r['Default']!==null?'DEFAULT '.$r['Default']:'') . ' ' . ($r['Extra']?'EXTRA':'') . PHP_EOL;
    }
} else {
    echo 'menu table does not exist.' . PHP_EOL;
}
?>
