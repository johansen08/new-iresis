<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

echo "--- TABLE: menu ---\n";
$result = $mysqli->query("DESCRIBE menu");
while($row = $result->fetch_assoc()) {
    print_r($row);
}

echo "\n--- SAMPLE: menu (Selisih Paket) ---\n";
$result = $mysqli->query("SELECT * FROM menu WHERE name LIKE '%Selisih%'");
while($row = $result->fetch_assoc()) {
    print_r($row);
}

echo "\n--- TABLE: role ---\n";
$result = $mysqli->query("SELECT * FROM role");
while($row = $result->fetch_assoc()) {
    print_r($row);
}

$mysqli->close();
