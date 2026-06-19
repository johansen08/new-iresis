<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'iresis-prod');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$res = $conn->query("SELECT * FROM tblsku LIMIT 20");
$data = [];
while($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data, JSON_PRETTY_PRINT);
