<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

echo "--- ROLES WITH ACCESS TO 'Selisih Paket' (menuid=62) ---\n";
$result = $mysqli->query("SELECT roleid FROM roleaccess WHERE menuid = 62");
while($row = $result->fetch_assoc()) {
    print_r($row);
}

$mysqli->close();
