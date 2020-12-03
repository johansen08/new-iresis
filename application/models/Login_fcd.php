<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login_fcd extends CI_Model {

    function auth($username, $password)
    {
        $criterias['user.username'] = $username;
        $criterias['user.password'] = $password;
        $criterias['user.isactive'] = TRUE;
        $criterias['param.paramgroup'] = PARAMGROUP_ROLE;

        $this->db->select('user.*, param.paramvalue1 role');
        
        $this->db->join('param', 'param.id = user.roleid');

        $this->db->where($criterias);
        
        return $this->db->get('user');
    }

    function update_last_login($user_id)
    {
        $this->db->where('id', $user_id);
        
        $this->db->update('user', array('lastlogin' => date('Y-m-d H:i:s')));
    }
}
