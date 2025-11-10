<?php
include 'backend/db_connect.php';

$tables = ['menu', 'ingredients', 'menu_recipes', 'menu_inventory'];

foreach($tables as $t){
    $res = $conn->query("DESCRIBE $t");
    if($res){
        echo "Table $t exists:\n";
        while($r = $res->fetch_assoc()){
            echo '  ' . $r['Field'] . ' ' . $r['Type'] . ' ' . ($r['Null']=='NO'?'NOT NULL':'NULL') . ' ' . ($r['Key']?'KEY':'') . ' ' . ($r['Default']!==null?'DEFAULT '.$r['Default']:'') . ' ' . ($r['Extra']?'EXTRA':'') . "\n";
        }
    } else {
        echo "Table $t does not exist.\n";
    }
    echo "\n";
}
?>
