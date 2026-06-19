<?php
define('BASEPATH', 'dummy');
require 'application/config/database.php';
$db_info = $db['default'];

$conn = new mysqli($db_info['hostname'], $db_info['username'], $db_info['password'], $db_info['database']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("DESCRIBE tblsku");
if ($result) {
    while($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
