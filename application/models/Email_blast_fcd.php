<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Email_blast_fcd extends CI_Model {

    function get_email_blast($email_blast_id = null)
    {
        $criterias['email_blast.is_active'] = TRUE;
        $criterias['admin.is_active'] = TRUE;

        if (!empty($email_blast_id)) {
            $criterias['email_blast.id'] = $email_blast_id;
        }

        $this->db->select('email_blast.*, admin.username created_by_name');

        $this->db->join('yo_admin admin', 'admin.id = email_blast.created_by', 'left');

        $this->db->where($criterias);

        return $this->db->get('yo_email_blast email_blast');
    }

    function save($email_blast, $user_id)
    {
        if (empty($email_blast['id'])) {
            $email_blast['is_active'] = TRUE;
            $email_blast['created_date'] = date('Y-m-d H:i:s');
            $email_blast['created_by'] = $user_id;

            $this->db->insert('yo_email_blast', $email_blast);

            $email_blast['id'] = $this->db->insert_id();
            $email_blast['affected_rows'] = 1;
        } else {
            $email_blast['modified_date'] = date('Y-m-d H:i:s');
            $email_blast['modified_by'] = $user_id;

            $this->db->where(array('id' => $email_blast['id']));

            $this->db->update('yo_email_blast', $email_blast);

            $email_blast['affected_rows'] = $this->db->affected_rows();
        }

        return $email_blast;
    }

    function is_finished($email_blast)
    {
        $criterias['email_blast_id'] = $email_blast['id'];
        $criterias['status is not null'] = null;

        $this->db->where($criterias);

        $num = $this->db->count_all_results('yo_log_email_blast');

        return $num > 0;
    }

    function count_summary($email_blast)
    {
        $criterias['email_blast_id'] = $email_blast['id'];

        $this->db->where($criterias);
        $query = $this->db->get('yo_log_email_blast');
        $email_blast_count['total_email'] = $query->num_rows();

        $criterias['status'] = CONST_YES_NO['Yes'];
        $this->db->where($criterias);
        $query = $this->db->get('yo_log_email_blast');
        $email_blast_count['total_success_email'] = $query->num_rows();

        $criterias['status'] = CONST_YES_NO['No'];
        $this->db->where($criterias);
        $query = $this->db->get('yo_log_email_blast');
        $email_blast_count['total_failed_email'] = $query->num_rows();

        return $email_blast_count;
    }

    function save_recipients($email_blast)
    {
        $this->db->trans_start();

        // delete first old data
        $this->db->delete('yo_log_email_blast', array('email_blast_id' => $email_blast['id']));

        // prepare data for send all type
        if ($email_blast['upload_type'] == SEND_EMAIL_ALL) {

            $select = $this->db->select($email_blast['id'].' as email_blast_id, uid, username, firstname, lastname, email')
                ->where('confirm_email', TRUE)
                ->get('yo_user');

            if ($select->num_rows()) {
                $email_blast['list_recipients'] = $select->result_array();
            }

        }

        if (!empty($email_blast['list_recipients'])) {
            $this->db->insert_batch('yo_log_email_blast', $email_blast['list_recipients']);
        }

        $email_blast['affected_rows'] = $this->db->affected_rows();

        $this->db->trans_complete();

        return $email_blast;
    }

    function update_recipients($recipients)
    {
        $this->db->where(array('id' => $recipients['id']));

        $this->db->update('yo_log_email_blast', $recipients);

        $recipients['affected_rows'] = $this->db->affected_rows();

        return $recipients;
    }

    function get_recipients($email_blast)
    {
        $this->db->where('email_blast_id', $email_blast['id']);
        
        return $this->db->get('yo_log_email_blast');
    }

    function get_data_recipients($data)
    {
        if($data['order'] != null) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
        }

        if(!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->where('email_blast_id', $data['email_blast_id']);

        $this->db->limit($data['length'], $data['start']);

        return $this->db->get('yo_log_email_blast');
    }

    function total_data_recipients($data)
    {
        if(!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->where('email_blast_id', $data['email_blast_id']);

        $query = $this->db->select('count(1) as num')->get('yo_log_email_blast');
        $result = $query->row();

        return isset($result) ? $result->num : 0;
    }
}
