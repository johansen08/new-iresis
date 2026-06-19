<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

echo "--- PARENT MENU (id=1) ---\n";
$result = $mysqli->query("SELECT * FROM menu WHERE id = 1");
while($row = $result->fetch_assoc()) {
    print_r($row);
}

echo "\n--- TABLES starting with role --- \n";
$result = $mysqli->query("SHOW TABLES LIKE 'role%'");
while($row = $result->fetch_row()) {
    print_r($row);
}

echo "\n--- SAMPLE: roleaccess --- \n";
$result = $mysqli->query("SELECT * FROM roleaccess LIMIT 5");
while($row = $result->fetch_assoc()) {
    print_r($row);
}

$mysqli->close();
