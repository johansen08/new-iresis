<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Menu_fcd extends CI_Model {

    function get_menu($menu_id = null, $role_id = null)
    {
        $criterias['menu.isactive'] = TRUE;

        if (!empty($menu_id)) {
            $criterias['menu.id'] = $menu_id;
        }

        $select = array(
            'menu.*',
            'parent.name parentname'
        );

        $this->db->join('menu parent', 'parent.id = menu.parentid and parent.isactive = '.TRUE, 'left');

        if (!empty($role_id)) {
            $select[] = 'ra.id selected';
            $this->db->join('roleaccess ra', 'ra.menuid = menu.id and ra.roleid = '.$role_id, 'left');
        }

        $this->db->select($select);
        
        $this->db->where($criterias);
        
        $this->db->order_by('menu.parentid asc, menu.sortorder asc');

        return $this->db->get('menu');
    }

    function get_parent($parent_id = null)
    {
        $criterias['isactive'] = TRUE;
        $criterias['uri'] = null;

        $this->db->where($criterias);

        $this->db->select('id, name');

        return $this->db->get('menu');
    }

    function save($menu, $user_id)
    {
        if (empty($menu['id'])) {
            $menu['isactive'] = TRUE;
            $menu['createdby'] = $user_id;
            $menu['created'] = date('Y-m-d H:i:s');

            $this->db->insert('menu', $menu);

            $menu['id'] = $this->db->insert_id();
            $menu['affected_rows'] = 1;
        } else {
            $menu['updatedby'] = $user_id;
            $menu['updated'] = date('Y-m-d H:i:s');

            $this->db->where(array('id' => $menu['id']));

            $this->db->update('menu', $menu);

            $menu['affected_rows'] = $this->db->affected_rows();
        }

        return $menu;
    }
}
