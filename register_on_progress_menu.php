<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

$parent_id = 1; // Resi Team parent menu ID
$name = 'Paket On Progress';
$uri = 'resi_team/paket_on_progress';
$icon = 'fa fa-tasks';
$sort_order = 12;

// 1. Check if menu already exists
$check = $mysqli->query("SELECT id FROM menu WHERE uri = '$uri'");
if ($check->num_rows > 0) {
    $menu_id = $check->fetch_assoc()['id'];
    echo "Menu already exists with ID: $menu_id. Updating...\n";
    $mysqli->query("UPDATE menu SET name = '$name', icon = '$icon', sortorder = $sort_order WHERE id = $menu_id");
} else {
    echo "Creating new menu...\n";
    $mysqli->query("INSERT INTO menu (parentid, name, uri, icon, sortorder, isactive, created) VALUES ($parent_id, '$name', '$uri', '$icon', $sort_order, 1, NOW())");
    $menu_id = $mysqli->insert_id;
    echo "Menu created with ID: $menu_id\n";
}

// 2. Assign access to roles 1 (Admin) and 2 (Resi Team)
$roles = [1, 2];
foreach ($roles as $role_id) {
    $check_access = $mysqli->query("SELECT id FROM roleaccess WHERE menuid = $menu_id AND roleid = $role_id");
    if ($check_access->num_rows == 0) {
        echo "Granting access to role $role_id...\n";
        $mysqli->query("INSERT INTO roleaccess (menuid, roleid, created) VALUES ($menu_id, $role_id, NOW())");
    } else {
        echo "Role $role_id already has access.\n";
    }
}

$mysqli->close();
echo "Registration complete.\n";
