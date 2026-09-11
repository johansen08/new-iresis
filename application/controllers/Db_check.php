<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Db_check extends CI_Controller {
    public function check_menu() {
        $res = $this->db->get('menu')->result_array();
        foreach ($res as $row) {
            echo "ID: {$row['id']} | Name: {$row['name']} | Icon: {$row['icon']} | Parent: {$row['parentid']}\n";
        }
    }
}
