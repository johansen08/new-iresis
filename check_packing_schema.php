<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");
$res = $mysqli->query("DESCRIBE tblpacking");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
$mysqli->close();
