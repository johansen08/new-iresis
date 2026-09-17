<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{

    protected $data;

    public function __construct()
    {
        header("Access-Control-Allow-Origin: *");
        parent::__construct();

        if (!$this->session->userdata('user')) {
            if ($this->input->is_ajax_request()) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 401, 'message' => 'Session expired. Please login again.']);
                exit;
            } else {
                redirect('login');
            }
        } else {
            $this->validate();

            $this->data['user'] = $this->session->userdata('user');
            $this->data['nama_pk'] = $this->session->userdata('nama_pk');
            $this->data['status_performa'] = $this->session->userdata('status_performa');

            $this->jalankan_bootstrap_sekali();

            $this->data['html_menu_tree'] = $this->ambil_menu_tree();
        }
    }

    /**
     * Versi bootstrap.
     *
     * NAIKKAN nilai ini setiap kali ada migrasi atau menu baru ditambahkan di
     * berkas ini. Itulah satu-satunya pemicu agar blok migrasi dijalankan ulang
     * di server, sekaligus membuang cache pohon menu semua pengguna.
     */
    const BOOTSTRAP_VERSI = '2026-09-17.6';

    /**
     * Menjalankan seluruh migrasi + auto-create menu SEKALI saja per versi.
     *
     * Sebelumnya blok ini jalan di SETIAP request, termasuk ~16rb POST scan HO
     * per hari, dengan ongkos ~98 query plus sederet DDL. Pengukuran 2026-08-10
     * di menu Scan HO membuktikan biayanya: total waktu server 228 ms, dan
     * boot_ms-nya juga 228 ms -- seluruh waktu habis di sini sebelum kode menu
     * tersentuh sama sekali. Sisi jaringan cuma 2 ms.
     *
     * Penanda sengaja berupa berkas, bukan variabel statis: dengan mod_php tiap
     * request memulai ulang state PHP, jadi statis tidak bertahan lintas
     * request. Kalau penanda gagal ditulis (mis. folder tidak bisa ditulisi),
     * migrasi tetap jalan seperti dulu -- lambat, tapi tidak pernah gagal.
     *
     * Dijaga kunci berkas (flock) karena pada 2026-09-14 dua request tiba
     * bersamaan tepat setelah versi dinaikkan: keduanya membaca penanda lama,
     * keduanya menjalankan migrasi, dan menu "Video Packing" pun tercipta dua
     * kali (id 175 & 176). Request kedua kini menunggu yang pertama selesai,
     * lalu membaca ulang penanda dan langsung keluar.
     */
    protected function jalankan_bootstrap_sekali()
    {
        $penanda = APPPATH . 'cache/bootstrap_migrasi.txt';

        if ($this->bootstrap_sudah_versi_ini($penanda)) {
            return FALSE;
        }

        $kunci = @fopen(APPPATH . 'cache/bootstrap_migrasi.lock', 'c');
        if ($kunci) {
            @flock($kunci, LOCK_EX);
            // Cek ulang setelah dapat kunci: request lain mungkin baru saja selesai.
            if ($this->bootstrap_sudah_versi_ini($penanda)) {
                @flock($kunci, LOCK_UN);
                @fclose($kunci);
                return FALSE;
            }
        }

        $this->migrasi_menu_cek_sku();
        $this->run_display_return_migrations();
        $this->run_daftar_sku_migrations();
        $this->run_accounting_menu_migrations();
        $this->run_complain_migrations();
        $this->run_cancel_order_migrations();
        $this->run_tracking_picker_migration();
        $this->run_menu_scan_packer_webcam();
        $this->run_batal_scan_packer_migration();
        $this->run_video_packing_migration();
        $this->run_nonaktifkan_menu_ngrok();
        $this->run_nonaktifkan_menu_tanpa_route();
        $this->run_masalah_picker_new_migration();

        @file_put_contents($penanda, self::BOOTSTRAP_VERSI, LOCK_EX);

        if ($kunci) {
            @flock($kunci, LOCK_UN);
            @fclose($kunci);
        }

        return TRUE;
    }

    protected function bootstrap_sudah_versi_ini($penanda)
    {
        return is_file($penanda) && trim((string) @file_get_contents($penanda)) === self::BOOTSTRAP_VERSI;
    }

    /**
     * Menu + hak akses "Cek SKU Retur". Isinya persis seperti sebelumnya,
     * hanya dipindah keluar dari konstruktor supaya ikut terjaga penanda versi.
     */
    protected function migrasi_menu_cek_sku()
    {
        // Automatically create menu and role access for Cek SKU if not present
        $menu = $this->db->get_where('menu', ['uri' => 'retur/cek-sku'])->row();
        if (!$menu) {
            $scan_menu = $this->db->get_where('menu', ['uri' => 'retur/scan_retur'])->row();
            $parent_id = $scan_menu ? $scan_menu->parentid : 30; // fallback to 30

            $menu_data = [
                'name'      => 'Cek SKU Retur',
                'parentid'  => $parent_id,
                'uri'       => 'retur/cek-sku',
                'icon'      => 'fa fa-search',
                'sortorder' => 15,
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ];
            $this->db->insert('menu', $menu_data);
            $menu_id = $this->db->insert_id();
        } else {
            $menu_id = $menu->id;
            $parent_id = $menu->parentid;
            if ($parent_id == 0) {
                $scan_menu = $this->db->get_where('menu', ['uri' => 'retur/scan_retur'])->row();
                $parent_id = $scan_menu ? $scan_menu->parentid : 30;
                $this->db->where('id', $menu_id)->update('menu', ['parentid' => $parent_id]);
            }
        }

        // Ensure role access is granted for all roles that have access to scan_retur (ID 31)
        $roles = $this->db->get_where('roleaccess', ['menuid' => 31])->result();
        foreach ($roles as $r) {
            $access_exist = $this->db->get_where('roleaccess', ['roleid' => $r->roleid, 'menuid' => $menu_id])->row();
            if (!$access_exist) {
                $this->db->insert('roleaccess', [
                    'roleid'    => $r->roleid,
                    'menuid'    => $menu_id,
                    'created'   => date('Y-m-d H:i:s'),
                    'createdby' => 1
                ]);
            }
        }
    }

    /**
     * Pohon menu HTML, dibangun sekali lalu disimpan di session.
     *
     * Dulu selalu dibangun ulang tiap request "to prevent stale caching". Cache
     * di sini dikunci ke BOOTSTRAP_VERSI + hak akses pengguna, jadi menu baru
     * tetap muncul serentak begitu versinya dinaikkan -- tanpa membayar query
     * menu di setiap request.
     *
     * Kalau menu diubah langsung lewat DB tanpa menaikkan versi, perubahan baru
     * terlihat setelah pengguna login ulang.
     */
    protected function ambil_menu_tree()
    {
        $peran = isset($this->data['user']['hakakses']) ? $this->data['user']['hakakses'] : '';
        $kunci = self::BOOTSTRAP_VERSI . '|' . $peran;

        $tersimpan = $this->session->userdata('html_menu_tree');
        if (!empty($tersimpan) && $this->session->userdata('html_menu_tree_kunci') === $kunci) {
            return $tersimpan;
        }

        $this->load->model('access_fcd');
        $this->load->helper('menu_helper');

        $list_menu = $this->access_fcd->get_access_menu($peran)->result_array();
        if (empty($list_menu)) {
            return $tersimpan;
        }

        $list_menu_tree  = menu_to_tree($list_menu, $list_menu[0]);
        $html_menu_tree  = tree_to_html_menu($list_menu_tree['child']);

        $this->session->set_userdata([
            'html_menu_tree'       => $html_menu_tree,
            'html_menu_tree_kunci' => $kunci,
        ]);

        return $html_menu_tree;
    }

    public function validate()
    {
        if ($this->router->class == 'welcome') {
            return true;
        }

        $class = $this->router->class;
        $method = $this->router->method == 'index' ? '' : '/' . $this->router->method; // leave it blank when it is `index` method
    }

    public function show($data_content = null, $view_path = null)
    {
        // Clear any output buffer to prevent HTML/whitespace before JSON
        if (ob_get_length()) ob_clean();
        
        // Set JSON header and prevent browser caching of SPA views
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        
        $view = $view_path ? $view_path : $this->router->class . '/' . $this->router->method;

        $json = json_encode(array(
            'view' => $this->load->view($view, $data_content, TRUE),
            'message' => empty($data_content['message']) ? null : $data_content['message'],
        ));

        // JANGAN dikembalikan ke 'echo'. Dengan echo, CI_Output::$final_output tetap
        // NULL, lalu CI_Output::_display() menjalankan str_replace() dengan subject
        // NULL -> "Deprecated" di PHP 8.1+. Karena ENVIRONMENT development menyalakan
        // display_errors, kotak HTML error itu ikut tercetak SESUDAH JSON. Respons
        // jadi bukan JSON valid, jQuery gagal parse, dan plugins.js membuang seluruh
        // body mentah ke .page-content-wrap -- halaman tampil sebagai teks JSON.
        $this->output
            ->set_content_type('application/json')
            ->set_output($json);
    }

    public function show_index($override_index = null)
    {
        redirect($this->router->class . ($override_index ? '/' . $override_index : ''));
    }

    public function show_page($override_index = null)
    {
        $this->show_index($override_index);
    }

    public function set_message($title, $message, $type)
    {
        $this->session->set_flashdata('message', array(
            'title' => $title,
            'message' => $message,
            'type' => $type,
        ));
    }

    public function make_ajax_response($status_code, $message, $data = [])
    {
        // Clear ALL output buffer levels to prevent HTML/whitespace before JSON
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Always send HTTP 200 - communicating status via JSON body
        // set_status_header(4xx) in CodeIgniter returns an HTML error page which
        // breaks all AJAX JSON parsing in plugins.js
        header('Content-Type: application/json');

        $response = array(
            'code'    => $status_code,
            'message' => $message,
        );

        if (!empty($data)) {
            $response['data'] = $data;
        }

        echo json_encode($response);
        exit();
    }

    protected function run_display_return_migrations()
    {
        // 1. Create tables
        $this->db->query("CREATE TABLE IF NOT EXISTS `tblretur_display_batch` (
          `id_batch` int(11) NOT NULL AUTO_INCREMENT,
          `kode_batch` varchar(50) NOT NULL,
          `status` enum('DIKIRIM','DITERIMA') NOT NULL DEFAULT 'DIKIRIM',
          `total_qty` int(11) NOT NULL DEFAULT 0,
          `created_by` int(11) NOT NULL,
          `created_at` datetime NOT NULL,
          `received_by` int(11) DEFAULT NULL,
          `received_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id_batch`),
          UNIQUE KEY `idx_kode_batch` (`kode_batch`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `tblretur_display_batch_detail` (
          `id_detail` int(11) NOT NULL AUTO_INCREMENT,
          `batch_id` int(11) NOT NULL,
          `id_bukaretur` int(11) NOT NULL,
          `qty` int(11) NOT NULL DEFAULT 1,
          `keterangan` text DEFAULT NULL,
          PRIMARY KEY (`id_detail`),
          KEY `idx_batch_id` (`batch_id`),
          KEY `idx_id_bukaretur` (`id_bukaretur`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 2. Automatically create menu and role access for Kirim ke Display (TIM RETUR, parentid = 30)
        $menu_kirim = $this->db->get_where('menu', ['uri' => 'retur/kirim-display'])->row();
        if (!$menu_kirim) {
            $menu_data = [
                'name'      => 'Kirim ke Display',
                'parentid'  => 30,
                'uri'       => 'retur/kirim-display',
                'icon'      => 'fa fa-send',
                'sortorder' => 18,
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ];
            $this->db->insert('menu', $menu_data);
            $menu_kirim_id = $this->db->insert_id();
        } else {
            $menu_kirim_id = $menu_kirim->id;
        }

        // Grant access to Kirim ke Display for roles 1, 2, 3, 6 (same as Scan Retur / Cek SKU)
        $kirim_roles = [1, 2, 3, 6];
        foreach ($kirim_roles as $role_id) {
            $access_exist = $this->db->get_where('roleaccess', ['roleid' => $role_id, 'menuid' => $menu_kirim_id])->row();
            if (!$access_exist) {
                $this->db->insert('roleaccess', [
                    'roleid'    => $role_id,
                    'menuid'    => $menu_kirim_id,
                    'created'   => date('Y-m-d H:i:s'),
                    'createdby' => 1
                ]);
            }
        }

        // 3. Automatically create menu and role access for Laporan Retur Display (TIM RESTOCK, parentid = 77)
        $menu_laporan = $this->db->get_where('menu', ['uri' => 'restock/laporan-retur-display'])->row();
        if (!$menu_laporan) {
            $menu_data = [
                'name'      => 'Laporan Retur Display',
                'parentid'  => 77,
                'uri'       => 'restock/laporan-retur-display',
                'icon'      => 'fa fa-bar-chart',
                'sortorder' => 40,
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ];
            $this->db->insert('menu', $menu_data);
            $menu_laporan_id = $this->db->insert_id();
        } else {
            $menu_laporan_id = $menu_laporan->id;
        }

        // Grant access to Laporan Retur Display for roles 1, 2, 4, 6, 11 (Restock group)
        $laporan_roles = [1, 2, 4, 6, 11];
        foreach ($laporan_roles as $role_id) {
            $access_exist = $this->db->get_where('roleaccess', ['roleid' => $role_id, 'menuid' => $menu_laporan_id])->row();
            if (!$access_exist) {
                $this->db->insert('roleaccess', [
                    'roleid'    => $role_id,
                    'menuid'    => $menu_laporan_id,
                    'created'   => date('Y-m-d H:i:s'),
                    'createdby' => 1
                ]);
            }
        }
    }

    protected function run_daftar_sku_migrations()
    {
        // 1. Add jenis_packing column to tblsku if not exists
        $fields = $this->db->list_fields('tblsku');
        if (!in_array('jenis_packing', $fields)) {
            $this->db->query("ALTER TABLE tblsku ADD COLUMN jenis_packing VARCHAR(100) DEFAULT NULL AFTER is_special");
        }

        // 2. Automatically create menu and role access for Daftar SKU (TIM RESI, parentid = 83)
        $menu = $this->db->get_where('menu', ['uri' => 'resi_team/daftar_sku'])->row();
        if (!$menu) {
            $menu_data = [
                'name'      => 'Daftar SKU',
                'parentid'  => 83,
                'uri'       => 'resi_team/daftar_sku',
                'icon'      => 'fa fa-cube',
                'sortorder' => 15,
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ];
            $this->db->insert('menu', $menu_data);
            $menu_id = $this->db->insert_id();
        } else {
            $menu_id = $menu->id;
        }

        // Grant access to Daftar SKU for ALL roles having access to parent menu (ID 83 = TIM MONITORING)
        $roles = $this->db->get_where('roleaccess', ['menuid' => 83])->result();
        foreach ($roles as $r) {
            $access_exist = $this->db->get_where('roleaccess', ['roleid' => $r->roleid, 'menuid' => $menu_id])->row();
            if (!$access_exist) {
                $this->db->insert('roleaccess', [
                    'roleid'    => $r->roleid,
                    'menuid'    => $menu_id,
                    'created'   => date('Y-m-d H:i:s'),
                    'createdby' => 1
                ]);
            }
        }
    }

    protected function run_accounting_menu_migrations()
    {
        // 1. Find or insert TIM ACCOUNTING parent menu
        $parent = $this->db->get_where('menu', ['name' => 'TIM ACCOUNTING'])->row();
        if (!$parent) {
            $parent = $this->db->get_where('menu', ['id' => 92])->row();
        }

        if (!$parent) {
            $menu_data = [
                'id'        => 92,
                'name'      => 'TIM ACCOUNTING',
                'parentid'  => 1,
                'uri'       => null,
                'icon'      => 'fa fa-book',
                'sortorder' => 130,
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ];
            $this->db->insert('menu', $menu_data);
            $parent_id = 92;
        } else {
            $parent_id = $parent->id;
            // Ensure icon is fa fa-book
            if ($parent->icon !== 'fa fa-book') {
                $this->db->where('id', $parent_id)->update('menu', ['icon' => 'fa fa-book']);
            }
        }

        // 2. Rename or create "Surat Jalan" (formerly "Unggah Surat Jalan")
        $menu_sj = $this->db->where('parentid', $parent_id)
                            ->group_start()
                            ->where('uri', 'accounting/form-unggah-surat-jalan')
                            ->or_where('name', 'Unggah Surat Jalan')
                            ->group_end()
                            ->get('menu')->row();
        if ($menu_sj) {
            $this->db->where('id', $menu_sj->id)->update('menu', [
                'name' => 'Surat Jalan',
                'uri'  => 'accounting/form-unggah-surat-jalan'
            ]);
        } else {
            $this->db->insert('menu', [
                'name'      => 'Surat Jalan',
                'parentid'  => $parent_id,
                'uri'       => 'accounting/form-unggah-surat-jalan',
                'icon'      => 'fa fa-upload',
                'sortorder' => 10,
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ]);
        }

        // 3. Rename or create "Surat Jalan Database" (formerly "Riwayat Surat Jalan")
        $menu_sj_db = $this->db->where('parentid', $parent_id)
                               ->group_start()
                               ->where('uri', 'accounting/riwayat-surat-jalan')
                               ->or_where('name', 'Riwayat Surat Jalan')
                               ->group_end()
                               ->get('menu')->row();
        if ($menu_sj_db) {
            $this->db->where('id', $menu_sj_db->id)->update('menu', [
                'name' => 'Surat Jalan Database',
                'uri'  => 'accounting/riwayat-surat-jalan'
            ]);
        } else {
            $this->db->insert('menu', [
                'name'      => 'Surat Jalan Database',
                'parentid'  => $parent_id,
                'uri'       => 'accounting/riwayat-surat-jalan',
                'icon'      => 'fa fa-history',
                'sortorder' => 20,
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ]);
        }

        // 4. Create "Nomor Rak" (points to accounting/nomor-rak) under parent_id
        $menu_rak = $this->db->where('parentid', $parent_id)
                             ->group_start()
                             ->where('uri', 'accounting/nomor-rak')
                             ->or_where('uri', 'resi_team/daftar_sku')
                             ->or_where('name', 'Nomor Rak')
                             ->group_end()
                             ->get('menu')->row();
        if ($menu_rak) {
            $this->db->where('id', $menu_rak->id)->update('menu', [
                'name' => 'Nomor Rak',
                'uri'  => 'accounting/nomor-rak'
            ]);
        } else {
            $this->db->insert('menu', [
                'name'      => 'Nomor Rak',
                'parentid'  => $parent_id,
                'uri'       => 'accounting/nomor-rak',
                'icon'      => 'fa fa-cube',
                'sortorder' => 25,
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ]);
        }

        // 5. Create "Laporan Retur Shipped" (points to retur/laporan-retur-shipped) under parent_id
        $menu_shipped = $this->db->get_where('menu', ['uri' => 'retur/laporan-retur-shipped'])->row();
        if (!$menu_shipped) {
            $menu_data = [
                'name'      => 'Laporan Retur Shipped',
                'parentid'  => $parent_id,
                'uri'       => 'retur/laporan-retur-shipped',
                'icon'      => 'fa fa-file-text-o',
                'sortorder' => 90,
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ];
            $this->db->insert('menu', $menu_data);
            $menu_shipped_id = $this->db->insert_id();
        } else {
            $menu_shipped_id = $menu_shipped->id;
            $this->db->where('id', $menu_shipped_id)->update('menu', [
                'parentid'  => $parent_id,
                'sortorder' => 90
            ]);
        }

        // Copy role access from Verifikasi Retur (ID 137) to ensure all allowed roles get access
        $roles_shipped = $this->db->get_where('roleaccess', ['menuid' => 137])->result();
        foreach ($roles_shipped as $r) {
            $access_exist = $this->db->get_where('roleaccess', ['roleid' => $r->roleid, 'menuid' => $menu_shipped_id])->row();
            if (!$access_exist) {
                $this->db->insert('roleaccess', [
                    'roleid'    => $r->roleid,
                    'menuid'    => $menu_shipped_id,
                    'created'   => date('Y-m-d H:i:s'),
                    'createdby' => 1
                ]);
            }
        }

        // 6. Grant access for role 1 (webmaster), 2 (admin), and 8 (tim accounting)
        // Get all menu ids under TIM ACCOUNTING parent menu
        $menu_ids = [$parent_id];
        $children = $this->db->get_where('menu', ['parentid' => $parent_id])->result();
        foreach ($children as $child) {
            $menu_ids[] = $child->id;
        }

        $roles = [1, 2, 8];
        foreach ($menu_ids as $menu_id) {
            foreach ($roles as $role_id) {
                $exists = $this->db->get_where('roleaccess', [
                    'roleid' => $role_id,
                    'menuid' => $menu_id
                ])->row();
                if (!$exists) {
                    $this->db->insert('roleaccess', [
                        'roleid'    => $role_id,
                        'menuid'    => $menu_id,
                        'created'   => date('Y-m-d H:i:s'),
                        'createdby' => 1
                    ]);
                }
            }
        }

        // Create tblsurat_jalan_items if not exists
        $this->db->query("CREATE TABLE IF NOT EXISTS `tblsurat_jalan_items` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `tgl` DATE DEFAULT NULL,
          `jenis_sj` VARCHAR(100) DEFAULT NULL,
          `no_trf_jubelio` VARCHAR(100) DEFAULT NULL,
          `sku` VARCHAR(100) DEFAULT NULL,
          `qty_restock_rqst` INT DEFAULT 0,
          `qty_restock_real` INT DEFAULT 0,
          `qty_restock_over` INT DEFAULT 0,
          `qty_jubelio_disp` INT DEFAULT 0,
          `qty_jubelio_gd` INT DEFAULT 0,
          `sj_jubelio_sku` VARCHAR(100) DEFAULT NULL,
          `sj_jubelio_qty` INT DEFAULT 0,
          `real_vs_jb_sku` VARCHAR(100) DEFAULT NULL,
          `real_vs_jb_qty` INT DEFAULT 0,
          `selisih` INT DEFAULT 0,
          `action_in_jubelio` VARCHAR(255) DEFAULT NULL,
          `created_at` DATETIME DEFAULT NULL,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Add no_rak_gudang to tblsku if not exists
        if (!$this->db->field_exists('no_rak_gudang', 'tblsku')) {
            $this->db->query("ALTER TABLE `tblsku` ADD COLUMN `no_rak_gudang` VARCHAR(100) DEFAULT NULL AFTER `no_rak`");
        }
    }

    protected function run_complain_migrations()
    {
        // 1. Create tables
        $this->db->query("CREATE TABLE IF NOT EXISTS `tblcs_complain` (
          `id_complain` int(11) NOT NULL AUTO_INCREMENT,
          `no_resi` varchar(100) NOT NULL,
          `no_pesanan` varchar(100) DEFAULT NULL,
          `tgl_komplain` date DEFAULT NULL,
          `tgl_pesanan` datetime DEFAULT NULL,
          `id_marketplace` int(11) NOT NULL,
          `toko` varchar(100) NOT NULL,
          `kategori_komplain` enum('Kurang Kirim','Reject','Paket Kosong','Salah Kirim','Tidak Sesuai Deskripsi','Barang Rusak','Paket Hilang','Telat Kirim','Salah Alamat','Barang Tidak Original','Lainnya') NOT NULL,
          `detail_lainnya` text DEFAULT NULL,
          `sumber_komplain` enum('Chat Marketplace','WhatsApp','Telepon','Review/Rating','Email','Retur Fisik','Lainnya') DEFAULT NULL,
          `status_penanganan` enum('Baru','Proses','Selesai','Ditolak') DEFAULT NULL,
          `hasil_investigasi` enum('QC Salah','QC Benar','Tidak Terlihat CCTV','CCTV Tidak Bisa Diakses') DEFAULT NULL,
          `id_masalahpacker` int(11) DEFAULT NULL,
          `catatan_penanganan` text DEFAULT NULL,
          `id_resiretur` int(10) unsigned DEFAULT NULL,
          `nama_qc` varchar(100) DEFAULT NULL,
          `nama_packer` varchar(100) DEFAULT NULL,
          `nama_picker` varchar(100) DEFAULT NULL,
          `nominal_total` decimal(15,2) NOT NULL DEFAULT 0.00,
          `nilai_pesanan` decimal(15,2) DEFAULT NULL,
          `tgl_banding_pengajuan` datetime DEFAULT NULL,
          `tgl_banding_tinjauan` datetime DEFAULT NULL,
          `tgl_claim_dana` date DEFAULT NULL,
          `keterangan_banding` text DEFAULT NULL,
          `nominal_claim_dana` decimal(15,2) DEFAULT 0.00,
          `created_at` datetime NOT NULL,
          `created_by` int(11) NOT NULL,
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          `updated_by` int(11) DEFAULT NULL,
          `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
          PRIMARY KEY (`id_complain`),
          KEY `idx_cs_complain_resi` (`no_resi`),
          KEY `idx_cs_complain_marketplace` (`id_marketplace`),
          KEY `idx_cs_complain_updated` (`updated_at`),
          KEY `idx_cs_complain_deleted` (`is_deleted`),
          KEY `idx_cs_complain_tgl` (`tgl_komplain`),
          KEY `idx_cs_complain_sumber` (`sumber_komplain`),
          KEY `idx_cs_complain_status` (`status_penanganan`),
          KEY `idx_cs_complain_resiretur` (`id_resiretur`),
          KEY `idx_cs_complain_hasil` (`hasil_investigasi`),
          KEY `idx_cs_complain_masalahpacker` (`id_masalahpacker`),
          KEY `idx_cs_complain_pesanan` (`no_pesanan`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `tblcs_complain_detail` (
          `id_complain_detail` int(11) NOT NULL AUTO_INCREMENT,
          `id_complain` int(11) NOT NULL,
          `no_resi` varchar(100) NOT NULL,
          `sku` varchar(100) NOT NULL,
          `qty` int(11) NOT NULL DEFAULT 1,
          `price` decimal(15,2) NOT NULL DEFAULT 0.00,
          `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
          PRIMARY KEY (`id_complain_detail`),
          KEY `idx_cs_complain_detail_master` (`id_complain`),
          KEY `idx_cs_complain_detail_resi` (`no_resi`),
          KEY `idx_cs_complain_detail_deleted` (`is_deleted`),
          CONSTRAINT `fk_cs_complain_detail_master` FOREIGN KEY (`id_complain`) REFERENCES `tblcs_complain` (`id_complain`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 1b. Upgrade skema lama (PK no_resi, tanpa kolom cakupan baru) ke skema baru.
        //     Satu query pengecekan; ALTER hanya jalan sekali seumur hidup database.
        //     Harus dijalankan sebelum tabel lampiran dibuat karena FK-nya menunjuk id_complain.
        $this->upgrade_complain_schema();

        $this->db->query("CREATE TABLE IF NOT EXISTS `tblcs_complain_lampiran` (
          `id_lampiran` int(11) NOT NULL AUTO_INCREMENT,
          `id_complain` int(11) NOT NULL,
          `nama_file` varchar(255) NOT NULL,
          `nama_asli` varchar(255) DEFAULT NULL,
          `mime_type` varchar(100) DEFAULT NULL,
          `ukuran` int(11) NOT NULL DEFAULT 0,
          `uploaded_by` int(11) NOT NULL,
          `uploaded_at` datetime NOT NULL,
          `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
          PRIMARY KEY (`id_lampiran`),
          KEY `idx_cs_lampiran_complain` (`id_complain`),
          KEY `idx_cs_lampiran_deleted` (`is_deleted`),
          CONSTRAINT `fk_cs_lampiran_complain` FOREIGN KEY (`id_complain`) REFERENCES `tblcs_complain` (`id_complain`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");


        // 2. Automatically create menu and role access for Manajemen Komplain (TIM CS, parentid = 51)
        $menu_complain = $this->db->get_where('menu', ['uri' => 'cs/complain-management'])->row();
        if (!$menu_complain) {
            $menu_data = [
                'name'      => 'Manajemen Komplain',
                'parentid'  => 51,
                'uri'       => 'cs/complain-management',
                'icon'      => 'fa fa-exclamation-triangle',
                'sortorder' => 60,
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ];
            $this->db->insert('menu', $menu_data);
            $menu_complain_id = $this->db->insert_id();
        } else {
            $menu_complain_id = $menu_complain->id;
            // Ensure correct sort order & icon & parent
            $this->db->where('id', $menu_complain_id)->update('menu', [
                'parentid'  => 51,
                'sortorder' => 60,
                'icon'      => 'fa fa-exclamation-triangle'
            ]);
        }

        // Grant access to Manajemen Komplain for roles 1 (webmaster), 2 (admin), 6 (tim retur), 11 (client orders)
        $complain_roles = [1, 2, 6, 11];
        foreach ($complain_roles as $role_id) {
            $access_exist = $this->db->get_where('roleaccess', ['roleid' => $role_id, 'menuid' => $menu_complain_id])->row();
            if (!$access_exist) {
                $this->db->insert('roleaccess', [
                    'roleid'    => $role_id,
                    'menuid'    => $menu_complain_id,
                    'created'   => date('Y-m-d H:i:s'),
                    'createdby' => 1
                ]);
            }
        }
    }

    /**
     * Naikkan tblcs_complain dari skema lama (PK no_resi, 1 komplain per resi)
     * ke skema baru (PK id_complain, multi komplain per resi + kolom cakupan baru).
     * Aman dipanggil berulang: keluar lebih awal begitu kolom id_complain sudah ada.
     */
    protected function upgrade_complain_schema()
    {
        // Satu query untuk dua penanda sekaligus: kolom PK baru dan kolom hasil
        // investigasi. Keduanya dijaga terpisah supaya database yang sudah naik
        // ke tahap pertama tetap kebagian tahap kedua.
        $kolom = $this->db->query(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblcs_complain'
               AND COLUMN_NAME IN ('id_complain', 'hasil_investigasi', 'no_pesanan')"
        )->result_array();

        $ada = array_column($kolom, 'COLUMN_NAME');
        $perlu_pk = !in_array('id_complain', $ada);
        $perlu_hasil = !in_array('hasil_investigasi', $ada);
        $perlu_autofill = !in_array('no_pesanan', $ada);

        if (!$perlu_pk && !$perlu_hasil && !$perlu_autofill) {
            return;
        }

        if ($perlu_hasil) {
            $this->upgrade_complain_hasil_investigasi();
        }

        if ($perlu_autofill) {
            $this->upgrade_complain_autofill();
        }

        if (!$perlu_pk) {
            return;
        }

        $stamp = date('Ymd');

        // Cadangan sebelum struktur diubah
        $this->db->query("CREATE TABLE IF NOT EXISTS `tblcs_complain_backup_{$stamp}` AS SELECT * FROM `tblcs_complain`");
        $this->db->query("CREATE TABLE IF NOT EXISTS `tblcs_complain_detail_backup_{$stamp}` AS SELECT * FROM `tblcs_complain_detail`");

        // Lepas FK lama kalau masih ada
        $fk_lama = $this->db->query(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'tblcs_complain_detail'
               AND CONSTRAINT_NAME = 'fk_cs_complain_detail_resi'"
        )->row();
        if ($fk_lama) {
            $this->db->query("ALTER TABLE `tblcs_complain_detail` DROP FOREIGN KEY `fk_cs_complain_detail_resi`");
        }

        // PK no_resi -> id_complain
        $this->db->query("ALTER TABLE `tblcs_complain`
            DROP PRIMARY KEY,
            ADD COLUMN `id_complain` INT(11) NOT NULL AUTO_INCREMENT FIRST,
            ADD PRIMARY KEY (`id_complain`),
            ADD KEY `idx_cs_complain_resi` (`no_resi`)");

        // Kolom cakupan baru + perluasan kategori
        $this->db->query("ALTER TABLE `tblcs_complain`
            MODIFY COLUMN `kategori_komplain` ENUM('Kurang Kirim','Reject','Paket Kosong','Salah Kirim','Tidak Sesuai Deskripsi','Barang Rusak','Paket Hilang','Telat Kirim','Salah Alamat','Barang Tidak Original','Lainnya') NOT NULL,
            ADD COLUMN `tgl_komplain` DATE DEFAULT NULL AFTER `no_resi`,
            ADD COLUMN `sumber_komplain` ENUM('Chat Marketplace','WhatsApp','Telepon','Review/Rating','Email','Retur Fisik','Lainnya') DEFAULT NULL AFTER `detail_lainnya`,
            ADD COLUMN `status_penanganan` ENUM('Baru','Proses','Selesai','Ditolak') DEFAULT NULL AFTER `sumber_komplain`,
            ADD COLUMN `catatan_penanganan` TEXT DEFAULT NULL AFTER `status_penanganan`,
            ADD COLUMN `id_resiretur` INT(10) UNSIGNED DEFAULT NULL AFTER `catatan_penanganan`,
            ADD KEY `idx_cs_complain_tgl` (`tgl_komplain`),
            ADD KEY `idx_cs_complain_sumber` (`sumber_komplain`),
            ADD KEY `idx_cs_complain_status` (`status_penanganan`),
            ADD KEY `idx_cs_complain_resiretur` (`id_resiretur`)");

        // Detail: pindah relasi no_resi -> id_complain (data lama 1 resi = 1 komplain)
        $this->db->query("ALTER TABLE `tblcs_complain_detail`
            ADD COLUMN `id_complain` INT(11) DEFAULT NULL AFTER `id_complain_detail`,
            ADD KEY `idx_cs_complain_detail_master` (`id_complain`)");

        $this->db->query("UPDATE `tblcs_complain_detail` d
            JOIN `tblcs_complain` c ON c.`no_resi` = d.`no_resi`
            SET d.`id_complain` = c.`id_complain`
            WHERE d.`id_complain` IS NULL");

        $this->db->query("CREATE TABLE IF NOT EXISTS `tblcs_complain_detail_orphan_{$stamp}` AS
            SELECT * FROM `tblcs_complain_detail` WHERE `id_complain` IS NULL");
        $this->db->query("DELETE FROM `tblcs_complain_detail` WHERE `id_complain` IS NULL");

        $this->db->query("ALTER TABLE `tblcs_complain_detail`
            MODIFY COLUMN `id_complain` INT(11) NOT NULL,
            ADD CONSTRAINT `fk_cs_complain_detail_master`
              FOREIGN KEY (`id_complain`) REFERENCES `tblcs_complain` (`id_complain`)
              ON DELETE CASCADE ON UPDATE CASCADE");

        log_message('info', 'tblcs_complain berhasil dinaikkan ke skema multi-komplain.');
    }

    /**
     * Tambahkan kolom hasil investigasi CCTV + kaitan ke poin KPI packer.
     * Dipisah dari upgrade PK supaya database yang sudah lewat tahap pertama
     * tetap ikut naik.
     */
    protected function upgrade_complain_hasil_investigasi()
    {
        $this->db->query("ALTER TABLE `tblcs_complain`
            ADD COLUMN `hasil_investigasi` ENUM('QC Salah','QC Benar','Tidak Terlihat CCTV','CCTV Tidak Bisa Diakses') DEFAULT NULL AFTER `status_penanganan`,
            ADD COLUMN `id_masalahpacker` INT(11) DEFAULT NULL AFTER `hasil_investigasi`,
            ADD KEY `idx_cs_complain_hasil` (`hasil_investigasi`),
            ADD KEY `idx_cs_complain_masalahpacker` (`id_masalahpacker`)");

        $type_ada = $this->db->get_where('tbltypemasalahpacker', ['type_masalah' => 'QC Salah (Komplain CS)'])->row();
        if (!$type_ada) {
            $this->db->insert('tbltypemasalahpacker', ['type_masalah' => 'QC Salah (Komplain CS)']);
        }

        log_message('info', 'tblcs_complain: kolom hasil_investigasi ditambahkan.');
    }

    /**
     * Kolom hasil auto-fill dari nomor resi: nomor & tanggal pesanan, picker,
     * dan nilai pesanan.
     */
    protected function upgrade_complain_autofill()
    {
        $this->db->query("ALTER TABLE `tblcs_complain`
            ADD COLUMN `no_pesanan` VARCHAR(100) DEFAULT NULL AFTER `no_resi`,
            ADD COLUMN `tgl_pesanan` DATETIME DEFAULT NULL AFTER `tgl_komplain`,
            ADD COLUMN `nama_picker` VARCHAR(100) DEFAULT NULL AFTER `nama_packer`,
            ADD COLUMN `nilai_pesanan` DECIMAL(15,2) DEFAULT NULL AFTER `nominal_total`,
            ADD KEY `idx_cs_complain_pesanan` (`no_pesanan`)");

        log_message('info', 'tblcs_complain: kolom auto-fill pesanan ditambahkan.');
    }

    /**
     * Menu Cancel Order (TIM RESI): tabel penampung resi yang batal, baik yang
     * ketahuan dari sinkron Jubelio (status_pesanan CANCELED/REQUEST_CANCEL di
     * tblprintresi) maupun yang diinput manual lewat scan.
     *
     * CATATAN CHARSET: `noresi` & `no_pesanan` sengaja dipaksa latin1 mengikuti
     * tblprintresi/tbldetailprintresi supaya JOIN tetap bisa pakai index dan
     * tidak kena masalah campur utf8/latin1 seperti tblreturjubelio.
     */
    protected function run_cancel_order_migrations()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `tblcancelorder` (
          `id_cancel` int(11) NOT NULL AUTO_INCREMENT,
          `noresi` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
          `no_pesanan` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
          `id_printresi` bigint(20) unsigned DEFAULT NULL,
          `id_marketplace` int(11) DEFAULT NULL,
          `nama_marketplace` varchar(100) DEFAULT NULL,
          `toko` varchar(100) DEFAULT NULL,
          `status_marketplace` varchar(50) DEFAULT NULL,
          `tanggal_pesan` datetime DEFAULT NULL,
          `tanggal_cancel` datetime DEFAULT NULL,
          `sumber` enum('JUBELIO','SCAN') NOT NULL DEFAULT 'JUBELIO',
          `terkonfirmasi_jubelio` tinyint(1) NOT NULL DEFAULT 0,
          `ada_di_iresis` tinyint(1) NOT NULL DEFAULT 1,
          `catatan` varchar(255) DEFAULT NULL,
          `created_by` int(11) DEFAULT NULL,
          `created_at` datetime DEFAULT NULL,
          `updated_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id_cancel`),
          UNIQUE KEY `uq_cancelorder_noresi` (`noresi`),
          KEY `idx_cancelorder_tglcancel` (`tanggal_cancel`),
          KEY `idx_cancelorder_tglpesan` (`tanggal_pesan`),
          KEY `idx_cancelorder_sumber` (`sumber`),
          KEY `idx_cancelorder_status` (`status_marketplace`),
          KEY `idx_cancelorder_mp` (`id_marketplace`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Menu induk "Cancel Order" di bawah TIM RESI (parentid = 6)
        $menu_induk = $this->db->get_where('menu', ['uri' => null, 'name' => 'Cancel Order', 'parentid' => 6])->row();
        if (!$menu_induk) {
            $this->db->insert('menu', [
                'name'      => 'Cancel Order',
                'parentid'  => 6,
                'uri'       => null,
                'icon'      => 'fa fa-ban',
                'sortorder' => 75,
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ]);
            $id_induk = $this->db->insert_id();
        } else {
            $id_induk = $menu_induk->id;
        }

        $submenu = [
            [
                'name'      => 'Daftar Cancel Order',
                'uri'       => 'resi_team/cancel_order',
                'icon'      => 'fa fa-list-alt',
                'sortorder' => 10,
            ],
            [
                'name'      => 'Scan Cek Cancel',
                'uri'       => 'resi_team/scan_cancel_order',
                'icon'      => 'fa fa-barcode',
                'sortorder' => 20,
            ],
        ];

        // Hak akses: samakan dengan menu TIM RESI lain (Scan Resi / Cek Paket RTS)
        $cancel_roles = [1, 2, 6];

        foreach ($submenu as $m) {
            $row = $this->db->get_where('menu', ['uri' => $m['uri']])->row();
            if (!$row) {
                $this->db->insert('menu', [
                    'name'      => $m['name'],
                    'parentid'  => $id_induk,
                    'uri'       => $m['uri'],
                    'icon'      => $m['icon'],
                    'sortorder' => $m['sortorder'],
                    'isactive'  => 1,
                    'createdby' => 1,
                    'created'   => date('Y-m-d H:i:s')
                ]);
                $menu_id = $this->db->insert_id();
            } else {
                $menu_id = $row->id;
                // Pastikan tetap nempel di induk yang benar kalau menu pernah dipindah
                $this->db->where('id', $menu_id)->update('menu', [
                    'parentid'  => $id_induk,
                    'icon'      => $m['icon'],
                    'sortorder' => $m['sortorder'],
                ]);
            }

            foreach ($cancel_roles as $role_id) {
                $access_exist = $this->db->get_where('roleaccess', ['roleid' => $role_id, 'menuid' => $menu_id])->row();
                if (!$access_exist) {
                    $this->db->insert('roleaccess', [
                        'roleid'    => $role_id,
                        'menuid'    => $menu_id,
                        'created'   => date('Y-m-d H:i:s'),
                        'createdby' => 1
                    ]);
                }
            }
        }

        // Menu induk juga butuh roleaccess supaya ikut kebawa di menu tree
        foreach ($cancel_roles as $role_id) {
            $access_exist = $this->db->get_where('roleaccess', ['roleid' => $role_id, 'menuid' => $id_induk])->row();
            if (!$access_exist) {
                $this->db->insert('roleaccess', [
                    'roleid'    => $role_id,
                    'menuid'    => $id_induk,
                    'created'   => date('Y-m-d H:i:s'),
                    'createdby' => 1
                ]);
            }
        }
    }

    /**
     * Menu "Tracking Picker" di bawah Laporan Operasional (parentid = 141),
     * sejajar dengan Totalan Picker/Packer dkk.
     */
    protected function run_tracking_picker_migration()
    {
        $menu = $this->db->get_where('menu', ['uri' => 'laporan/tracking-picker'])->row();
        if (!$menu) {
            $this->db->insert('menu', [
                'name'      => 'Tracking Picker',
                'parentid'  => 15,
                'uri'       => 'laporan/tracking-picker',
                'icon'      => 'fa fa-map-marker',
                'sortorder' => 45,
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ]);
            $menu_id = $this->db->insert_id();
        } else {
            $menu_id = $menu->id;
            // Pindahkan entri lama (kalau sempat kebuat di bawah parentid=141 yang cacat)
            if ($menu->parentid != 15) {
                $this->db->where('id', $menu_id)->update('menu', ['parentid' => 15]);
            }
        }

        // Samakan hak akses dengan menu Laporan lain (mis. Dashboard Operasional Harian, id 135)
        $roles = $this->db->get_where('roleaccess', ['menuid' => 135])->result();
        foreach ($roles as $r) {
            $access_exist = $this->db->get_where('roleaccess', ['roleid' => $r->roleid, 'menuid' => $menu_id])->row();
            if (!$access_exist) {
                $this->db->insert('roleaccess', [
                    'roleid'    => $r->roleid,
                    'menuid'    => $menu_id,
                    'created'   => date('Y-m-d H:i:s'),
                    'createdby' => 1
                ]);
            }
        }
    }

    /**
     * Menu "Scan Resi Packer (Webcam)" di grup TIM PACKER.
     *
     * Untuk sekarang halamannya salinan utuh Scan Resi Packer; pembedanya baru
     * menyusul, yaitu rekam video saat packing. Versi biasa tetap dipertahankan
     * sebagai cadangan kalau kamera bermasalah, jadi kedua menu ini berdiri
     * sendiri-sendiri -- controller dan view-nya pun sengaja tidak dipakai
     * bersama.
     *
     * Hak aksesnya: webmaster (roleid 1) sejak uji coba, lalu client packer
     * (roleid 4) sejak 2026-09-16. Jangan disamakan dengan hak akses menu Scan
     * Resi Packer (menuid 25) yang dipegang hampir semua role.
     *
     * Kalau dibuka untuk role lain lagi, tambahkan roleid-nya di $role_boleh
     * DAN di Packer::ROLE_BOLEH_WEBCAM (penjaga di controller), lalu naikkan
     * BOOTSTRAP_VERSI -- jangan menghapus baris roleaccess yang ada.
     */
    protected function run_menu_scan_packer_webcam()
    {
        $uri = 'packer/scan_packer_webcam';

        // Urutan dikunci ke id terkecil: bootstrap ini pernah jalan dua kali
        // serentak (dua request masuk sebelum penanda versi sempat ditulis) dan
        // menghasilkan dua baris menu dengan uri sama. Tanpa order_by, row() bisa
        // memungut baris duplikat yang sudah dinonaktifkan lalu memberinya hak
        // akses lagi.
        $menu = $this->db->order_by('id', 'ASC')->limit(1)->get_where('menu', ['uri' => $uri])->row();
        if (!$menu) {
            // Induknya diambil dari menu Scan Resi Packer supaya ikut pindah kalau
            // grup TIM PACKER pernah ditata ulang; 24 hanya cadangan.
            $menu_asal = $this->db->get_where('menu', ['uri' => 'packer/scan_packer'])->row();
            $parent_id = $menu_asal ? $menu_asal->parentid : 24;

            $this->db->insert('menu', [
                'name'      => 'Scan Resi Packer (Webcam)',
                'parentid'  => $parent_id,
                'uri'       => $uri,
                'icon'      => 'fa fa-video-camera',
                'sortorder' => 11,
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ]);
            $menu_id = $this->db->insert_id();
        } else {
            $menu_id = $menu->id;
        }

        $role_boleh = [1, 4];
        foreach ($role_boleh as $roleid) {
            $akses_ada = $this->db->get_where('roleaccess', ['roleid' => $roleid, 'menuid' => $menu_id])->row();
            if (!$akses_ada) {
                $this->db->insert('roleaccess', [
                    'roleid'    => $roleid,
                    'menuid'    => $menu_id,
                    'created'   => date('Y-m-d H:i:s'),
                    'createdby' => 1
                ]);
            }
        }
    }

    /**
     * Tabel jejak tombol Batal Scan di Scan Resi Packer (Webcam).
     *
     * Batal Scan membuka kembali siklus scan pertama yang menggantung sekaligus
     * membuang rekamannya. Itu memang perlu -- barang kurang atau salah bisa
     * lama beresnya -- tapi tanpa catatan, urutan "scan, packing sampai selesai,
     * lalu Batal Scan" menghasilkan resi yang tersimpan normal dengan video
     * beberapa detik saja, dan tidak ada yang bisa melihatnya terjadi.
     */
    protected function run_batal_scan_packer_migration()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `tblbatalscanpacker` (
          `id_batalscan` int(11) NOT NULL AUTO_INCREMENT,
          `noresi` varchar(100) NOT NULL,
          `id_user` int(11) DEFAULT NULL,
          `nama_komputer` varchar(50) DEFAULT NULL,
          `durasi_detik` int(11) NOT NULL DEFAULT 0,
          `tanggal_batal` datetime NOT NULL,
          PRIMARY KEY (`id_batalscan`),
          KEY `idx_noresi` (`noresi`),
          KEY `idx_tanggal_batal` (`tanggal_batal`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    /**
     * Tabel metadata rekaman video packing + menu "Video Packing" untuk CS.
     *
     * Berkas videonya sendiri ada di C:/video-packing/ -- di luar document
     * root, bisa diubah lewat kunci video_packing_dir di secrets.php (lihat
     * Video_packing_fcd); tabel ini hanya menyimpan siapa, kapan, berapa lama,
     * dan status rekamannya. Satu baris = satu sesi rekam (satu resi, satu
     * kali packing; bagian 2+ lahir kalau tab packer mati di tengah).
     */
    protected function run_video_packing_migration()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `tblvideopacking` (
          `id_videopacking` int(11) NOT NULL AUTO_INCREMENT,
          `noresi` varchar(100) NOT NULL,
          `kode_sesi` varchar(40) NOT NULL,
          `nama_file` varchar(255) NOT NULL,
          `folder` varchar(20) NOT NULL,
          `bagian` int(11) NOT NULL DEFAULT 1,
          `mime_type` varchar(60) NOT NULL DEFAULT 'video/webm',
          `durasi_detik` int(11) NOT NULL DEFAULT 0,
          `ukuran_byte` bigint(20) NOT NULL DEFAULT 0,
          `status` enum('MEREKAM','SELESAI','TERPUTUS','DIBATALKAN') NOT NULL DEFAULT 'MEREKAM',
          `id_user` int(11) DEFAULT NULL,
          `nama_komputer` varchar(50) DEFAULT NULL,
          `mulai_at` datetime NOT NULL,
          `selesai_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id_videopacking`),
          UNIQUE KEY `idx_kode_sesi` (`kode_sesi`),
          KEY `idx_noresi` (`noresi`),
          KEY `idx_mulai_at` (`mulai_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Menu "Video Packing" di grup TIM CS. Induknya diambil dari menu
        // Manajemen Komplain supaya ikut pindah kalau grup CS pernah ditata
        // ulang; 51 hanya cadangan. order_by id: bootstrap pernah jalan dua kali
        // serentak dan membuat baris menu dobel, jadi baris terkecil yang dipegang.
        $uri = 'cs/video-packing';
        $menu = $this->db->order_by('id', 'ASC')->limit(1)->get_where('menu', ['uri' => $uri])->row();
        if (!$menu) {
            $menu_cs   = $this->db->get_where('menu', ['uri' => 'cs/complain-management'])->row();
            $parent_id = $menu_cs ? $menu_cs->parentid : 51;

            $this->db->insert('menu', [
                'name'      => 'Video Packing',
                'parentid'  => $parent_id,
                'uri'       => $uri,
                'icon'      => 'fa fa-video-camera',
                'sortorder' => 70,
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ]);
            $menu_id = $this->db->insert_id();
        } else {
            $menu_id = $menu->id;
        }

        // Hak akses SENGAJA cuma webmaster (roleid 1) selama fitur webcam masih
        // uji coba -- sama seperti menu Scan Resi Packer (Webcam). Kalau nanti
        // dibuka untuk tim CS, tambahkan roleid-nya di sini dan naikkan
        // BOOTSTRAP_VERSI; jangan menghapus baris roleaccess yang ada.
        $akses_ada = $this->db->get_where('roleaccess', ['roleid' => 1, 'menuid' => $menu_id])->row();
        if (!$akses_ada) {
            $this->db->insert('roleaccess', [
                'roleid'    => 1,
                'menuid'    => $menu_id,
                'created'   => date('Y-m-d H:i:s'),
                'createdby' => 1
            ]);
        }

        // Kolom finalisasi (remux WebM oleh ffmpeg agar punya durasi/cues) dan
        // konversi MP4 atas permintaan CS. Keduanya dikerjakan Cron::finalisasi_video,
        // bukan di request upload, jadi statusnya harus tersimpan di tabel.
        // Baris lama otomatis BELUM -> ikut di-remux bertahap oleh cron.
        if (!$this->db->field_exists('finalisasi', 'tblvideopacking')) {
            $this->db->query("ALTER TABLE `tblvideopacking`
                ADD COLUMN `finalisasi` ENUM('BELUM','PROSES','SELESAI','GAGAL') NOT NULL DEFAULT 'BELUM' AFTER `selesai_at`,
                ADD COLUMN `finalisasi_percobaan` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0 AFTER `finalisasi`,
                ADD COLUMN `finalisasi_at` DATETIME DEFAULT NULL AFTER `finalisasi_percobaan`,
                ADD COLUMN `finalisasi_pesan` VARCHAR(255) DEFAULT NULL AFTER `finalisasi_at`,
                ADD COLUMN `mp4_status` ENUM('TIDAK','ANTRI','PROSES','SIAP','GAGAL') NOT NULL DEFAULT 'TIDAK' AFTER `finalisasi_pesan`,
                ADD COLUMN `mp4_nama_file` VARCHAR(255) DEFAULT NULL AFTER `mp4_status`,
                ADD COLUMN `mp4_ukuran_byte` BIGINT(20) NOT NULL DEFAULT 0 AFTER `mp4_nama_file`,
                ADD COLUMN `mp4_percobaan` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0 AFTER `mp4_ukuran_byte`,
                ADD COLUMN `mp4_diminta_at` DATETIME DEFAULT NULL AFTER `mp4_percobaan`,
                ADD COLUMN `mp4_diminta_oleh` INT(11) DEFAULT NULL AFTER `mp4_diminta_at`,
                ADD COLUMN `mp4_selesai_at` DATETIME DEFAULT NULL AFTER `mp4_diminta_oleh`,
                ADD COLUMN `mp4_pesan` VARCHAR(255) DEFAULT NULL AFTER `mp4_selesai_at`,
                ADD KEY `idx_finalisasi` (`finalisasi`),
                ADD KEY `idx_mp4_status` (`mp4_status`)");
        }
    }

    /**
     * Controller Ngrok_control dihapus 17 Sep 2026 (ngrok tidak dipakai lagi,
     * akses LAN sudah lewat HTTPS Apache). Baris menunya disembunyikan, bukan
     * dihapus, dan roleaccess dibiarkan -- konsisten dengan migrasi lain.
     */
    protected function run_nonaktifkan_menu_ngrok()
    {
        $this->db->where('uri', 'ngrok_control')
                 ->where('isactive', 1)
                 ->update('menu', ['isactive' => 0]);
    }

    /**
     * Menu yang uri-nya tidak punya controller maupun route (klik = 404):
     * id 135 "Dashboard Operasional Harian" (dashboard-harian) dan
     * id 136 "Master Jadwal Kerja" (jadwal-kerja). Disembunyikan 17 Sep 2026;
     * roleaccess dibiarkan supaya tinggal diaktifkan lagi kalau fiturnya dibuat.
     */
    protected function run_nonaktifkan_menu_tanpa_route()
    {
        $this->db->where_in('uri', ['dashboard-harian', 'jadwal-kerja'])
                 ->where('isactive', 1)
                 ->update('menu', ['isactive' => 0]);
    }

    /**
     * Menu TIM CS -> "Daftar Masalah Picker New" + tabel riwayat prosesnya.
     *
     * Versi baru berdiri sendiri (controller Masalah_picker_new, model, view,
     * route) supaya menu lama "Daftar Masalah Picker" (cs/masalah-picker)
     * tetap utuh sebagai cadangan. Sumber datanya tetap tblmasalahpicker.
     *
     * tblmasalahpicker_proses mencatat tiap klik "Proses & Cetak" (siapa,
     * kapan, berapa picker/item); _item menyimpan snapshot tiap baris yang
     * ikut diproses -- termasuk picker yang terdeteksi, packer, dan no rak --
     * supaya slip bisa dicetak ulang persis meski data induknya berubah atau
     * dihapus lewat Laporan Masalah Picker (Restock).
     *
     * Hak akses: HANYA Tim CS, yaitu webmaster (1), admin (2), dan tim retur
     * (6) -- tidak ada role "CS" tersendiri di tblhakakses. Daftarnya dikunci
     * juga di Masalah_picker_new::ROLE_BOLEH dan keduanya harus sejalan.
     * Versi pertama migrasi ini (2026-09-17.5) sempat menyalin pemegang menu
     * lama sehingga client packer (4) dan client orders (11) ikut dapat baris
     * roleaccess; baris itu tidak dihapus di sini (aturan proyek: tanpa
     * DELETE) melainkan dicabut lewat menu Access, dan controller-nya sendiri
     * menolak role di luar daftar.
     */
    protected function run_masalah_picker_new_migration()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `tblmasalahpicker_proses` (
          `id_proses` int(11) NOT NULL AUTO_INCREMENT,
          `waktu_proses` datetime NOT NULL,
          `id_user` int(11) NOT NULL,
          `nama_user` varchar(100) DEFAULT NULL,
          `jumlah_picker` int(11) NOT NULL DEFAULT 0,
          `jumlah_item` int(11) NOT NULL DEFAULT 0,
          PRIMARY KEY (`id_proses`),
          KEY `idx_waktu_proses` (`waktu_proses`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `tblmasalahpicker_proses_item` (
          `id_proses_item` int(11) NOT NULL AUTO_INCREMENT,
          `id_proses` int(11) NOT NULL,
          `id_masalahpicker` int(11) NOT NULL,
          `kode_picker` int(11) DEFAULT NULL,
          `nama_picker` varchar(255) DEFAULT NULL,
          `nama_packer` varchar(255) DEFAULT NULL,
          `noresi` varchar(100) DEFAULT NULL,
          `sku` varchar(100) DEFAULT NULL,
          `nama_barang` varchar(255) DEFAULT NULL,
          `sku_salah` varchar(100) DEFAULT NULL,
          `qty_bermasalah` int(11) NOT NULL DEFAULT 0,
          `no_rak` varchar(100) DEFAULT NULL,
          `id_typemasalah` int(11) DEFAULT NULL,
          `type_masalah` varchar(100) DEFAULT NULL,
          `dicetak` tinyint(1) NOT NULL DEFAULT 1,
          PRIMARY KEY (`id_proses_item`),
          KEY `idx_id_proses` (`id_proses`),
          KEY `idx_id_masalahpicker` (`id_masalahpicker`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $uri_baru = 'masalah-picker-new';
        $uri_lama = 'cs/masalah-picker';

        $menu_lama = $this->db->order_by('id', 'ASC')->limit(1)->get_where('menu', ['uri' => $uri_lama])->row();

        // Urutan dikunci ke id terkecil -- lihat catatan di run_menu_scan_packer_webcam.
        $menu = $this->db->order_by('id', 'ASC')->limit(1)->get_where('menu', ['uri' => $uri_baru])->row();
        if (!$menu) {
            $this->db->insert('menu', [
                'name'      => 'Daftar Masalah Picker New',
                'parentid'  => $menu_lama ? $menu_lama->parentid : 51,
                'uri'       => $uri_baru,
                'icon'      => 'fa fa-print',
                'sortorder' => $menu_lama ? ((int) $menu_lama->sortorder + 1) : 1,
                'isactive'  => 1,
                'createdby' => 1,
                'created'   => date('Y-m-d H:i:s')
            ]);
            $menu_id = $this->db->insert_id();
        } else {
            $menu_id = $menu->id;
        }

        $role_boleh = [1, 2, 6];
        foreach ($role_boleh as $roleid) {
            $akses_ada = $this->db->get_where('roleaccess', ['roleid' => $roleid, 'menuid' => $menu_id])->row();
            if (!$akses_ada) {
                $this->db->insert('roleaccess', [
                    'roleid'    => $roleid,
                    'menuid'    => $menu_id,
                    'created'   => date('Y-m-d H:i:s'),
                    'createdby' => 1
                ]);
            }
        }
    }
}
