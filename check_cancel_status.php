<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");
$q = $mysqli->query("SELECT batal, COUNT(*) as cnt FROM tblprintresi WHERE batal IS NOT NULL AND batal != '' GROUP BY batal");
while($r = $q->fetch_assoc()) {
    echo $r['batal'] . ': ' . $r['cnt'] . "\n";
}
$mysqli->close();
