<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Access_fcd extends CI_Model {

    function update($access, $user_id)
    {
        $this->db->trans_start();

        $this->db->delete('roleaccess', array('roleid' => $access['roleid']));

        $affected_rows = 0;
        if (!empty($access['list'])) {
            foreach ($access['list'] as $data):
                if ($data == 0) {
                    continue;
                }
    
                $insert_data['menuid'] = $data;
                $insert_data['roleid'] = $access['roleid'];
                $insert_data['createdby'] = $user_id;
                $insert_data['created'] = date('Y-m-d H:i:s');
    
                $this->db->insert('roleaccess', $insert_data);
    
                $affected_rows++;
            endforeach;
        }

        $access['affected_rows'] = $affected_rows;

        $this->db->trans_complete();

        return $access;
    }

    function get_access_menu($role_id)
    {
        $sql = '
            SELECT * FROM (
                SELECT * 
                FROM menu 
                WHERE id IN (SELECT menuid FROM roleaccess WHERE roleid = '.$role_id.')
                AND isactive = '.TRUE.'
                UNION 
                SELECT * 
                FROM menu 
                WHERE id IN (SELECT parentid FROM menu WHERE id IN (SELECT menuid FROM roleaccess WHERE roleid = '.$role_id.'))
                AND isactive = '.TRUE.'
            ) foo 
            ORDER BY parentid ASC, sortorder ASC
        ';

        $query = $this->db->query($sql);

        return $query;
    }
}
