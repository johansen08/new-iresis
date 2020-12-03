<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bgprocess extends CI_Controller {

    function __construct()
    {
        parent::__construct();
    }

    public function send_email_blast()
    {
        try {
            $email_blast = json_decode($this->input->post('email_blast'), true);

            if (empty($email_blast)) {
                die(DATA_NOT_FOUND);
            }
    
            $list_recipients = json_decode($this->input->post('list_recipients'), true);
            
            // load notif lib
            $this->load->library('notif');
    
            // load model email_blast_fcd
            $this->load->model('email_blast_fcd');
    
            foreach ($list_recipients as $recipients):
                if ($recipients['status']) 
                    continue;
    
                $name = ucwords($recipients['firstname'].' '.$recipients['lastname']);
    
                $content = $this->load->view('email_blast_template/tmp_'.$email_blast['file_template'], $recipients, true);
    
                $recipients['sent_date'] = date('Y-m-d H:i:s');
                $send = $this->notif->send(['email' => $recipients['email'], 'name' => $name], $email_blast['subject'], $content);
    
                $recipients['status'] = $send;
                $this->email_blast_fcd->update_recipients($recipients);
            endforeach;
            
            if ($this->email_blast_fcd->is_finished($email_blast)) {
                $email_blast['status'] = EMAIL_BLAST_STATUS['DONE'];
                
                $this->email_blast_fcd->save($email_blast);
            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }
}