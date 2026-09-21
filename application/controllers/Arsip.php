<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Mode Arsip — melihat iresis_arsip (semua data sejak awal) lewat aplikasi
 * yang sama, hanya baca. Pengalihan koneksinya ada di
 * MY_Controller::terapkan_mode_arsip(); di sini cuma halaman info + saklar.
 *
 * Siapa yang boleh: role yang punya akses menu `arsip` (halaman Access).
 * Alur lengkap: docs/ARSIP_DATA.md.
 */
class Arsip extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->config->load('arsip', TRUE);
    }

    /** Halaman menu: status sekarang, kesegaran arsip, tombol masuk/keluar. */
    public function index()
    {
        $cfg   = $this->config->item('arsip');
        $arsip = $cfg['db_arsip'];

        $this->data['boleh']        = $this->boleh_mode_arsip();
        $this->data['aktif']        = (bool) $this->session->userdata('mode_arsip');
        $this->data['db_arsip']     = $arsip;
        $this->data['db_live']      = $this->data['aktif'] ? iresis_secret('db_database', 'iresis_prod') : $this->db->database;
        $this->data['retensi_hari'] = (int) $cfg['retensi_hari'];
        $this->data['tulis']        = !empty($cfg['mode_arsip_tulis']);

        // Kesegaran arsip: dibaca dengan prefix DB supaya benar di kedua mode.
        // db_debug dimatikan sementara: kalau arsip belum pernah dibuat, jangan
        // sampai halaman HTML error CI merusak JSON (lihat make_ajax_response).
        $debug_lama = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $status = NULL;
        $gagal  = array();
        try {
            $q = $this->db->query("SELECT MAX(terakhir_jalan) terakhir, COUNT(*) jml_tabel,
                    SUM(mode = 'lewati') dilewati, SUM(keterangan = 'sedang berjalan') berjalan
                FROM `$arsip`.`_arsip_status`");
            $status = $q ? $q->row() : NULL;

            $q = $this->db->query("SELECT waktu, tahap, tabel, LEFT(pesan, 160) pesan FROM `$arsip`.`_arsip_log`
                WHERE status = 'FAIL' AND waktu >= NOW() - INTERVAL 3 DAY ORDER BY id DESC LIMIT 5");
            $gagal = $q ? $q->result() : array();
        } catch (Throwable $e) {
            $status = NULL;
        }
        $this->db->db_debug = $debug_lama;

        $this->data['status'] = $status;
        $this->data['gagal']  = $gagal;

        $this->show($this->data, 'arsip/index');
    }

    /** Nyalakan Mode Arsip untuk sesi ini lalu muat ulang aplikasi. */
    public function masuk()
    {
        if (!$this->boleh_mode_arsip()) {
            $this->set_message('Mode Arsip', 'Role Anda tidak punya akses menu Mode Arsip. Minta admin mencentangnya di halaman Access.', 'danger');
            redirect('/');
            return;
        }
        $this->session->set_userdata('mode_arsip', 1);
        redirect('/');
    }

    /** Kembali ke data live. */
    public function keluar()
    {
        $this->session->unset_userdata('mode_arsip');
        redirect('/');
    }
}
