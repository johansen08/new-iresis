<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Role_fcd extends CI_Model {

    function get_role($role_id = null)
    {
        $criterias['isactive'] = TRUE;
        $criterias['paramgroup'] = PARAMGROUP_ROLE;

        if (!empty($role_id)) {
            $criterias['id'] = $role_id;
        }

        $this->db->where($criterias);

        return $this->db->get('param');
    }

    function save($role, $user_id)
    {
        if (empty($role['id'])) {
            $role['isactive'] = TRUE;
            $role['createdby'] = $user_id;
            $role['created'] = date('Y-m-d H:i:s');

            $this->db->insert('param', $role);

            $role['id'] = $this->db->insert_id();
            $role['affected_rows'] = 1;
        } else {
            $role['updatedby'] = $user_id;
            $role['updated'] = date('Y-m-d H:i:s');

            $this->db->where(array('id' => $role['id']));

            $this->db->update('param', $role);

            $role['affected_rows'] = $this->db->affected_rows();
        }

        return $role;
    }
}
