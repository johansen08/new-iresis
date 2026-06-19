<?php
define('BASEPATH', 'dummy');
require 'application/config/database.php';
$db_info = $db['default'];

$conn = new mysqli($db_info['hostname'], $db_info['username'], $db_info['password'], $db_info['database']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "--- tblresiambilbarang ---\n";
$result = $conn->query("SELECT * FROM tblresiambilbarang LIMIT 10");
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo json_encode($row) . "\n";
    }
}

$conn->close();
