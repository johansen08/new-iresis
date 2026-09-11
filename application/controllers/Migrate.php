<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migrate extends CI_Controller {
    public function index() {
        $this->load->database();
        $query = "ALTER TABLE tblprintresi ADD COLUMN assigned_picker VARCHAR(50) DEFAULT NULL AFTER status_pesanan";
        if ($this->db->query($query)) {
            echo "Success adding assigned_picker column to tblprintresi";
        } else {
            echo "Failed or column already exists";
        }
    }
}
