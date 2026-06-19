<?php
define('BASEPATH', 'dummy');
require 'application/config/database.php';
$db_info = $db['default'];

$conn = new mysqli($db_info['hostname'], $db_info['username'], $db_info['password'], $db_info['database']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SHOW TABLES");
if ($result) {
    while($row = $result->fetch_array()) {
        echo $row[0] . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
