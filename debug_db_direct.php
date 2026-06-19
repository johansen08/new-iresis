<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

echo "Connected to DB\n";

echo "\n--- Roles (tblhakakses) ---\n";
echo "\n--- Describe tblhakakses ---\n";
$result = $mysqli->query("DESCRIBE tblhakakses");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
}

echo "\n--- Roles (tblhakakses) ---\n";
$result = $mysqli->query("SELECT * FROM tblhakakses");
$param_roles = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id_hakakses'] . " | Name: " . $row['akses'] . "\n"; // Changed to 'akses' based on describe
        $param_roles[$row['id_hakakses']] = $row['akses'];
    }
    $result->free();
}

echo "\n--- Menus (menu) matching 'retur' ---\n";
$result = $mysqli->query("SELECT * FROM menu WHERE name LIKE '%retur%'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | URI: " . $row['uri'] . "\n";
    }
    $result->free();
}

echo "\n--- Menus (menu) matching 'dashboard' or 'home' ---\n";
$result = $mysqli->query("SELECT * FROM menu WHERE name LIKE '%dashboard%' OR name LIKE '%home%'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | URI: " . $row['uri'] . "\n";
    }
    $result->free();
}

echo "\n--- Checking Permissions ---\n";
foreach ($param_roles as $id => $name) {
    if (stripos($name, 'retur') !== false) {
        echo "\nRole: $name (ID: $id)\n";
        $sql = "SELECT m.name, m.uri FROM roleaccess ra JOIN menu m ON m.id = ra.menuid WHERE ra.roleid = $id";
        $res = $mysqli->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                echo "- " . $row['name'] . " (" . $row['uri'] . ")\n";
            }
        }
    }
}

$mysqli->close();
