<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'iresis-prod');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

echo "ALL TABLES:\n";
$res = $conn->query("SHOW TABLES");
while($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}
