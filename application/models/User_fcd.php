<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_fcd extends CI_Model {

    function get_user($user_id = null)
    {
        $criterias['user.isactive'] = TRUE;
        $criterias['role.isactive'] = TRUE;

        if (!empty($user_id)) {
            $criterias['user.id'] = $user_id;
        }

        $this->db->select('user.*, role.paramvalue1 rolename');

        $this->db->join('param role', 'role.id = user.roleid', 'left');

        $this->db->where($criterias);

        return $this->db->get('user');
    }

    function save($user, $user_id)
    {
        if (empty($user['id'])) {
            $user['isactive'] = TRUE;
            $user['createdby'] = $user_id;
            $user['created'] = date('Y-m-d H:i:s');

            $this->db->insert('user', $user);

            $user['id'] = $this->db->insert_id();
            $user['affected_rows'] = 1;
        } else {
            unset($user['password']); // admin can not update user password

            $user['updatedby'] = $user_id;
            $user['updated'] = date('Y-m-d H:i:s');

            $this->db->where(array('id' => $user['id']));

            $this->db->update('user', $user);

            $user['affected_rows'] = $this->db->affected_rows();
        }

        return $user;
    }

    function update_password($user, $user_id)
    {
        $user['updatedby'] = $user_id;
        $user['updated'] = date('Y-m-d H:i:s');

        $this->db->where(array('id' => $user['id']));

        $this->db->update('user', $user);

        $user['affected_rows'] = $this->db->affected_rows();

        return $user;
    }
}
