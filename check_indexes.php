<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");
$res = $mysqli->query("SHOW INDEX FROM tblprintresi");
while($row = $res->fetch_assoc()) {
    echo $row['Table'] . " [" . $row['Key_name'] . "] -> " . $row['Column_name'] . "\n";
}
$mysqli->close();
