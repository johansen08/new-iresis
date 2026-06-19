<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

echo "Connected to DB\n";

// 1. Create Role 'tim retur'
$role_name = 'tim retur';
// Check if exists first
$check = $mysqli->query("SELECT id_hakakses FROM tblhakakses WHERE akses = '$role_name'");
if ($check->num_rows > 0) {
    $role_id = $check->fetch_object()->id_hakakses;
    echo "Role '$role_name' already exists with ID: $role_id\n";
} else {
    if ($mysqli->query("INSERT INTO tblhakakses (akses) VALUES ('$role_name')")) {
        $role_id = $mysqli->insert_id;
        echo "Created role '$role_name' with ID: $role_id\n";
    } else {
        die("Error creating role: " . $mysqli->error);
    }
}

// 2. Assign Menus
$menu_ids = [30, 31, 32, 44, 57, 58, 60];
$user_id = 1; // Assuming admin/system action, using ID 1 for createdby
$timestamp = date('Y-m-d H:i:s');

echo "Assigning menus to Role ID $role_id...\n";

// Get menu names for logging
$menu_names = [];
$q_menu = $mysqli->query("SELECT id, name FROM menu WHERE id IN (" . implode(',', $menu_ids) . ")");
while ($r_menu = $q_menu->fetch_object()) {
    $menu_names[$r_menu->id] = $r_menu->name;
}

foreach ($menu_ids as $mid) {
    $mname = isset($menu_names[$mid]) ? $menu_names[$mid] : "Unknown ID $mid";
    // Check if mapping exists
    $check_map = $mysqli->query("SELECT id FROM roleaccess WHERE roleid = $role_id AND menuid = $mid");
    if ($check_map->num_rows == 0) {
        $sql = "INSERT INTO roleaccess (menuid, roleid, createdby, created) VALUES ($mid, $role_id, $user_id, '$timestamp')";
        if ($mysqli->query($sql)) {
            echo " - Assigned Menu: $mname (ID $mid)\n";
        } else {
            echo " ! Error assigning Menu $mname (ID $mid): " . $mysqli->error . "\n";
        }
    } else {
        echo " - Menu: $mname (ID $mid) already assigned.\n";
    }
}

echo "Done.\n";
$mysqli->close();
