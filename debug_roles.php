<?php
ob_start();
$_SERVER['HTTP_HOST'] = 'localhost';
include('index.php');
if (ob_get_length()) ob_clean();
$CI =& get_instance();

echo "Roles (tblhakakses):\n";
$roles = $CI->db->get('tblhakakses')->result_array();
foreach ($roles as $r) {
    echo $r['id_hakakses'] . ": " . $r['hak_akses'] . "\n";
}

echo "\nMenus (menu) - searching for 'retur':\n";
$menus = $CI->db->like('name', 'retur')->get('menu')->result_array();
foreach ($menus as $m) {
    echo $m['id'] . ": " . $m['name'] . " (" . $m['uri'] . ")\n";
}

echo "\nChecking permissions for 'Tim Retur' (if exists):\n";
foreach ($roles as $r) {
    if (stripos($r['hak_akses'], 'retur') !== false) {
        echo "Found role: " . $r['hak_akses'] . " (ID: " . $r['id_hakakses'] . ")\n";
        $access = $CI->db->select('m.name, m.uri')->from('roleaccess ra')->join('menu m', 'm.id = ra.menuid')->where('ra.roleid', $r['id_hakakses'])->get()->result_array();
        echo "Access:\n";
        foreach ($access as $a) {
            echo "- " . $a['name'] . " (" . $a['uri'] . ")\n";
        }
    }
}
