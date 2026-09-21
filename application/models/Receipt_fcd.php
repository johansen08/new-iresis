<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Receipt_fcd extends CI_Model
{
    function get_data($data = null) {

        $this->db->select('t.noresi, t.tanggal_printresi, t3.nama_kurir, t2.nama_marketplace, t.nomorpicklist, t.status_pesanan, t4.name, t.created_at, t.id_printresi');
        $this->db->from('tblprintresi t');
        $this->db->join('tblmarketplace t2', 't.id_marketplace = t2.id_marketplace', 'left');
        $this->db->join('tblkurir t3', 't.id_kurir = t3.id_kurir', 'left');
        $this->db->join('tbluser t4', 't.created_by = t4.id_user', 'left');
        $this->db->order_by('t.created_at', 'DESC');

        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        if (!empty($data['search'])) {
            $this->db->group_start();
            foreach ($data['valid_columns'] as $column) {
                if (!empty($column)) {
                    $this->db->or_like($column, $data['search']);
                }
            }
            $this->db->group_end();
        }

        $query = $this->db->get();
        log_message('error', 'Query yang dijalankan: ' . $this->db->last_query());
        return $query;
    }

    public function get_total_data($data = null)
    {
        $this->db->select('t.noresi, t.tanggal_printresi, t3.nama_kurir, t2.nama_marketplace, t.nomorpicklist, t.status_pesanan, t4.name, t.created_at, t.id_printresi');
        $this->db->from('tblprintresi t');
        $this->db->join('tblmarketplace t2', 't.id_marketplace = t2.id_marketplace', 'left');
        $this->db->join('tblkurir t3', 't.id_kurir = t3.id_kurir', 'left');
        $this->db->join('tbluser t4', 't.created_by = t4.id_user', 'left');
        $this->db->order_by('t.created_at', 'DESC');

        // Apply search filter if available
        if (!empty($data['search'])) {
            $this->db->group_start();
            foreach ($data['valid_columns'] as $column) {
                if (!empty($column)) {
                    $this->db->or_like($column, $data['search']);
                }
            }
            $this->db->group_end();
        }

        return $this->db->count_all_results();
    }

    function get_detail_receipt($noresi) {
        pastikan_resi_live($noresi); // resi lama yang sudah diarsipkan ditarik dulu ke prod (docs/ARSIP_DATA.md)
        $this->db->select('
            pr.id_printresi,
            pr.noresi,
            dr.sku,
            dr.jumlah,
            dr.no_rak
        ');
        $this->db->from('tblprintresi pr');
        $this->db->join('tbldetailprintresi dr', 'dr.id_resi = pr.id_printresi');
        $this->db->where('pr.noresi', $noresi);
        return $this->db->get();
    }

    function get_receipt_for_packer($data, $noresi) {
        $this->db->select('
            pr.noresi,
            pr.id_printresi,
            dr.sku,
            dr.jumlah,
            dr.no_rak,
            s.nama_sku,
            s.link_foto,
            s.foto_lokal,
            s.jenis_packing,
            rab.yangambil_pegawai,
            u.name
        ');
        $this->db->from('tblprintresi pr');
        $this->db->join('tbldetailprintresi dr', 'dr.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblsku s', 's.id_sku = dr.sku', 'left');
        $this->db->join('tblresiambilbarang rab', 'rab.id_resi = pr.id_printresi', 'left');
        $this->db->join('tbluser u', 'u.id_pegawai = rab.yangambil_pegawai', 'left');
        $this->db->join('tblpacking p', 'p.id_resi = pr.id_printresi', 'left');

        $this->db->where('pr.noresi', $noresi);
        //$this->db->where('p.id_resi IS NULL'); // Only unpacked items

        // Optional: pagination
        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        // Order by latest
        $this->db->order_by('pr.created_at', 'DESC');

        // Di sini dulu ada log_message('error', ...) berisi SQL lengkap. Levelnya
        // 'error' padahal isinya debug, jadi lolos log_threshold = 1 dan ikut
        // menulis file di SETIAP pemanggilan -- 4.815 baris pada satu hari kerja,
        // dan application/logs sudah menggelembung ke 75 MB. Sudah dihapus.
        return $this->db->get()->result();
    }

    function get_total_receipt_for_packer($noresi) {
        $this->db->from('tblprintresi pr');
        $this->db->join('tbldetailprintresi dr', 'dr.id_resi = pr.id_printresi', 'left');
        $this->db->join('tblpacking p', 'p.id_resi = pr.id_printresi', 'left');
        $this->db->where('pr.noresi', $noresi);
        $this->db->where('p.id_resi IS NULL'); // Only unpacked items

        return $this->db->count_all_results();
    }

    function save($receipt, $id_user)
    {
        $existing_data = $this->get_existing_receipt_data_scan($receipt);
        if ($existing_data) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor resi sudah completed'];
        }

        $receipt['tanggal_printresi'] = date('Y-m-d H:i:s');
        $receipt['admin_pegawai'] = $id_user;

        $this->db->insert('tblprintresi', $receipt);

        $receipt['id_printresi'] = $this->db->insert_id();
        $receipt['affected_rows'] = 1;

        return $receipt;
    }

    function save_reprint($receipt, $id_user)
    {
        $receipt['tanggal_printresi'] = date('Y-m-d H:i:s');
        $receipt['admin_pegawai'] = $id_user;

        $this->db->insert('tblprintulangresi', $receipt);

        $receipt['id_printresi'] = $this->db->insert_id();
        $receipt['affected_rows'] = 1;

        return $receipt;
    }

    function get_detail($noresi)
    {
        $this->db->select('t.noresi, t.toko, t.tanggal_printresi, t2.nama_marketplace, t3.tanggal_resiambilbarang
            , t4.nama_pegawai picker, t5.tanggal_packing, t6.name packer
            , COALESCE(t5.keterangan, t6.nama_komputer) komputer_packer_no, t7.nama_kurir, t8.tanggal_cetak
            , t8.tanggal_resikeluar, t.status_pesanan, t.tanggal_retur, t.tanggal_bataskirim
            , sp_picker.status_name as picker_status
            , sp_packer.status_name as packer_status
            , retur.tanggal_resiretur as tanggal_diterima
            , (SELECT MAX(tanggal_buka_retur) FROM tblbukaretur WHERE resi_buka = t.noresi) as tanggal_dibuka
            , (SELECT GROUP_CONCAT(DISTINCT status_detail_buka SEPARATOR ", ") FROM tblbukaretur WHERE resi_buka = t.noresi) as status_dibuka
            , verif.verified_at as tanggal_acc'
        );

        $this->db->join('tblmarketplace t2', 't2.id_marketplace = t.id_marketplace', 'left');
        $this->db->join('tblresiambilbarang t3', 't3.id_resi = t.id_printresi', 'left');
        $this->db->join('tblpegawai t4', 't4.kode_pegawai = t3.yangambil_pegawai', 'left');
        $this->db->join('tblpacking t5', 't5.id_resi = t.id_printresi', 'left');
        $this->db->join('tbluser t6', 't6.id_user = t5.packer_pegawai', 'left');
        // Status performa diambil dari kolom per-resi, bukan tebakan waktu terdekat di tblkpi
        $this->db->join('tblmasterstatusperforma sp_picker', 'sp_picker.id_statusperforma = t3.status_performa_id', 'left');
        $this->db->join('tblmasterstatusperforma sp_packer', 'sp_packer.id_statusperforma = t5.status_performa_id', 'left');
        $this->db->join('tblkurir t7', 't7.id_kurir = t.id_kurir', 'left');
        $this->db->join('tblresikeluar t8', 't8.id_resi = t.id_printresi', 'left');
        $this->db->join('tblresiretur retur', 'retur.noresi = t.noresi', 'left');
        $this->db->join('tblreturverifikasi verif', 'verif.no_resi = t.noresi AND verif.verified = 1', 'left');

        $this->db->where(['t.noresi' => $noresi]);
        $this->db->order_by('t.created_at', 'DESC');
        $this->db->limit(1);

        return $this->db->get('tblprintresi t');
    }

    function get_detail_items($noresi)
    {
        $this->db->select('t9.sku, t9.jumlah, t9.no_pesanan, COALESCE(s.no_rak, "BELUM DITENTUKAN") as no_rak');
        $this->db->join('tbldetailprintresi t9', 't9.id_resi = t.id_printresi', 'inner');
        $this->db->join('tblsku s', 's.id_sku = t9.sku', 'left');
        $this->db->where(['t.noresi' => $noresi]);
        $this->db->order_by('t9.id_detail_resi', 'ASC');

        return $this->db->get('tblprintresi t');
    }

    /**
     * Data satu resi berdasarkan id_printresi untuk label cetak
     * (Receipt::print_label, dibuka otomatis setelah Scan Resi manual).
     * Kolom yang dipilih mengikuti kebutuhan view receipt/print_label.
     */
    function get_detail_by_id($id_resi)
    {
        $this->db->select('t.id_printresi, t.noresi, t.toko, t.nomorpicklist, t.tanggal_printresi
            , t2.nama_marketplace, t7.nama_kurir');
        $this->db->join('tblmarketplace t2', 't2.id_marketplace = t.id_marketplace', 'left');
        $this->db->join('tblkurir t7', 't7.id_kurir = t.id_kurir', 'left');
        $this->db->where('t.id_printresi', (int) $id_resi);
        $this->db->limit(1);

        return $this->db->get('tblprintresi t');
    }

    /**
     * Resolusi nomor resi dari hasil scan/input.
     * Mendukung input berupa nomor resi maupun nomor pesanan.
     */
    function resolve_noresi($keyword)
    {
        // Coba cocokkan langsung sebagai nomor resi.
        $this->db->select('t.noresi');
        $this->db->where('t.noresi', $keyword);
        $this->db->order_by('t.created_at', 'DESC');
        $this->db->limit(1);
        $row = $this->db->get('tblprintresi t')->row_array();
        if (!empty($row)) {
            return $row['noresi'];
        }

        // Fallback: cocokkan sebagai nomor pesanan pada detail resi.
        $this->db->select('t.noresi');
        $this->db->join('tbldetailprintresi d', 'd.id_resi = t.id_printresi', 'inner');
        $this->db->where('d.no_pesanan', $keyword);
        $this->db->order_by('t.created_at', 'DESC');
        $this->db->limit(1);
        $row = $this->db->get('tblprintresi t')->row_array();

        return !empty($row) ? $row['noresi'] : null;
    }

    function get_total_scan_user($id_user)
    {
        $this->db->select('count(1) as total_scan');

        $criterias = [
            'tanggal_printresi >= ' => date('Y-m-d'),
            'admin_pegawai' => $id_user,
        ];

        $this->db->where($criterias);

        return $this->db->get_where('tblprintresi');
    }

    /*
     * Laporan > Resi Dalam Proses (Report::get_receipt_in_process_data_tab0..2).
     *
     * Hint USE INDEX FOR ORDER BY () pada tblprintresi di query data ketiga tab
     * JANGAN dibuang. Tanpa hint, ORDER BY + LIMIT membuat MariaDB 10.4 memilih
     * menyisir indeks kolom urutan (idx_printresi_btk untuk Batas Kirim, urutan
     * default tabel) dari awal sambil berharap cepat menemukan 10 baris yang
     * masuk rentang tanggal. Kenyataannya ia membaca hampir seluruh ~2,5 juta
     * baris tblprintresi. Diukur 2026-09-15 (iresis_prod lokal):
     *
     *     tab 0, rentang hari ini   21,3 dtk -> 0,03 dtk
     *     tab 0, rentang bulan ini  22,1 dtk -> 0,7 dtk
     *     tab 1, rentang bulan ini  19,8 dtk -> 1,6 dtk
     *     tab 2, rentang bulan ini  24,6 dtk -> 0,9 dtk (ditambah NOT EXISTS)
     *
     * Dengan hint, indeks tanggal tetap dipakai untuk menyaring; hanya
     * pengurutan yang dikerjakan filesort atas hasil yang sudah tersaring.
     * Query Builder membiarkan nama tabel yang mengandung tanda kurung apa
     * adanya (DB_driver::protect_identifiers), jadi hint aman ditulis di
     * get()/join().
     */
    function get_data_receipt_process_tab0($data, $start_date, $end_date)
    {
        if (!empty($data) && $data['order'] != null) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
            // Pemecah seri: banyak resi berbatas kirim sama (atau NULL). Tanpa ini
            // urutan antarhalaman tidak pasti, baris bisa dobel atau terlewat.
            $this->db->order_by('t.id_printresi', 'ASC');
        }

        if (!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->select('
            t3.nama_marketplace,
            t.tanggal_printresi,
            t.noresi,
            t4.nama_kurir,
            t.nomorpicklist,
            t.status_pesanan,
            t.tanggal_bataskirim
        ');

        $this->db->distinct();
        $this->db->join('tblresiambilbarang t2', 't2.id_resi = t.id_printresi', 'left');
        $this->db->join('tblmarketplace t3', 't3.id_marketplace = t.id_marketplace', 'left');
        $this->db->join('tblkurir t4', 't4.id_kurir = t.id_kurir', 'left');
        $this->db->where('t.tanggal_printresi >=', $start_date);
        $this->db->where('t.tanggal_printresi <=', $end_date);
        $this->db->where('t2.id_resiambilbarang is null');

        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        // USE INDEX FOR ORDER BY (): lihat catatan di atas fungsi ini.
        return $this->db->get('tblprintresi t USE INDEX FOR ORDER BY ()');
    }

    function get_total_data_receipt_process_tab0($data, $start_date, $end_date)
    {
        if (!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->join('tblresiambilbarang t2', 't2.id_resi = t.id_printresi', 'left');
        $this->db->join('tblmarketplace t3', 't3.id_marketplace = t.id_marketplace', 'left');
        $this->db->join('tblkurir t4', 't4.id_kurir = t.id_kurir', 'left');
        $this->db->where('t.tanggal_printresi >=', $start_date);
        $this->db->where('t.tanggal_printresi <=', $end_date);
        $this->db->where('t2.id_resiambilbarang is null');

        $query = $this->db->select("COUNT(DISTINCT t.noresi) AS num")->get("tblprintresi t");
        $result = $query->row();

        return isset($result) ? $result->num : 0;
    }

    function get_data_receipt_process_tab1($data, $start_date, $end_date)
    {
        if (!empty($data) && $data['order'] != null) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
            $this->db->order_by('t2.id_printresi', 'ASC'); // pemecah seri, lihat tab0
        }

        if (!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->select('
            t3.nama_marketplace,
            t2.tanggal_printresi,
            t2.noresi,
            t4.nama_kurir,
            t2.nomorpicklist,
            t2.status_pesanan,
            t.tanggal_resiambilbarang,
            t5.nama_pegawai picker,
            t2.tanggal_bataskirim
        ');

        $this->db->distinct();
        // USE INDEX FOR ORDER BY (): lihat catatan di atas get_data_receipt_process_tab0().
        $this->db->join('tblprintresi t2 USE INDEX FOR ORDER BY ()', 't2.id_printresi = t.id_resi', 'left');
        $this->db->join('tblmarketplace t3', 't3.id_marketplace = t2.id_marketplace', 'left');
        $this->db->join('tblkurir t4', 't4.id_kurir = t2.id_kurir', 'left');
        $this->db->join('tblpegawai t5', 't5.kode_pegawai = t.yangambil_pegawai', 'left');
        $this->db->join('tblpacking t6', 't6.id_resi = t.id_resi', 'left');

        $this->db->where('t2.tanggal_printresi >=', $start_date);
        $this->db->where('t2.tanggal_printresi <=', $end_date);
        $this->db->where('t6.id_packing is null');

        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        return $this->db->get('tblresiambilbarang t');
    }

    function get_total_data_receipt_process_tab1($data, $start_date, $end_date)
    {
        if (!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->join('tblprintresi t2', 't2.id_printresi = t.id_resi', 'left');
        $this->db->join('tblmarketplace t3', 't3.id_marketplace = t2.id_marketplace', 'left');
        $this->db->join('tblkurir t4', 't4.id_kurir = t2.id_kurir', 'left');
        $this->db->join('tblpegawai t5', 't5.kode_pegawai = t.yangambil_pegawai', 'left');
        $this->db->join('tblpacking t6', 't6.id_resi = t.id_resi', 'left');
        $this->db->where('t2.tanggal_printresi >=', $start_date);
        $this->db->where('t2.tanggal_printresi <=', $end_date);
        $this->db->where('t6.id_packing is null');

        $query = $this->db->select("COUNT(DISTINCT t2.noresi) AS num")->get("tblresiambilbarang t");
        $result = $query->row();

        return isset($result) ? $result->num : 0;
    }

    function get_data_receipt_process_tab2($data, $start_date, $end_date)
    {
        if (!empty($data) && $data['order'] != null) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
            $this->db->order_by('t2.id_printresi', 'ASC'); // pemecah seri, lihat tab0
        }

        if (!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->select('
            t4.nama_marketplace,
            t2.tanggal_printresi,
            t2.noresi,
            t2.status_pesanan,
            t5.nama_kurir,
            t2.nomorpicklist,
            t3.tanggal_resiambilbarang,
            t6.nama_pegawai picker,
            t7.name packer,
            t2.tanggal_bataskirim
        ');

        $this->db->distinct();
        // USE INDEX FOR ORDER BY (): lihat catatan di atas get_data_receipt_process_tab0().
        $this->db->join('tblprintresi t2 USE INDEX FOR ORDER BY ()', 't2.id_printresi = t.id_resi', 'left');
        $this->db->join('tblresiambilbarang t3', 't3.id_resi = t2.id_printresi', 'left');
        $this->db->join('tblmarketplace t4', 't4.id_marketplace = t2.id_marketplace', 'left');
        $this->db->join('tblkurir t5', 't5.id_kurir = t2.id_kurir', 'left');
        $this->db->join('tblpegawai t6', 't6.kode_pegawai = t3.yangambil_pegawai', 'left');
        $this->db->join('tbluser t7', 't7.id_user = t.packer_pegawai', 'left');

        $this->db->where('t2.tanggal_printresi >=', $start_date);
        $this->db->where('t2.tanggal_printresi <=', $end_date);
        // NOT EXISTS, bukan LEFT JOIN tblresikeluar ... IS NULL. Sebagai anti-join,
        // MariaDB 10.4 menaruh cek ini paling akhir, sesudah lookup ke
        // tblresiambilbarang dan tblpacking (masing-masing ~2,5 juta baris) untuk
        // setiap resi di rentang tanggal. Sebagai subquery ia langsung membuang resi
        // yang sudah keluar begitu baris tblprintresi terbaca: 2,8 -> 1,1 dtk.
        $this->db->where('NOT EXISTS (SELECT 1 FROM tblresikeluar t8 WHERE t8.id_resi = t2.id_printresi)', NULL, FALSE);

        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        return $this->db->get('tblpacking t');
    }

    function get_total_data_receipt_process_tab2($data, $start_date, $end_date)
    {
        if (!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->join('tblprintresi t2', 't2.id_printresi = t.id_resi', 'left');
        $this->db->join('tblresiambilbarang t3', 't3.id_resi = t2.id_printresi', 'left');
        $this->db->join('tblmarketplace t4', 't4.id_marketplace = t2.id_marketplace', 'left');
        $this->db->join('tblkurir t5', 't5.id_kurir = t2.id_kurir', 'left');
        $this->db->join('tblpegawai t6', 't6.kode_pegawai = t3.yangambil_pegawai', 'left');
        $this->db->join('tbluser t7', 't7.id_user = t.packer_pegawai', 'left');

        $this->db->where('t2.tanggal_printresi >=', $start_date);
        $this->db->where('t2.tanggal_printresi <=', $end_date);
        // Lihat catatan NOT EXISTS di get_data_receipt_process_tab2(): 2,1 -> 1,0 dtk.
        $this->db->where('NOT EXISTS (SELECT 1 FROM tblresikeluar t8 WHERE t8.id_resi = t2.id_printresi)', NULL, FALSE);

        $query = $this->db->select("COUNT(DISTINCT t2.noresi) AS num")->get("tblpacking t");
        $result = $query->row();

        return isset($result) ? $result->num : 0;
    }

    function get_data_daily_report($data, $start_date, $end_date)
    {
        $this->db->select('
            f.nama_marketplace,
            a.toko as nama_toko,
            e.nama_kurir,
            a.noresi,
            a.status_pesanan,
            a.nomorpicklist,
            a.tanggal_printresi,
            t1.nama_pegawai as admin_scan,
            b.tanggal_resiambilbarang,
            t2.nama_pegawai as admin_picker,
            COALESCE(sp_picker.status_name, "") as picker_status,
            c.tanggal_packing,
            t3.name as admin_packer,
            COALESCE(sp_packer.status_name, "") as packer_status,
            d.tanggal_resikeluar,
            t4.nama_pegawai as admin_ho
        ');
        $this->db->from('tblprintresi a');
        $this->db->join('tblresiambilbarang b', 'a.id_printresi = b.id_resi', 'left');
        $this->db->join('tblpacking c', 'a.id_printresi = c.id_resi', 'left');
        $this->db->join('tblresikeluar d', 'a.id_printresi = d.id_resi', 'left');
        $this->db->join('tblkurir e', 'e.id_kurir = a.id_kurir', 'left');
        $this->db->join('tblmarketplace f', 'f.id_marketplace = a.id_marketplace', 'left');
        $this->db->join('tblpegawai t1', 't1.kode_pegawai = a.admin_pegawai', 'left');
        $this->db->join('tblpegawai t2', 't2.kode_pegawai = b.yangambil_pegawai', 'left');
        $this->db->join('tbluser t3', 't3.id_user = c.packer_pegawai', 'left');
        $this->db->join('tblpegawai t4', 't4.kode_pegawai = d.id_pegawai', 'left');
        $this->db->join('tblmasterstatusperforma sp_picker', 'sp_picker.id_statusperforma = b.status_performa_id', 'left');
        $this->db->join('tblmasterstatusperforma sp_packer', 'sp_packer.id_statusperforma = c.status_performa_id', 'left');

        $this->db->where('a.tanggal_printresi >=', $start_date);
        $this->db->where('a.tanggal_printresi <=', $end_date);

        if (!empty($data['search'])) {
            $x = 0;
            $this->db->group_start();
            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;
                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }
                $x++;
            }
            $this->db->group_end();
        }

        if (!empty($data['order'])) {
            $this->db->order_by($data['order'], $data['dir']);
        } else {
            $this->db->order_by('a.tanggal_printresi', 'DESC');
        }

        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        return $this->db->get();
    }

    function get_total_data_daily_report($data, $start_date, $end_date)
    {
        $this->db->from('tblprintresi a');
        $this->db->where('a.tanggal_printresi >=', $start_date);
        $this->db->where('a.tanggal_printresi <=', $end_date);

        if (!empty($data['search'])) {
            $this->db->join('tblresiambilbarang b', 'a.id_printresi = b.id_resi', 'left');
            $this->db->join('tblpacking c', 'a.id_printresi = c.id_resi', 'left');
            $this->db->join('tblresikeluar d', 'a.id_printresi = d.id_resi', 'left');
            $this->db->join('tblkurir e', 'e.id_kurir = a.id_kurir', 'left');
            $this->db->join('tblmarketplace f', 'f.id_marketplace = a.id_marketplace', 'left');
            $this->db->join('tblpegawai t1', 't1.kode_pegawai = a.admin_pegawai', 'left');
            $this->db->join('tblpegawai t2', 't2.kode_pegawai = b.yangambil_pegawai', 'left');
            $this->db->join('tbluser t3', 't3.id_user = c.packer_pegawai', 'left');
            $this->db->join('tblpegawai t4', 't4.kode_pegawai = d.id_pegawai', 'left');

            $x = 0;
            $this->db->group_start();
            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;
                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }
                $x++;
            }
            $this->db->group_end();
        }

        return $this->db->count_all_results();
    }

    function get_header_daily_report($start_date, $end_date)
    {
        // OPTIMIZED V5: 4 separate simple queries are much faster than 1 big join on large datasets
        
        // 1. Scan Resi
        $q_scan = $this->db->select("COUNT(a.id_printresi) as num")
                           ->where('a.tanggal_printresi >=', $start_date)
                           ->where('a.tanggal_printresi <=', $end_date)
                           ->get("tblprintresi a");
        $total_scan = $q_scan->row()->num;

        // 2. Pick Resi
        $q_pick = $this->db->select("COUNT(b.id_resiambilbarang) as num")
                           ->join('tblprintresi a', 'a.id_printresi = b.id_resi')
                           ->where('a.tanggal_printresi >=', $start_date)
                           ->where('a.tanggal_printresi <=', $end_date)
                           ->get("tblresiambilbarang b");
        $total_pick = $q_pick->row()->num;

        // 3. Pack Resi
        $q_pack = $this->db->select("COUNT(c.id_packing) as num")
                           ->join('tblprintresi a', 'a.id_printresi = c.id_resi')
                           ->where('a.tanggal_printresi >=', $start_date)
                           ->where('a.tanggal_printresi <=', $end_date)
                           ->get("tblpacking c");
        $total_pack = $q_pack->row()->num;

        // 4. HO Resi
        $q_ho = $this->db->select("COUNT(d.id_resikeluar) as num")
                          ->join('tblprintresi a', 'a.id_printresi = d.id_resi')
                          ->where('a.tanggal_printresi >=', $start_date)
                          ->where('a.tanggal_printresi <=', $end_date)
                          ->get("tblresikeluar d");
        $total_ho = $q_ho->row()->num;

        // Package result in a way that matches row_array() expected by controller
        $result = new stdClass();
        $result->total_scan_resi = $total_scan;
        $result->total_pick_resi = $total_pick;
        $result->total_pack_resi = $total_pack;
        $result->total_ho_resi = $total_ho;

        // Return a mock query object that works with ->row_array()
        return new class($result) {
            private $data;
            public function __construct($data) { $this->data = $data; }
            public function row_array() { return (array)$this->data; }
        };
    }

    function destroy($id_printresi, $id_user)
    {
        /**
         * 1. get resi from tblprintresi
         * 2. insert resi from #1 into tblprintresihapus with added field (`admin_pegawai_hapus`, `tanggal_printresi_hapus`)
         * 3. delete related records from tbldetailprintresi
         * 4. delete resi from tblprintresi
         */

        $receipt = $this->db->get_where('tblprintresi', ['id_printresi' => $id_printresi])->row_array();

        if (empty($receipt)) {
            $receipt['affected_rows'] = 0;

            return $receipt;
        }

        //$receipt['admin_pegawai_hapus'] = $id_user;
        //$receipt['tanggal_printresi_hapus'] = date('Y-m-d H:i:s');

        $data = [
            'id_printresi' => $receipt['id_printresi'],
            'tanggal_printresi' => $receipt['tanggal_printresi'],
            'id_marketplace' => $receipt['id_marketplace'],
            'noresi' => $receipt['noresi'],
            'nomorpicklist' => $receipt['nomorpicklist'],
            'batal' => $receipt['batal'],
            'keterangan' => $receipt['keterangan'],
            'id_kurir' => $receipt['id_kurir'],
            'admin_pegawai' => $receipt['admin_pegawai'],
            'admin_pegawai_hapus' => $id_user,
            'tanggal_printresi_hapus' => date('Y-m-d H:i:s')
        ];

        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $data[$key] = '';
            }
        }

        $this->db->insert('tblprintresihapus', $data);

        // Delete related records from tbldetailprintresi first
        $this->db->delete('tbldetailprintresi', ['id_resi' => $id_printresi]);

        $this->db->delete('tblprintresi', ['id_printresi' => $id_printresi]);

        $receipt['affected_rows'] = $this->db->affected_rows();

        return $receipt;
    }

    function destroy_by_noresi($noresi, $user)
    {
        $receipt = $this->db->get_where('tblprintresi', ['noresi' => $noresi])->row_array();
        if (empty($receipt)) {
            return ['error' => TRUE, 'code' => 400, 'message' => 'Nomor resi tidak ditemukan'];
        }

        return $this->destroy($receipt['id_printresi'], $user);
    }

    function get_data_receipt_tab0($data, $start_date, $end_date)
    {
        if (!empty($data) && $data['order'] != null) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
        }

        if (!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->select('
            t.tanggal_printresi,
            t3.nama_marketplace,
            t.nomorpicklist,
            t4.nama_kurir,
            t.noresi,
            t6.tanggal_resikeluar,
            t7.nama_pegawai as picker,
            t8.nama_pegawai as packer
        ');

        $this->db->distinct();
        $this->db->join('tblresiambilbarang t2', 't2.id_resi = t.id_printresi', 'left');
        $this->db->join('tblmarketplace t3', 't3.id_marketplace = t.id_marketplace', 'left');
        $this->db->join('tblkurir t4', 't4.id_kurir = t.id_kurir', 'left');
        $this->db->join('tblpacking t5', 't5.id_resi = t.id_printresi', 'left');
        $this->db->join('tblresikeluar t6', 't6.id_resi = t.id_printresi', 'left');
        $this->db->join('tblpegawai t7', 't7.kode_pegawai = t2.yangambil_pegawai', 'left');
        $this->db->join('tblpegawai t8', 't8.kode_pegawai = t6.id_pegawai', 'left');
        $this->db->where('t.tanggal_printresi >=', $start_date);
        $this->db->where('t.tanggal_printresi <=', $end_date);

        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        return $this->db->get('tblprintresi t');
    }

    function get_total_data_receipt_tab0($data, $start_date, $end_date)
    {
        if (!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->join('tblresiambilbarang t2', 't2.id_resi = t.id_printresi', 'left');
        $this->db->join('tblmarketplace t3', 't3.id_marketplace = t.id_marketplace', 'left');
        $this->db->join('tblkurir t4', 't4.id_kurir = t.id_kurir', 'left');
        $this->db->join('tblpacking t5', 't5.id_resi = t.id_printresi', 'left');
        $this->db->join('tblresikeluar t6', 't6.id_resi = t.id_printresi', 'left');
        $this->db->join('tblpegawai t7', 't7.kode_pegawai = t2.yangambil_pegawai', 'left');
        $this->db->join('tblpegawai t8', 't8.kode_pegawai = t6.id_pegawai', 'left');

        $this->db->where('t.tanggal_printresi >=', $start_date);
        $this->db->where('t.tanggal_printresi <=', $end_date);

        $query = $this->db->select("COUNT(DISTINCT t.noresi) AS num")->get("tblprintresi t");
        $result = $query->row();

        return isset($result) ? $result->num : 0;
    }

    function get_data_receipt_tab1($data, $start_date, $end_date)
    {
        if (!empty($data) && $data['order'] != null) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
        }

        if (!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->select('
            date(t.tanggal_printresi) as tanggal_printresi,
            t.nomorpicklist,
            COUNT(1) as total
        ');

        $this->db->where('t.tanggal_printresi >=', $start_date);
        $this->db->where('t.tanggal_printresi <=', $end_date);

        $this->db->group_by('date(t.tanggal_printresi), t.nomorpicklist');

        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        return $this->db->get('tblprintresi t');
    }

    function get_total_data_receipt_tab1($data, $start_date, $end_date)
    {
        if (!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->where('t.tanggal_printresi >=', $start_date);
        $this->db->where('t.tanggal_printresi <=', $end_date);

        $this->db->group_by('date(t.tanggal_printresi), t.nomorpicklist');

        $query = $this->db->select("count(1) as num")->get("tblprintresi t");
        $result = $query->num_rows();

        return isset($result) ? $result : 0;
    }

    function get_grand_total_data_receipt_tab1($data, $start_date, $end_date)
    {
        if (!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->where('t.tanggal_printresi >=', $start_date);
        $this->db->where('t.tanggal_printresi <=', $end_date);

        $query = $this->db->select("count(1) as num")->get("tblprintresi t");
        $result = $query->row()->num;

        return isset($result) ? $result : 0;
    }

    function get_data_shipped_report($data, $start_date, $end_date)
    {
        if (!empty($data) && $data['order'] != null) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
        }

        if (!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->select('
            t2.tanggal_resikeluar
            , t2.tanggal_resikeluar
            , t.noresi
            , t3.nama_kurir
            , t2.tanggal_cetak
        ');

        $this->db->distinct();
        $this->db->join('tblresikeluar t2', 't2.id_resi = t.id_printresi');
        $this->db->join('tblkurir t3', 't3.id_kurir = t.id_kurir', 'left');

        $this->db->where('t2.tanggal_resikeluar >=', $start_date);
        $this->db->where('t2.tanggal_resikeluar <=', $end_date);

        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        return $this->db->get('tblprintresi t');
    }

    function get_total_data_shipped_report($data, $start_date, $end_date)
    {
        if (!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->join('tblresikeluar t2', 't2.id_resi = t.id_printresi');

        $this->db->join('tblkurir t3', 't3.id_kurir = t.id_kurir', 'left');

        $this->db->where('t2.tanggal_resikeluar >=', $start_date);
        $this->db->where('t2.tanggal_resikeluar <=', $end_date);

        $query = $this->db->select("COUNT(DISTINCT t.noresi) AS num")->get("tblprintresi t");
        $result = $query->row();

        return isset($result) ? $result->num : 0;
    }

    function get_data_shipping_report($start_date, $end_date)
    {
        $this->db->select('
            coalesce(t3.nama_kurir, \'- Tidak diketahui -\') nama_kurir
            , count(1) total
        ');

        $this->db->join('tblprintresi t2', 't2.id_printresi = t.id_resi');

        $this->db->join('tblkurir t3', 't3.id_kurir = t2.id_kurir', 'left');

        $this->db->where('t.tanggal_resikeluar >=', $start_date);
        $this->db->where('t.tanggal_resikeluar <=', $end_date);

        $this->db->group_by('t3.nama_kurir');

        $this->db->order_by('t3.nama_kurir');

        return $this->db->get('tblresikeluar t');
    }

    function get_grand_total_data_shipping_report($start_date, $end_date)
    {
        $this->db->where('t.tanggal_resikeluar >=', $start_date);
        $this->db->where('t.tanggal_resikeluar <=', $end_date);

        $query = $this->db->select("count(1) as num")->get("tblresikeluar t");
        $result = $query->row()->num;

        return isset($result) ? $result : 0;
    }

    function get_data_per_day_report($data, $start_date, $end_date)
    {
        if (!empty($data) && $data['order'] != null) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
        }

        if (!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->select('
            t.noresi
            , t5.nama_marketplace
            , t6.nama_kurir
            , t.nomorpicklist
            , t.tanggal_printresi
            , t2.tanggal_resiambilbarang
            , t3.tanggal_packing
            , t4.tanggal_resikeluar
        ');

        $this->db->distinct();
        $this->db->join('tblresiambilbarang t2', 't.id_printresi = t2.id_resi', 'left');
        $this->db->join('tblpacking t3', 't.id_printresi = t3.id_resi', 'left');
        $this->db->join('tblresikeluar t4', 't.id_printresi = t4.id_resi', 'left');
        $this->db->join('tblmarketplace t5', 't5.id_marketplace = t.id_marketplace', 'left');
        $this->db->join('tblkurir t6', 't6.id_kurir = t.id_kurir', 'left');

        $this->db->where('t.tanggal_printresi >=', $start_date);
        $this->db->where('t.tanggal_printresi <=', $end_date);
        $this->db->group_start()
            ->where('t2.id_resiambilbarang', null)
            ->or_where('t3.id_packing', null)
            ->or_where('t4.id_resikeluar', null);
        $this->db->group_end();

        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        return $this->db->get('tblprintresi t');
    }

    function get_total_data_per_day_report($data, $start_date, $end_date)
    {
        if (!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->join('tblresiambilbarang t2', 't.id_printresi = t2.id_resi', 'left');
        $this->db->join('tblpacking t3', 't.id_printresi = t3.id_resi', 'left');
        $this->db->join('tblresikeluar t4', 't.id_printresi = t4.id_resi', 'left');
        $this->db->join('tblmarketplace t5', 't5.id_marketplace = t.id_marketplace', 'left');
        $this->db->join('tblkurir t6', 't6.id_kurir = t.id_kurir', 'left');

        $this->db->where('t.tanggal_printresi >=', $start_date);
        $this->db->where('t.tanggal_printresi <=', $end_date);
        $this->db->group_start()
            ->where('t2.id_resiambilbarang', null)
            ->or_where('t3.id_packing', null)
            ->or_where('t4.id_resikeluar', null);
        $this->db->group_end();

        $query = $this->db->select("COUNT(DISTINCT t.noresi) as num")->get("tblprintresi t");
        $result = $query->row();

        return isset($result) ? $result->num : 0;
    }

    /**
     * Dipakai auto_upload_resi_api (Jubelio core-api) untuk skip fetch detail
     * item Jubelio bagi resi yang statusnya sudah final di iresis.
     */
    function get_completed_noresi(array $noresi_list)
    {
        $completed = [];
        $chunks = array_chunk(array_values(array_unique($noresi_list)), 1000);

        foreach ($chunks as $chunk) {
            $this->db->select('noresi');
            $this->db->from('tblprintresi');
            $this->db->where('status_pesanan', 'COMPLETED');
            $this->db->where_in('noresi', $chunk);
            foreach ($this->db->get()->result() as $row) {
                $completed[] = $row->noresi;
            }
        }

        return $completed;
    }

    /**
     * Versi lebih lengkap dari get_completed_noresi(): balikin status_pesanan
     * SAAT INI untuk semua noresi yang sudah ada di iresis (apapun statusnya,
     * bukan cuma COMPLETED). Dipakai auto_upload_resi_api untuk skip panggil
     * detail Jubelio kalau status TIDAK BERUBAH sejak upload terakhir -
     * mayoritas resi di window H-3 sudah SHIPPED (bukan cuma COMPLETED) jadi
     * filter status-map ini jauh lebih efektif daripada hanya cek COMPLETED.
     */
    function get_status_map(array $noresi_list)
    {
        $map = [];
        $chunks = array_chunk(array_values(array_unique($noresi_list)), 1000);

        foreach ($chunks as $chunk) {
            $this->db->select('noresi, status_pesanan');
            $this->db->from('tblprintresi');
            $this->db->where_in('noresi', $chunk);
            foreach ($this->db->get()->result() as $row) {
                $map[$row->noresi] = strtoupper(trim($row->status_pesanan ?? ''));
            }
        }

        return $map;
    }

    /**
     * PERBAIKAN SEKALI-JALAN untuk data rusak akibat bug run auto_upload_resi_api
     * tanggal 2026-07-04 (qty ter-gandakan karena order dobel di pagination, dan
     * no_rak kosong). NON-DESTRUKTIF: hanya UPDATE kolom jumlah & no_rak pada
     * tbldetailprintresi untuk resi tertentu; TIDAK menyentuh header, picklist,
     * status, maupun link pick/pack. Hanya menulis kalau nilainya memang beda.
     *
     * $corrections: [ ['noresi'=>..., 'nomorpicklist'=>..., 'items'=>[ ['sku'=>..., 'qty'=>int], ... ] ], ... ]
     * nomorpicklist (opsional) = detail.picked_in Jubelio; hanya diisi kalau
     * kolom di iresis saat ini KOSONG (tidak menimpa nilai yang sudah ada).
     * Return ringkasan jumlah baris yang diperbaiki.
     */
    function fix_detail_from_jubelio(array $corrections)
    {
        $qty_fixed = 0;
        $rak_fixed = 0;
        $picklist_fixed = 0;
        $resi_touched = 0;
        $resi_notfound = 0;

        // Kumpulkan semua sku untuk lookup no_rak master sekaligus
        $all_skus = [];
        foreach ($corrections as $c) {
            foreach (($c['items'] ?? []) as $it) {
                if (!empty($it['sku'])) $all_skus[$it['sku']] = true;
            }
        }
        $rak_map = [];
        foreach (array_chunk(array_keys($all_skus), 1000) as $sku_chunk) {
            if (empty($sku_chunk)) continue;
            $this->db->select('id_sku, no_rak');
            $this->db->from('tblsku');
            $this->db->where_in('id_sku', $sku_chunk);
            foreach ($this->db->get()->result() as $srow) {
                $rak_map[$srow->id_sku] = $srow->no_rak;
            }
        }

        foreach ($corrections as $c) {
            $noresi = $c['noresi'] ?? '';
            $items  = $c['items'] ?? [];
            $has_picklist = trim((string)($c['nomorpicklist'] ?? '')) !== '';
            if ($noresi === '' || (empty($items) && !$has_picklist)) continue;

            // Ambil id_printresi resi ini (utamakan yang di-insert run bermasalah)
            $this->db->select('id_printresi, nomorpicklist');
            $this->db->from('tblprintresi');
            $this->db->where('noresi', $noresi);
            $this->db->order_by('id_printresi', 'DESC');
            $header = $this->db->get()->row();
            if (!$header) { $resi_notfound++; continue; }
            $id_resi = $header->id_printresi;

            // Pulihkan nomorpicklist dari Jubelio (picked_in) HANYA kalau kosong
            $new_picklist = trim((string)($c['nomorpicklist'] ?? ''));
            if ($new_picklist !== '' && ($header->nomorpicklist ?? '') === '') {
                $this->db->where('id_printresi', $id_resi);
                $this->db->update('tblprintresi', ['nomorpicklist' => $new_picklist]);
                $picklist_fixed++;
            }

            // Ambil detail sekarang untuk resi ini
            $this->db->select('id_detail_resi, sku, jumlah, no_rak');
            $this->db->from('tbldetailprintresi');
            $this->db->where('id_resi', $id_resi);
            $current = [];
            foreach ($this->db->get()->result() as $d) {
                $current[$d->sku] = $d;
            }

            $touched = false;
            foreach ($items as $it) {
                $sku = $it['sku'] ?? '';
                $correct_qty = (int)($it['qty'] ?? 0);
                if ($sku === '' || !isset($current[$sku])) continue;

                $d = $current[$sku];
                $upd = [];

                if ((int)$d->jumlah !== $correct_qty) {
                    $upd['jumlah'] = $correct_qty;
                }
                if (($d->no_rak ?? '') === '' && !empty($rak_map[$sku])) {
                    $upd['no_rak'] = $rak_map[$sku];
                }

                if (!empty($upd)) {
                    $this->db->where('id_detail_resi', $d->id_detail_resi);
                    $this->db->update('tbldetailprintresi', $upd);
                    if (isset($upd['jumlah'])) $qty_fixed++;
                    if (isset($upd['no_rak'])) $rak_fixed++;
                    $touched = true;
                }
            }
            if ($touched) $resi_touched++;
        }

        return [
            'resi_touched'   => $resi_touched,
            'qty_fixed'      => $qty_fixed,
            'rak_fixed'      => $rak_fixed,
            'picklist_fixed' => $picklist_fixed,
            'resi_notfound'  => $resi_notfound,
        ];
    }

    /**
     * Impor baris laporan penjualan Jubelio (key kolom A..W) ke tblprintresi +
     * tbldetailprintresi. Dipakai menu Upload Resi, Cron::auto_upload_resi, dan
     * Cron::auto_upload_resi_api.
     *
     * @param array         $receiptData Baris ala Worksheet::toArray(null,true,true,true)
     * @param string|null   $user_id     admin_pegawai / created_by
     * @param callable|null $lapor       Opsional: fn(string $tahap, string $pesan, ?int $persen)
     *                                   dipanggil di tiap tahap agar UI bisa menampilkan progres
     * @return string       Ringkasan hasil, atau pesan "Error ..." bila gagal
     */
    function insert_receipt(array $receiptData, ?string $user_id = null, ?callable $lapor = null) {
        if (empty($receiptData)) return "No data provided";

        $lapor = $lapor ?: function () {};

        $batch_size = 500;
        $total_success_insert = 0;
        $total_skip_insert = 0;
        $total_duplicate_skip = 0;
        $total_skip_update_same_status = 0; // Track skipped updates

        // Matikan db_debug selama impor: kalau ada query gagal, CI jangan
        // mencetak halaman HTML error (merusak respons JSON) -- cukup kembalikan
        // FALSE lalu kita baca $this->db->error() sendiri.
        $db_debug_awal = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        // Start transaction
        $this->db->trans_start();

        try {
            // Lookup caches
            $marketplace_map = [];
            foreach ($this->db->get('tblmarketplace')->result() as $mp) {
                $marketplace_map[strtolower($mp->nama_marketplace)] = $mp->id_marketplace;
            }
            $kurir_map = [];
            foreach ($this->db->get('tblkurir')->result() as $kr) {
                $kurir_map[strtolower($kr->nama_kurir)] = $kr->id_kurir;
            }

            // Helpers
            $excelDateToPhpDate = function ($excelDate) {
                if (is_numeric($excelDate)) {
                    $unixDate = ($excelDate - 25569) * 86400;
                    return gmdate("d/m/Y", $unixDate);
                }
                return $excelDate;
            };
            $excelTimeToPhpTime = function ($excelTime) {
                if (is_numeric($excelTime)) {
                    $totalSeconds = (int) round($excelTime * 86400);
                    $hours = floor($totalSeconds / 3600);
                    $minutes = floor(($totalSeconds % 3600) / 60);
                    $seconds = $totalSeconds % 60;
                    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                }
                return $excelTime;
            };
            $combineDateTime = function ($date, $time) {
                if (!$date || !$time) return null;

                // 1. Try original format d/m/Y H:i:s
                $dt = DateTime::createFromFormat('d/m/Y H:i:s', "$date $time");
                if ($dt) return $dt->format('Y-m-d H:i:s');

                // 2. Try Y-m-d H:i:s (ISO) usually from Excel general format
                $dt = DateTime::createFromFormat('Y-m-d H:i:s', "$date $time");
                if ($dt) return $dt->format('Y-m-d H:i:s');

                // 3. Fallback to smart parsing
                try {
                    $dt = new DateTime("$date $time");
                    return $dt->format('Y-m-d H:i:s');
                } catch (Exception $e) {
                    return null;
                }
            };

            $courier_aliases = [
                'anteraja'          => ['anteraja'],
                'baraka'            => ['baraka'],
                'central cargo'     => ['kargo', 'central cargo'],
                'goto'              => ['goto'],
                'id express'        => ['id express'],
                'instant/sameday'   => ['instant', 'gosend', 'grab'],
                'jemput sendiri'    => ['jemput sendiri'],
                'jne'               => ['jne'],
                'jnt'               => ['j&t', 'jnt'],
                'jnt-fierra'        => ['jnt-fierra'],
                'jnt-kav-dpr'       => ['jnt-kav-dpr'],
                'lazada'            => ['lazada', 'lex id'],
                'ninja'             => ['ninja'],
                'rex'               => ['rex'],
                'sicepat - rekom'   => ['sicepat rekom', 'rekomendasi'],
                'sicepat'           => ['sicepat'],
                'shopee'            => ['spx', 'shopee'],
                'spax'              => ['spax'],
                'wahana'            => ['wahana'],
            ];
            $detectCourier = function(string $raw) use ($courier_aliases) {
                $rawLower = strtolower($raw);
                $section = $rawLower;
                if (stripos($rawLower, 'diantar oleh:') !== false) {
                    $parts = explode('diantar oleh:', $rawLower);
                    $section = trim($parts[1]);
                } elseif (stripos($rawLower, 'delivery:') !== false) {
                    $parts = explode('delivery:', $rawLower);
                    $section = trim($parts[1]);
                }
                foreach ([$section, $rawLower] as $text) {
                    foreach ($courier_aliases as $norm => $alts) {
                        foreach ($alts as $needle) {
                            if (stripos($text, $needle) !== false) return $norm;
                        }
                    }
                }
                return '';
            };

            // Check for existing records with status-based logic
            $existing_noresi = [];
            $update_candidates = [];
            $existing_status_map = [];
            $noresi_list = array_filter(array_unique(array_column($receiptData, 'B')));
            $lapor('cek', 'Memeriksa ' . number_format(count($noresi_list), 0, ',', '.') . ' nomor resi yang sudah ada di database...', 20);
            if (!empty($noresi_list)) {
                // Process in smaller chunks to avoid regex compilation errors
                $chunk_size = 1000; // Process 1000 noresi at a time
                $noresi_chunks = array_chunk($noresi_list, $chunk_size);

                foreach ($noresi_chunks as $chunk) {
                    $this->db->select('noresi, status_pesanan, id_printresi');
                    $this->db->from('tblprintresi');
                    $this->db->where_in('noresi', $chunk);
                    $existing_result = $this->db->get()->result();

                    foreach ($existing_result as $row) {
                        $status = strtoupper(trim($row->status_pesanan ?? ''));
                        $existing_status_map[$row->noresi] = $status;
                        if ($status === 'COMPLETED') {
                            // Skip - status is COMPLETED, do not update
                            $existing_noresi[$row->noresi] = 'completed_skip';
                            $total_duplicate_skip++;
                        } else {
                            // Can be updated - status is null, empty string, or anything except COMPLETED
                            $update_candidates[$row->noresi] = [
                                'id_printresi' => $row->id_printresi,
                                'current_status' => $row->status_pesanan
                            ];
                        }
                    }
                }
            }

            $batch_header_map = [];
            $batch_detail_map = [];
            $batch_update_map = [];
            $total_updated = 0;

            $lapor('olah', 'Mengolah ' . number_format(count($receiptData), 0, ',', '.') . ' baris data...', 30);

            foreach ($receiptData as $row) {
                $noresi             = $row['B'] ?? '';
                $no_pesanan         = $row['A'] ?? null;
                $sku                = $row['P'] ?? '';
                $status_pesanan     = $row['T'] ?? null;
                $new_status_upper   = strtoupper(trim($status_pesanan ?? ''));

                // Skip header row or invalid data
                if ($no_pesanan === 'NO_PESANAN' || !$no_pesanan || !$sku || !$noresi) {
                    $total_skip_insert++;
                    continue;
                }

                // Skip if already exists and is COMPLETED
                if (isset($existing_noresi[$noresi])) {
                    continue; // This will be counted in total_duplicate_skip already
                }

                // Check if this noresi needs to be updated instead of inserted
                if (isset($update_candidates[$noresi])) {
                    $current_status = strtoupper(trim($existing_status_map[$noresi] ?? ''));
                    // Only update if status_pesanan is different (case-insensitive, trim)
                    if ($current_status !== $new_status_upper) {
                        // Normalize marketplace
                        $marketplaceRaw = strtolower($row['N'] ?? '');
                        if (stripos($marketplaceRaw, 'tokopedia') !== false) {
                            $marketplace = 'tokopedia';
                        }
                        elseif (stripos($marketplaceRaw, 'internal') !== false) {
                            $marketplace = 'reseller';
                        }
                        else $marketplace = $marketplaceRaw;
                        $id_marketplace = $marketplace_map[$marketplace] ?? 99;

                        // Normalize courier
                        $kurirRaw = $row['S'] ?? '';
                        $kurir = $detectCourier($kurirRaw);
                        // Override: if marketplace is Lazada but detected courier is JNE, Ninja, J&T, or SiCepat, force Lazada courier
                        if ($marketplace === 'lazada' && in_array($kurir, ['jne', 'ninja', 'j&t', 'jnt', 'jnt-fierra', 'jnt-kav-dpr', 'sicepat', 'sicepat - rekom'], true)) {
                            $kurir = 'lazada';
                        }
                        if ($kurir === 'jnt') {
                            $tanggal_pesan_date = $combineDateTime($excelDateToPhpDate($row['D'] ?? null), $excelTimeToPhpTime($row['E'] ?? null));
                            $dateStr = $tanggal_pesan_date ? substr($tanggal_pesan_date, 0, 10) : '';
                            if ($dateStr !== '') {
                                if ($dateStr < '2024-05-27') {
                                    $id_kurir = $kurir_map['jnt'] ?? 99;
                                } elseif ($dateStr >= '2024-05-27' && $dateStr < '2025-10-13') {
                                    $id_kurir = $kurir_map['jnt-fierra'] ?? 99;
                                } else {
                                    $id_kurir = $kurir_map['jnt-kav-dpr'] ?? 99;
                                }
                            } else {
                                $id_kurir = $kurir_map['jnt-kav-dpr'] ?? 99;
                            }
                        } else {
                            $id_kurir = $kurir_map[$kurir] ?? 99;
                        }

                        $batch_update_map[$noresi] = [
                            'id_printresi'        => $update_candidates[$noresi]['id_printresi'],
                            'id_marketplace'      => $id_marketplace,
                            'id_kurir'            => $id_kurir,
                            'tanggal_pesan'       => $combineDateTime($excelDateToPhpDate($row['D'] ?? null), $excelTimeToPhpTime($row['E'] ?? null)),
                            'tanggal_bataskirim'  => $combineDateTime($excelDateToPhpDate($row['H'] ?? null), $excelTimeToPhpTime($row['I'] ?? null)),
                            'tanggal_pengiriman'  => $combineDateTime($excelDateToPhpDate($row['J'] ?? null), $excelTimeToPhpTime($row['K'] ?? null)),
                            'tanggal_selesai'     => $combineDateTime($excelDateToPhpDate($row['L'] ?? null), $excelTimeToPhpTime($row['M'] ?? null)),
                            'tanggal_retur'       => $combineDateTime($excelDateToPhpDate($row['U'] ?? null), $excelTimeToPhpTime($row['V'] ?? null)),
                            'status_pesanan'      => $row['T'] ?? null,
                            'status_wms'          => $row['W'] ?? null,
                            'modified_at'         => date('Y-m-d H:i:s'),
                            'modified_by'         => $user_id
                        ];
                        // Hanya timpa nomorpicklist kalau ada nilai baru - jangan kosongkan
                        // yang sudah ada (sumber data spt Jubelio core-api tidak punya field ini).
                        if (!empty($row['C'])) {
                            $batch_update_map[$noresi]['nomorpicklist'] = $row['C'];
                        }
                    } else {
                        $total_skip_update_same_status++;
                    }
                    continue;
                }

                // Normalize marketplace
                $marketplaceRaw = strtolower($row['N'] ?? '');
                if (stripos($marketplaceRaw, 'tokopedia') !== false) {
                    $marketplace = 'tokopedia';
                }
                elseif (stripos($marketplaceRaw, 'internal') !== false) {
                    $marketplace = 'reseller';
                }
                else $marketplace = $marketplaceRaw;
                $id_marketplace = $marketplace_map[$marketplace] ?? 99;

                // Normalize courier
                $kurirRaw = $row['S'] ?? '';
                $kurir = $detectCourier($kurirRaw);
                // Override: if marketplace is Lazada but detected courier is JNE, Ninja, J&T, or SiCepat, force Lazada courier
                if ($marketplace === 'lazada' && in_array($kurir, ['jne', 'ninja', 'j&t', 'jnt', 'jnt-fierra', 'jnt-kav-dpr', 'sicepat', 'sicepat - rekom'], true)) {
                    $kurir = 'lazada';
                }
                if ($kurir === 'jnt') {
                    $tanggal_pesan_date = $combineDateTime($excelDateToPhpDate($row['D'] ?? null), $excelTimeToPhpTime($row['E'] ?? null));
                    $dateStr = $tanggal_pesan_date ? substr($tanggal_pesan_date, 0, 10) : '';
                    if ($dateStr !== '') {
                        if ($dateStr < '2024-05-27') {
                            $id_kurir = $kurir_map['jnt'] ?? 99;
                        } elseif ($dateStr >= '2024-05-27' && $dateStr < '2025-10-13') {
                            $id_kurir = $kurir_map['jnt-fierra'] ?? 99;
                        } else {
                            $id_kurir = $kurir_map['jnt-kav-dpr'] ?? 99;
                        }
                    } else {
                        $id_kurir = $kurir_map['jnt-kav-dpr'] ?? 99;
                    }
                } else {
                    $id_kurir = $kurir_map[$kurir] ?? 99;
                }

                // Always set detail_key
                $detail_key = "$noresi-$no_pesanan-$sku";

                // Header: one per noresi
                if (!isset($batch_header_map[$noresi])) {
                    $now = date('Y-m-d H:i:s');
                    $batch_header_map[$noresi] = [
                        'noresi'              => $noresi,
                        'id_marketplace'      => $id_marketplace,
                        'id_kurir'            => $id_kurir,
                        'admin_pegawai'       => $user_id,
                        'tanggal_printresi'   => $now,
                        'tanggal_pesan'       => $combineDateTime($excelDateToPhpDate($row['D'] ?? null), $excelTimeToPhpTime($row['E'] ?? null)),
                        'tanggal_bataskirim'  => $combineDateTime($excelDateToPhpDate($row['H'] ?? null), $excelTimeToPhpTime($row['I'] ?? null)),
                        'tanggal_pengiriman'  => $combineDateTime($excelDateToPhpDate($row['J'] ?? null), $excelTimeToPhpTime($row['K'] ?? null)),
                        'tanggal_selesai'     => $combineDateTime($excelDateToPhpDate($row['L'] ?? null), $excelTimeToPhpTime($row['M'] ?? null)),
                        'tanggal_retur'       => $combineDateTime($excelDateToPhpDate($row['U'] ?? null), $excelTimeToPhpTime($row['V'] ?? null)),
                        'status_pesanan'      => $row['T'] ?? null,
                        'status_wms'          => $row['W'] ?? null,
                        'batal'               => '',
                        'keterangan'          => '',
                        'nomorpicklist'       => $row['C'] ?? '',
                        'created_at'          => $now,
                        'created_by'          => $user_id
                    ];
                }

                // Detail: sum per noresi + no_pesanan + sku
                if (!isset($batch_detail_map[$detail_key])) {
                    $batch_detail_map[$detail_key] = [
                        'noresi'     => $noresi,
                        'no_pesanan' => $no_pesanan,
                        'sku'        => $sku,
                        'no_rak'     => $row['R'] ?? '',
                        'jumlah'     => (int)($row['Q'] ?? 0)
                    ];
                } else {
                    $batch_detail_map[$detail_key]['jumlah'] += (int)($row['Q'] ?? 0);
                }
            }

            // Insert Headers in batches
            $jumlah_header = count($batch_header_map);
            if ($jumlah_header > 0) {
                $header_chunks = array_chunk(array_values($batch_header_map), $batch_size);
                $sudah = 0;
                foreach ($header_chunks as $chunk) {
                    if ($this->db->insert_batch('tblprintresi', $chunk) === FALSE) {
                        throw new Exception('Gagal menyimpan header resi: ' . $this->pesan_error_db());
                    }
                    $sudah += count($chunk);
                    $lapor('simpan', 'Menyimpan resi baru ' . number_format($sudah, 0, ',', '.') . ' / ' . number_format($jumlah_header, 0, ',', '.') . '...', 40 + (int)(25 * $sudah / $jumlah_header));
                }
                $total_success_insert += $jumlah_header;
            }

            // Get inserted header IDs for detail insertion - handle large datasets properly
            $noresi_list = array_keys($batch_header_map);
            $id_resi_map = [];
            if (!empty($noresi_list)) {
                // Process in smaller chunks to avoid regex compilation errors
                $chunk_size = 1000; // Process 1000 noresi at a time
                $noresi_chunks = array_chunk($noresi_list, $chunk_size);

                foreach ($noresi_chunks as $chunk) {
                    $this->db->select('id_printresi, noresi');
                    $this->db->from('tblprintresi');
                    $this->db->where_in('noresi', $chunk);
                    $headers = $this->db->get()->result();

                    foreach ($headers as $h) {
                        $id_resi_map[$h->noresi] = $h->id_printresi;
                    }
                }

                // Backfill no_rak dari master tblsku untuk detail yang no_rak-nya
                // kosong. Sumber data spt Jubelio core-api tidak punya nomor rak
                // (itu data internal iresis) -> tanpa ini, tampilan packer
                // (get_receipt_for_packer baca dr.no_rak) jadi kosong.
                $rak_map = [];
                $skus_need_rak = [];
                foreach ($batch_detail_map as $detail) {
                    if (($detail['no_rak'] ?? '') === '' && !empty($detail['sku'])) {
                        $skus_need_rak[$detail['sku']] = true;
                    }
                }
                if (!empty($skus_need_rak)) {
                    foreach (array_chunk(array_keys($skus_need_rak), 1000) as $sku_chunk) {
                        $this->db->select('id_sku, no_rak');
                        $this->db->from('tblsku');
                        $this->db->where_in('id_sku', $sku_chunk);
                        foreach ($this->db->get()->result() as $srow) {
                            $rak_map[$srow->id_sku] = $srow->no_rak;
                        }
                    }
                }

                // Prepare Detail Rows
                $detail_rows = [];
                foreach ($batch_detail_map as $detail) {
                    $id_resi = $id_resi_map[$detail['noresi']] ?? null;
                    if (!$id_resi) continue;

                    $no_rak = $detail['no_rak'] ?? '';
                    if ($no_rak === '' && isset($rak_map[$detail['sku']])) {
                        $no_rak = $rak_map[$detail['sku']];
                    }

                    $detail_rows[] = [
                        'id_resi'    => $id_resi,
                        'no_pesanan' => $detail['no_pesanan'],
                        'sku'        => $detail['sku'],
                        'no_rak'     => $no_rak,
                        'jumlah'     => $detail['jumlah']
                    ];
                }

                // Insert Details in batches
                $jumlah_detail = count($detail_rows);
                if ($jumlah_detail > 0) {
                    $detail_chunks = array_chunk($detail_rows, $batch_size);
                    $sudah = 0;
                    foreach ($detail_chunks as $chunk) {
                        if ($this->db->insert_batch('tbldetailprintresi', $chunk) === FALSE) {
                            throw new Exception('Gagal menyimpan detail resi: ' . $this->pesan_error_db());
                        }
                        $sudah += count($chunk);
                        $lapor('simpan', 'Menyimpan detail SKU ' . number_format($sudah, 0, ',', '.') . ' / ' . number_format($jumlah_detail, 0, ',', '.') . '...', 65 + (int)(15 * $sudah / $jumlah_detail));
                    }
                }
            }

            // Update resi lama yang statusnya berubah. Dulu satu query UPDATE per
            // resi (ribuan round-trip saat file H-3 diunggah ulang); sekarang
            // update_batch = satu UPDATE ... CASE WHEN per 200 resi. Kolom yang
            // tidak ada di sebuah baris (mis. nomorpicklist) jatuh ke ELSE dan
            // tetap bernilai lama.
            $jumlah_update = count($batch_update_map);
            if ($jumlah_update > 0) {
                $sudah = 0;
                foreach (array_chunk(array_values($batch_update_map), 200) as $chunk) {
                    if ($this->db->update_batch('tblprintresi', $chunk, 'id_printresi') === FALSE) {
                        throw new Exception('Gagal memperbarui status resi: ' . $this->pesan_error_db());
                    }
                    $sudah += count($chunk);
                    $lapor('update', 'Memperbarui status resi ' . number_format($sudah, 0, ',', '.') . ' / ' . number_format($jumlah_update, 0, ',', '.') . '...', 80 + (int)(15 * $sudah / $jumlah_update));
                }
                $total_updated = $jumlah_update;
            }

            // Complete transaction
            $lapor('commit', 'Menyelesaikan transaksi database...', 96);
            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaksi database gagal: ' . $this->pesan_error_db());
            }

            $this->db->db_debug = $db_debug_awal;

            $message = "Total Data Terinput: $total_success_insert | Dilewati: $total_skip_insert | Duplikat: $total_duplicate_skip | Diupdate: $total_updated | Data Tidak Berubah: $total_skip_update_same_status";
            log_message('info', $message);

            return $message;

        } catch (Throwable $e) { // Throwable: TypeError dll. juga harus rollback, bukan cuma Exception
            $this->db->trans_rollback();
            $this->db->db_debug = $db_debug_awal;
            $error_message = "Error inserting receipt data: " . $e->getMessage();
            log_message('error', $error_message);
            return $error_message;
        }
    }

    /** Pesan error terakhir dari driver DB, untuk dilampirkan ke Exception. */
    private function pesan_error_db(): string
    {
        $err = $this->db->error();
        return !empty($err['message']) ? $err['message'] : 'tidak ada detail dari database';
    }

    private function get_existing_receipt_data_scan(array $receipt) {

        $noresi = $receipt['noresi'] ?? null;

        if (empty($noresi)) {
            return [];
        }

        $this->db->select('noresi, status_pesanan');
        $this->db->from('tblprintresi');
        $this->db->where('noresi', $noresi);

        $query = $this->db->get();

        $existing_data = [];
        foreach ($query->result() as $row) {
            if (strtolower($row->status_pesanan) === 'completed') {
                $existing_data[] = $row->noresi;
            }
        }

        return $existing_data;
    }

    function get_data_retur_receipt_report($data, $start_date, $end_date)
    {
        if (!empty($data) && $data['order'] != null) {
            $this->db->order_by($data['order'], $data['dir'], FALSE);
        }

        if (!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->select('
            t2.noresi
            , t3.nama_marketplace
            , t4.nama_kurir
            , t2.nomorpicklist
            , t2.tanggal_printresi
            , t.tanggal_resiretur
        ');

        $this->db->distinct();
        $this->db->join('tblprintresi t2' , 't2.id_printresi = t.id_resi', 'left');
        $this->db->join('tblmarketplace t3', 't3.id_marketplace = t.id_marketplace', 'left');
        $this->db->join('tblkurir t4', 't4.id_kurir = t.id_kurir', 'left');

        $this->db->where('t2.tanggal_printresi >=', $start_date);
        $this->db->where('t2.tanggal_printresi <=', $end_date);

        if (!empty($data['length'])) {
            $this->db->limit($data['length'], $data['start']);
        }

        return $this->db->get('tblresiretur t');
    }

    function get_total_data_retur_receipt_report($data, $start_date, $end_date)
    {
        if (!empty($data['search'])) {
            $x = 0;

            $this->db->group_start();

            foreach ($data['valid_columns'] as $sterm) {
                if (empty($sterm)) continue;

                if ($x == 0) {
                    $this->db->like($sterm, $data['search']);
                } else {
                    $this->db->or_like($sterm, $data['search']);
                }

                $x++;
            }

            $this->db->group_end();
        }

        $this->db->join('tblprintresi t2' , 't2.id_printresi = t.id_resi', 'left');
        $this->db->join('tblmarketplace t3', 't3.id_marketplace = t.id_marketplace', 'left');
        $this->db->join('tblkurir t4', 't4.id_kurir = t.id_kurir', 'left');

        $this->db->where('t2.tanggal_printresi >=', $start_date);
        $this->db->where('t2.tanggal_printresi <=', $end_date);

        $query = $this->db->select("COUNT(DISTINCT t.id_resi) as num")->get("tblresiretur t");
        $result = $query->row();

        return isset($result) ? $result->num : 0;
    }

    function get_data_production_team_tab0($data, $start_date, $end_date)
    {
        // Build search conditions for the subquery
        $search_where = '';
        if (!empty($data['search'])) {
            $search = $this->db->escape_like_str($data['search']);
            $search_where = " AND (t_usr.name LIKE '%{$search}%' OR DATE(b.tanggal_resiambilbarang) LIKE '%{$search}%')";
        }
        
        // OPTIMIZED V9: Group hanya per tanggal & picker, ambil status dari scan PERTAMA
        $sql = "
        SELECT 
            DATE(b.tanggal_resiambilbarang) as tanggal_resiambilbarang,
            b.yangambil_pegawai as id_picker,
            MIN(b.admin_pegawai) as admin_pegawai,
            MAX(COALESCE(t_usr.name, t_usr.username)) as pegawai,
            MAX(COALESCE(first_status.status_name, 'Tanpa Status')) as status_performa,
            COUNT(DISTINCT a.id_printresi) as total,
            MIN(b.tanggal_resiambilbarang) as waktu_scan_picker,
            MAX(b.tanggal_resiambilbarang) as waktu_scan_selesai,
            MAX(t_hak.akses) as role
        FROM tblprintresi a
        INNER JOIN tblresiambilbarang b ON a.id_printresi = b.id_resi
        LEFT JOIN tbluser t_usr ON t_usr.id_user = b.yangambil_pegawai
        LEFT JOIN tblhakakses t_hak ON t_hak.id_hakakses = t_usr.hakakses
        -- JOIN ke subquery untuk ambil status PERTAMA per picker per hari
        LEFT JOIN (
            SELECT 
                b3.yangambil_pegawai,
                DATE(b3.tanggal_resiambilbarang) as tanggal_date,
                sp3.status_name
            FROM tblresiambilbarang b3
            LEFT JOIN tblmasterstatusperforma sp3 ON sp3.id_statusperforma = b3.status_performa_id
            INNER JOIN (
                SELECT 
                    yangambil_pegawai,
                    DATE(tanggal_resiambilbarang) as tanggal_date,
                    MIN(tanggal_resiambilbarang) as first_scan_time
                FROM tblresiambilbarang
                WHERE tanggal_resiambilbarang >= " . $this->db->escape($start_date) . "
                AND tanggal_resiambilbarang <= " . $this->db->escape($end_date) . "
                GROUP BY yangambil_pegawai, DATE(tanggal_resiambilbarang)
            ) first_scan ON b3.yangambil_pegawai = first_scan.yangambil_pegawai
                AND DATE(b3.tanggal_resiambilbarang) = first_scan.tanggal_date
                AND b3.tanggal_resiambilbarang = first_scan.first_scan_time
        ) first_status ON first_status.yangambil_pegawai = b.yangambil_pegawai
            AND first_status.tanggal_date = DATE(b.tanggal_resiambilbarang)
        WHERE b.tanggal_resiambilbarang >= " . $this->db->escape($start_date) . "
        AND b.tanggal_resiambilbarang <= " . $this->db->escape($end_date) . "
        {$search_where}
        GROUP BY DATE(b.tanggal_resiambilbarang), b.yangambil_pegawai
        ";
        
        // Add ORDER BY
        if (!empty($data) && !empty($data['order'])) {
            $order_col = ['pegawai', 'tanggal_resiambilbarang', 'total'];
            $order_clauses = [];
            foreach ($data['order'] as $order) {
                if (isset($order_col[$order['column']])) {
                    $order_clauses[] = $order_col[$order['column']] . ' ' . strtoupper($order['dir']);
                }
            }
            if (!empty($order_clauses)) {
                $sql .= " ORDER BY " . implode(', ', $order_clauses);
            }
        } else {
            $sql .= " ORDER BY pegawai ASC, tanggal_resiambilbarang ASC, status_performa ASC";
        }
        
        // Add LIMIT/OFFSET
        if (!empty($data['length'])) {
            $sql .= " LIMIT " . intval($data['length']) . " OFFSET " . intval($data['start']);
        }

        return $this->db->query($sql);
    }

    function get_total_data_production_team_tab0($data, $start_date, $end_date)
    {
        // Build search conditions
        $search_where = '';
        if (!empty($data['search'])) {
            $search = $this->db->escape_like_str($data['search']);
            $search_where = " AND (t_usr.name LIKE '%{$search}%' OR DATE(b.tanggal_resiambilbarang) LIKE '%{$search}%')";
        }
        
        // OPTIMIZED V9: Count rows dengan grouping baru (hanya per tanggal & picker)
        $sql = "
        SELECT COUNT(*) as total
        FROM (
            SELECT 
                DATE(b.tanggal_resiambilbarang) as tanggal_resiambilbarang,
                b.yangambil_pegawai
            FROM tblprintresi a
            INNER JOIN tblresiambilbarang b ON a.id_printresi = b.id_resi
            LEFT JOIN tbluser t_usr ON t_usr.id_user = b.yangambil_pegawai
            WHERE b.tanggal_resiambilbarang >= " . $this->db->escape($start_date) . "
            AND b.tanggal_resiambilbarang <= " . $this->db->escape($end_date) . "
            {$search_where}
            GROUP BY DATE(b.tanggal_resiambilbarang), b.yangambil_pegawai
        ) as grouped_data";
        
        $result = $this->db->query($sql)->row();
        return $result ? $result->total : 0;
    }

    function get_data_production_team_tab1($data, $start_date, $end_date)
    {
        // Build search conditions for the subquery
        $search_where = '';
        if (!empty($data['search'])) {
            $search = $this->db->escape_like_str($data['search']);
            $search_where = " AND (t3.name LIKE '%{$search}%' OR DATE(c.tanggal_packing) LIKE '%{$search}%')";
        }
        
        // OPTIMIZED V9: Group hanya per tanggal & packer, ambil status dari scan PERTAMA
        $sql = "
        SELECT 
            DATE(c.tanggal_packing) as tanggal_packing,
            c.packer_pegawai,
            MAX(t3.name) as pegawai,
            MAX(COALESCE(first_status.status_name, 'Tanpa Status')) as status_performa,
            COUNT(DISTINCT a.id_printresi) as total,
            MIN(c.tanggal_packing) as waktu_scan_packer,
            MAX(c.tanggal_packing) as waktu_scan_selesai,
            MAX(t_hak.akses) as role
        FROM tblprintresi a
        INNER JOIN tblpacking c ON a.id_printresi = c.id_resi
        LEFT JOIN tbluser t3 ON t3.id_user = c.packer_pegawai
        LEFT JOIN tblhakakses t_hak ON t_hak.id_hakakses = t3.hakakses
        -- JOIN ke subquery untuk ambil status PERTAMA per packer per hari
        LEFT JOIN (
            SELECT 
                c3.packer_pegawai,
                DATE(c3.tanggal_packing) as tanggal_date,
                sp3.status_name
            FROM tblpacking c3
            LEFT JOIN tblmasterstatusperforma sp3 ON sp3.id_statusperforma = c3.status_performa_id
            INNER JOIN (
                SELECT 
                    packer_pegawai,
                    DATE(tanggal_packing) as tanggal_date,
                    MIN(tanggal_packing) as first_scan_time
                FROM tblpacking
                WHERE tanggal_packing >= " . $this->db->escape($start_date) . "
                AND tanggal_packing <= " . $this->db->escape($end_date) . "
                GROUP BY packer_pegawai, DATE(tanggal_packing)
            ) first_scan ON c3.packer_pegawai = first_scan.packer_pegawai
                AND DATE(c3.tanggal_packing) = first_scan.tanggal_date
                AND c3.tanggal_packing = first_scan.first_scan_time
        ) first_status ON first_status.packer_pegawai = c.packer_pegawai
            AND first_status.tanggal_date = DATE(c.tanggal_packing)
        WHERE c.tanggal_packing >= " . $this->db->escape($start_date) . "
        AND c.tanggal_packing <= " . $this->db->escape($end_date) . "
        {$search_where}
        GROUP BY DATE(c.tanggal_packing), c.packer_pegawai
        ";
        
        // Add ORDER BY
        if (!empty($data) && !empty($data['order'])) {
            $order_col = ['pegawai', 'tanggal_packing', 'total'];
            $order_clauses = [];
            foreach ($data['order'] as $order) {
                if (isset($order_col[$order['column']])) {
                    $order_clauses[] = $order_col[$order['column']] . ' ' . strtoupper($order['dir']);
                }
            }
            if (!empty($order_clauses)) {
                $sql .= " ORDER BY " . implode(', ', $order_clauses);
            }
        } else {
            $sql .= " ORDER BY pegawai ASC, tanggal_packing ASC";
        }
        
        // Add LIMIT/OFFSET
        if (!empty($data['length'])) {
            $sql .= " LIMIT " . intval($data['length']) . " OFFSET " . intval($data['start']);
        }

        return $this->db->query($sql);
    }

    function get_total_data_production_team_tab1($data, $start_date, $end_date)
    {
        // Build search conditions
        $search_where = '';
        if (!empty($data['search'])) {
            $search = $this->db->escape_like_str($data['search']);
            $search_where = " AND (t3.name LIKE '%{$search}%' OR DATE(c.tanggal_packing) LIKE '%{$search}%')";
        }
        
        // OPTIMIZED V9: Count rows dengan grouping baru (hanya per tanggal & packer)
        $sql = "
        SELECT COUNT(*) as total
        FROM (
            SELECT 
                DATE(c.tanggal_packing) as tanggal_packing,
                c.packer_pegawai
            FROM tblprintresi a
            INNER JOIN tblpacking c ON a.id_printresi = c.id_resi
            LEFT JOIN tbluser t3 ON t3.id_user = c.packer_pegawai
            WHERE c.tanggal_packing >= " . $this->db->escape($start_date) . "
            AND c.tanggal_packing <= " . $this->db->escape($end_date) . "
            {$search_where}
            GROUP BY DATE(c.tanggal_packing), c.packer_pegawai
        ) as grouped_data";
        
        $result = $this->db->query($sql)->row();
        return $result ? $result->total : 0;
    }

    function get_data_production_team_tab2($data, $start_date, $end_date)
    {
        // Build search conditions for the subquery
        $search_where = '';
        if (!empty($data['search'])) {
            $search = $this->db->escape_like_str($data['search']);
            $search_where = " AND (t4.nama_pegawai LIKE '%{$search}%' OR DATE(d.tanggal_resikeluar) LIKE '%{$search}%')";
        }
        
        $sql = "
        SELECT 
            DATE(d.tanggal_resikeluar) as tanggal_resikeluar,
            d.id_pegawai,
            MAX(t4.nama_pegawai) as pegawai,
            'Tanpa Status' as status_performa,
            COUNT(DISTINCT a.id_printresi) as total,
            MIN(d.tanggal_resikeluar) as waktu_scan_ho,
            MAX(t_hak.akses) as role
        FROM tblprintresi a
        INNER JOIN tblresikeluar d ON a.id_printresi = d.id_resi
        LEFT JOIN tblpegawai t4 ON t4.kode_pegawai = d.id_pegawai
        LEFT JOIN tbluser t_usr ON t_usr.id_pegawai = t4.kode_pegawai
        LEFT JOIN tblhakakses t_hak ON t_hak.id_hakakses = t_usr.hakakses
        WHERE d.tanggal_resikeluar >= " . $this->db->escape($start_date) . "
        AND d.tanggal_resikeluar <= " . $this->db->escape($end_date) . "
        {$search_where}
        GROUP BY DATE(d.tanggal_resikeluar), d.id_pegawai
        ";
        
        // Add ORDER BY
        if (!empty($data) && !empty($data['order'])) {
            $order_col = ['pegawai', 'tanggal_resikeluar', 'total'];
            $order_clauses = [];
            foreach ($data['order'] as $order) {
                if (isset($order_col[$order['column']])) {
                    $order_clauses[] = $order_col[$order['column']] . ' ' . strtoupper($order['dir']);
                }
            }
            if (!empty($order_clauses)) {
                $sql .= " ORDER BY " . implode(', ', $order_clauses);
            }
        } else {
            $sql .= " ORDER BY DATE(d.tanggal_resikeluar) DESC, total DESC";
        }
        
        // Add LIMIT
        if (!empty($data) && isset($data['start']) && isset($data['length']) && $data['length'] != -1) {
            $sql .= " LIMIT " . intval($data['length']) . " OFFSET " . intval($data['start']);
        }

        return $this->db->query($sql);
    }

    function get_total_data_production_team_tab2($data, $start_date, $end_date)
    {
        $search_where = '';
        if (!empty($data['search'])) {
            $search = $this->db->escape_like_str($data['search']);
            $search_where = " AND (t4.nama_pegawai LIKE '%{$search}%' OR DATE(d.tanggal_resikeluar) LIKE '%{$search}%')";
        }
        
        $sql = "
        SELECT COUNT(*) as total
        FROM (
            SELECT 
                DATE(d.tanggal_resikeluar) as tanggal_resikeluar,
                d.id_pegawai
            FROM tblprintresi a
            INNER JOIN tblresikeluar d ON a.id_printresi = d.id_resi
            LEFT JOIN tblpegawai t4 ON t4.kode_pegawai = d.id_pegawai
            WHERE d.tanggal_resikeluar >= " . $this->db->escape($start_date) . "
            AND d.tanggal_resikeluar <= " . $this->db->escape($end_date) . "
            {$search_where}
            GROUP BY DATE(d.tanggal_resikeluar), d.id_pegawai
        ) as grouped_data";
        
        $result = $this->db->query($sql)->row();
        return $result ? $result->total : 0;
    }

    // KPI Methods
    function get_total_receipts_processed($start_date, $end_date)
    {
        $this->db->where('tanggal_printresi >=', $start_date);
        $this->db->where('tanggal_printresi <=', $end_date);
        return $this->db->count_all_results('tblprintresi');
    }

    function get_total_shipped_receipts($start_date, $end_date)
    {
        $this->db->join('tblresikeluar t2', 't.id_printresi = t2.id_printresi', 'inner');
        $this->db->where('t.tanggal_printresi >=', $start_date);
        $this->db->where('t.tanggal_printresi <=', $end_date);
        return $this->db->count_all_results('tblprintresi t');
    }

    function get_total_pending_receipts($start_date, $end_date)
    {
        $this->db->join('tblresikeluar t2', 't.id_printresi = t2.id_printresi', 'left');
        $this->db->where('t.tanggal_printresi >=', $start_date);
        $this->db->where('t.tanggal_printresi <=', $end_date);
        $this->db->where('t2.id_printresi IS NULL');
        return $this->db->count_all_results('tblprintresi t');
    }

    function get_total_retur_receipts($start_date, $end_date)
    {
        $this->db->join('tblresiretur t2', 't.id_printresi = t2.id_printresi', 'inner');
        $this->db->where('t.tanggal_printresi >=', $start_date);
        $this->db->where('t.tanggal_printresi <=', $end_date);
        return $this->db->count_all_results('tblprintresi t');
    }

    function get_avg_processing_time($start_date, $end_date)
    {
        $this->db->select('AVG(TIMESTAMPDIFF(HOUR, t.tanggal_printresi, t2.tanggal_resikeluar)) as avg_time');
        $this->db->join('tblresikeluar t2', 't.id_printresi = t2.id_printresi', 'inner');
        $this->db->where('t.tanggal_printresi >=', $start_date);
        $this->db->where('t.tanggal_printresi <=', $end_date);
        $this->db->where('t2.tanggal_resikeluar IS NOT NULL');
        
        $query = $this->db->get('tblprintresi t');
        $result = $query->row();
        
        return $result ? round($result->avg_time, 2) : 0;
    }

    function get_picker_productivity($start_date, $end_date)
    {
        $this->db->select('COUNT(t.id_printresi) / COUNT(DISTINCT DATE(t.tanggal_resiambilbarang)) as productivity');
        $this->db->join('tblresiambilbarang t2', 't.id_printresi = t2.id_printresi', 'inner');
        $this->db->where('t.tanggal_printresi >=', $start_date);
        $this->db->where('t.tanggal_printresi <=', $end_date);
        $this->db->where('t2.tanggal_resiambilbarang IS NOT NULL');
        
        $query = $this->db->get('tblprintresi t');
        $result = $query->row();
        
        return $result ? round($result->productivity, 2) : 0;
    }

    function get_packer_productivity($start_date, $end_date)
    {
        $this->db->select('COUNT(t.id_printresi) / COUNT(DISTINCT DATE(t2.tanggal_packing)) as productivity');
        $this->db->join('tblpacking t2', 't.id_printresi = t2.id_printresi', 'inner');
        $this->db->where('t.tanggal_printresi >=', $start_date);
        $this->db->where('t.tanggal_printresi <=', $end_date);
        $this->db->where('t2.tanggal_packing IS NOT NULL');
        
        $query = $this->db->get('tblprintresi t');
        $result = $query->row();
        
        return $result ? round($result->productivity, 2) : 0;
    }

    function get_daily_performance($start_date, $end_date)
    {
        $this->db->select('
            DATE(t.tanggal_printresi) as date,
            COUNT(t.id_printresi) as receipts,
            COUNT(t2.id_printresi) as completed,
            ROUND((COUNT(t2.id_printresi) / COUNT(t.id_printresi)) * 100, 2) as completion_rate
        ');
        $this->db->join('tblresikeluar t2', 't.id_printresi = t2.id_printresi', 'left');
        $this->db->where('t.tanggal_printresi >=', $start_date);
        $this->db->where('t.tanggal_printresi <=', $end_date);
        $this->db->group_by('DATE(t.tanggal_printresi)');
        $this->db->order_by('DATE(t.tanggal_printresi)', 'ASC');
        
        $query = $this->db->get('tblprintresi t');
        $results = $query->result_array();
        
        $data = array(
            'labels' => array(),
            'values' => array()
        );
        
        foreach ($results as $row) {
            $data['labels'][] = $row['date'];
            $data['values'][] = $row['receipts'];
        }
        
        return $data;
    }


    function get_picker_performance_by_user($start_date, $end_date)
    {
        $this->db->select('
            COALESCE(peg.nama_pegawai, "Unknown User") as nama_pegawai,
            COALESCE(peg.kode_pegawai, "N/A") as kode_pegawai,
            COALESCE(sp.status_name, "Normal") as status_name,
            COALESCE(sp.kode_status, "NORMAL") as kode_status,
            COALESCE(COUNT(rab.id_resiambilbarang), 0) as total_scan,
            COUNT(DISTINCT DATE(rab.tanggal_resiambilbarang)) as hari_aktif,
            ROUND(COALESCE(COUNT(rab.id_resiambilbarang), 0) / NULLIF(COUNT(DISTINCT DATE(rab.tanggal_resiambilbarang)), 0), 2) as rata_rata_harian
        ');
        
        $this->db->from('tblresiambilbarang rab');
        $this->db->join('tblpegawai peg', 'peg.kode_pegawai = rab.yangambil_pegawai', 'left');
        $this->db->join('tblkpi k', 'k.id_user = rab.admin_pegawai AND DATE(k.tanggal) = DATE(rab.tanggal_resiambilbarang) AND k.tipe_transaksi = "PICKER"', 'left');
        $this->db->join('tblmasterstatusperforma sp', 'sp.id_statusperforma = k.id_statusperforma', 'left');
        $this->db->where('rab.tanggal_resiambilbarang >=', $start_date);
        $this->db->where('rab.tanggal_resiambilbarang <=', $end_date);
        $this->db->where('peg.kode_pegawai IS NOT NULL');
        $this->db->group_by('peg.kode_pegawai, peg.nama_pegawai, sp.kode_status, sp.status_name');
        $this->db->order_by('total_scan DESC');
        
        return $this->db->get();
    }

    function get_today_courier_totals()
    {
        $start_date = date('Y-m-d 00:00:00');
        $end_date = date('Y-m-d 23:59:59');

        $this->db->select('
            coalesce(t3.nama_kurir, \'- Tidak diketahui -\') nama_kurir
            , count(1) total
        ');
        $this->db->join('tblprintresi t2', 't2.id_printresi = t.id_resi');
        $this->db->join('tblkurir t3', 't3.id_kurir = t2.id_kurir', 'left');
        $this->db->where('t.tanggal_resikeluar >=', $start_date);
        $this->db->where('t.tanggal_resikeluar <=', $end_date);
        $this->db->group_by('t3.nama_kurir');
        $this->db->order_by('total', 'DESC');
        return $this->db->get('tblresikeluar t')->result_array();
    }

    function get_upcoming_deadlines_breakdown($days = 5)
    {
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+$days days"));

        $this->db->select('DATE(tanggal_bataskirim) as date, count(1) as total');
        $this->db->from('tblprintresi');
        $this->db->where('DATE(tanggal_bataskirim) >=', $start_date);
        $this->db->where('DATE(tanggal_bataskirim) <=', $end_date);
        $this->db->where("id_printresi NOT IN (SELECT id_resi FROM tblresikeluar)", NULL, FALSE);
        $this->db->group_by('DATE(tanggal_bataskirim)');
        $query_results = $this->db->get()->result_array();

        // Fill in missing dates with 0
        $results = [];
        $indexed_results = [];
        foreach($query_results as $row) {
            $indexed_results[$row['date']] = (int)$row['total'];
        }

        for ($i = 0; $i <= $days; $i++) {
            $date = date('Y-m-d', strtotime("+$i days"));
            $results[] = [
                'date' => $date,
                'total' => $indexed_results[$date] ?? 0
            ];
        }
        return $results;
    }

    function get_today_production_target()
    {
        $today = date('Y-m-d');
        $this->db->select('
            COUNT(pr.id_printresi) as total,
            COUNT(rab.id_resiambilbarang) as picked,
            COUNT(p.id_packing) as packed,
            COUNT(rk.id_resikeluar) as ho
        ');
        $this->db->from('tblprintresi pr');
        $this->db->join('tblresiambilbarang rab', 'pr.id_printresi = rab.id_resi', 'left');
        $this->db->join('tblpacking p', 'pr.id_printresi = p.id_resi', 'left');
        $this->db->join('tblresikeluar rk', 'pr.id_printresi = rk.id_resi', 'left');
        $this->db->where('DATE(pr.tanggal_bataskirim)', $today);
        
        return $this->db->get()->row_array();
    }

    // ==================== SKU SPECIAL REPORT METHODS ====================

    /**
     * Get summary (per-SKU total qty) for the SKU special report header cards
     */
    function get_sku_special_report_summary($start_date, $end_date)
    {
        $sql = "
            SELECT
                dr.sku,
                COALESCE(s.nama_sku, dr.sku) AS nama_sku,
                SUM(dr.jumlah) AS total_qty,
                COUNT(DISTINCT pr.id_printresi) AS total_resi
            FROM tblprintresi pr
            INNER JOIN tbldetailprintresi dr ON dr.id_resi = pr.id_printresi
            INNER JOIN tblsku s ON s.id_sku = dr.sku AND s.is_special = 1
            WHERE pr.created_at >= " . $this->db->escape($start_date) . "
              AND pr.created_at <= " . $this->db->escape($end_date) . "
            GROUP BY dr.sku, s.nama_sku
            ORDER BY total_qty DESC
        ";
        return $this->db->query($sql)->result();
    }

    /**
     * Get paginated detail rows for the SKU special DataTable
     */
    function get_data_sku_special_report($data, $start_date, $end_date)
    {
        $search_where = '';
        if (!empty($data['search'])) {
            $s = $this->db->escape_like_str($data['search']);
            $search_where = " AND (pr.noresi LIKE '%{$s}%' OR dr.sku LIKE '%{$s}%'
                OR m.nama_marketplace LIKE '%{$s}%' OR k.nama_kurir LIKE '%{$s}%'
                OR pr.status_pesanan LIKE '%{$s}%')";
        }

        $order_by = 'pr.created_at DESC';
        if (!empty($data['order']) && !empty($data['dir'])) {
            $allowed = ['pr.created_at', 'pr.noresi', 'dr.sku', 'dr.jumlah',
                        'm.nama_marketplace', 'k.nama_kurir', 'pr.status_pesanan'];
            if (in_array($data['order'], $allowed)) {
                $order_by = $this->db->escape_str($data['order']) . ' ' . ($data['dir'] === 'asc' ? 'ASC' : 'DESC');
            }
        }

        $limit_clause = '';
        if (!empty($data['length'])) {
            $limit_clause = 'LIMIT ' . intval($data['length']) . ' OFFSET ' . intval($data['start']);
        }

        $sql = "
            SELECT
                pr.created_at,
                pr.noresi,
                dr.sku,
                dr.jumlah,
                m.nama_marketplace,
                k.nama_kurir,
                pr.status_pesanan,
                agg.unique_skus,
                agg.total_qty_resi,
                CASE
                    WHEN agg.unique_skus = 1 AND agg.total_qty_resi = 1 THEN 'Resi Special'
                    WHEN agg.unique_skus = 1 AND agg.total_qty_resi BETWEEN 2 AND 9 THEN '1 SKU & Qty ≤9'
                    WHEN agg.unique_skus BETWEEN 2 AND 9 AND agg.total_qty_resi <= 9 THEN '2-9 SKU & Qty ≤9'
                    ELSE 'Qty Banyak'
                END AS kategori_resi
            FROM tblprintresi pr
            INNER JOIN tbldetailprintresi dr ON dr.id_resi = pr.id_printresi
            INNER JOIN tblsku s ON s.id_sku = dr.sku AND s.is_special = 1
            LEFT JOIN tblmarketplace m ON m.id_marketplace = pr.id_marketplace
            LEFT JOIN tblkurir k ON k.id_kurir = pr.id_kurir
            LEFT JOIN (
                SELECT id_resi,
                       COUNT(DISTINCT sku) AS unique_skus,
                       SUM(jumlah) AS total_qty_resi
                FROM tbldetailprintresi
                GROUP BY id_resi
            ) agg ON agg.id_resi = pr.id_printresi
            WHERE pr.created_at >= " . $this->db->escape($start_date) . "
              AND pr.created_at <= " . $this->db->escape($end_date) . "
            {$search_where}
            ORDER BY {$order_by}
            {$limit_clause}
        ";
        return $this->db->query($sql);
    }

    /**
     * Get total count for the SKU special DataTable
     */
    function get_total_data_sku_special_report($data, $start_date, $end_date)
    {
        $search_where = '';
        if (!empty($data['search'])) {
            $s = $this->db->escape_like_str($data['search']);
            $search_where = " AND (pr.noresi LIKE '%{$s}%' OR dr.sku LIKE '%{$s}%'
                OR m.nama_marketplace LIKE '%{$s}%' OR k.nama_kurir LIKE '%{$s}%'
                OR pr.status_pesanan LIKE '%{$s}%')";
        }

        $sql = "
            SELECT COUNT(*) AS num
            FROM tblprintresi pr
            INNER JOIN tbldetailprintresi dr ON dr.id_resi = pr.id_printresi
            INNER JOIN tblsku s ON s.id_sku = dr.sku AND s.is_special = 1
            LEFT JOIN tblmarketplace m ON m.id_marketplace = pr.id_marketplace
            LEFT JOIN tblkurir k ON k.id_kurir = pr.id_kurir
            WHERE pr.created_at >= " . $this->db->escape($start_date) . "
              AND pr.created_at <= " . $this->db->escape($end_date) . "
            {$search_where}
        ";
        $row = $this->db->query($sql)->row();
        return $row ? (int)$row->num : 0;
    }

    /**
     * Get enhanced shipping report with SKU-category breakdown per courier
     *
     * Resi 1 SKU 1 Qty dipecah menurut jalur spesial saat diproses:
     *  - total_1sku1qty_spesial : di-pick dengan status 1_SKU_PICKER
     *                             (tblresiambilbarang) ATAU di-packing dengan
     *                             status 1_SKU_PACKER lewat sinkronisasi dari
     *                             picker (tblpacking.keterangan = SYNC_FROM_PICKER)
     *  - total_1sku1qty_reguler : sisanya (status lain, kosong, belum ada data)
     * Dua sisi dipakai karena mode picker kadang jatuh ke NORMAL_PICKER di
     * tengah hari (13 Sep 2026: 404 resi jalur 1 SKU tercatat NORMAL_PICKER)
     * padahal packer-nya tetap 1_SKU_PACKER. Sisi packer hanya dipercaya untuk
     * resi hasil sinkron, bukan scan manual: packer 1 SKU kadang men-scan resi
     * jalur biasa saat modenya masih 1_SKU_PACKER (14 Sep 2026: 66 resi).
     * Resi jalur spesial yang isinya bukan 1 SKU 1 Qty tetap masuk kategori
     * qty/SKU-nya sendiri, jadi jumlah semua kategori tetap = total.
     * Dihitung COUNT(DISTINCT) karena tblpacking.id_resi tidak unik.
     */
    function get_shipping_report_detail($start_date, $end_date)
    {
        $sql = "
            SELECT
                COALESCE(k.nama_kurir, '- Tidak diketahui -') AS nama_kurir,
                COUNT(DISTINCT rk.id_resi) AS total,
                COUNT(DISTINCT CASE WHEN agg.unique_skus = 1 AND agg.total_qty = 1
                                     AND (spp.kode_status = '1_SKU_PICKER'
                                          OR (spk.kode_status = '1_SKU_PACKER' AND p.keterangan = 'SYNC_FROM_PICKER'))
                                    THEN rk.id_resi END) AS total_1sku1qty_spesial,
                COUNT(DISTINCT CASE WHEN agg.unique_skus = 1 AND agg.total_qty = 1
                                     AND COALESCE(spp.kode_status, '') <> '1_SKU_PICKER'
                                     AND NOT (COALESCE(spk.kode_status, '') = '1_SKU_PACKER' AND COALESCE(p.keterangan, '') = 'SYNC_FROM_PICKER')
                                    THEN rk.id_resi END) AS total_1sku1qty_reguler,
                COUNT(DISTINCT CASE WHEN agg.unique_skus = 1 AND agg.total_qty BETWEEN 2 AND 9 THEN rk.id_resi END) AS total_1sku,
                COUNT(DISTINCT CASE WHEN agg.unique_skus BETWEEN 2 AND 9 AND agg.total_qty <= 9 THEN rk.id_resi END) AS total_2_9sku,
                COUNT(DISTINCT CASE WHEN agg.total_qty > 9 THEN rk.id_resi END) AS total_qty_banyak
            FROM tblresikeluar rk
            INNER JOIN tblprintresi pr ON pr.id_printresi = rk.id_resi
            LEFT JOIN tblkurir k ON k.id_kurir = pr.id_kurir
            LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
            LEFT JOIN tblmasterstatusperforma spp ON spp.id_statusperforma = rab.status_performa_id
            LEFT JOIN tblpacking p ON p.id_resi = pr.id_printresi
            LEFT JOIN tblmasterstatusperforma spk ON spk.id_statusperforma = p.status_performa_id
            LEFT JOIN (
                SELECT dr.id_resi,
                       COUNT(DISTINCT dr.sku) AS unique_skus,
                       SUM(dr.jumlah) AS total_qty
                FROM tbldetailprintresi dr
                GROUP BY dr.id_resi
            ) agg ON agg.id_resi = pr.id_printresi
            WHERE rk.tanggal_resikeluar >= " . $this->db->escape($start_date) . "
              AND rk.tanggal_resikeluar <= " . $this->db->escape($end_date) . "
            GROUP BY k.nama_kurir
            ORDER BY total DESC
        ";
        return $this->db->query($sql)->result_array();
    }

    /**
     * Get overall category totals for the shipping report summary cards
     *
     * Pemecahan 1 SKU 1 Qty Spesial/Reguler sama dengan get_shipping_report_detail().
     */
    function get_shipping_report_category_totals($start_date, $end_date)
    {
        $sql = "
            SELECT
                COUNT(DISTINCT rk.id_resi) AS grand_total,
                COUNT(DISTINCT CASE WHEN agg.unique_skus = 1 AND agg.total_qty = 1
                                     AND (spp.kode_status = '1_SKU_PICKER'
                                          OR (spk.kode_status = '1_SKU_PACKER' AND p.keterangan = 'SYNC_FROM_PICKER'))
                                    THEN rk.id_resi END) AS total_1sku1qty_spesial,
                COUNT(DISTINCT CASE WHEN agg.unique_skus = 1 AND agg.total_qty = 1
                                     AND COALESCE(spp.kode_status, '') <> '1_SKU_PICKER'
                                     AND NOT (COALESCE(spk.kode_status, '') = '1_SKU_PACKER' AND COALESCE(p.keterangan, '') = 'SYNC_FROM_PICKER')
                                    THEN rk.id_resi END) AS total_1sku1qty_reguler,
                COUNT(DISTINCT CASE WHEN agg.unique_skus = 1 AND agg.total_qty BETWEEN 2 AND 9 THEN rk.id_resi END) AS total_1sku,
                COUNT(DISTINCT CASE WHEN agg.unique_skus BETWEEN 2 AND 9 AND agg.total_qty <= 9 THEN rk.id_resi END) AS total_2_9sku,
                COUNT(DISTINCT CASE WHEN agg.total_qty > 9 THEN rk.id_resi END) AS total_qty_banyak
            FROM tblresikeluar rk
            INNER JOIN tblprintresi pr ON pr.id_printresi = rk.id_resi
            LEFT JOIN tblresiambilbarang rab ON rab.id_resi = pr.id_printresi
            LEFT JOIN tblmasterstatusperforma spp ON spp.id_statusperforma = rab.status_performa_id
            LEFT JOIN tblpacking p ON p.id_resi = pr.id_printresi
            LEFT JOIN tblmasterstatusperforma spk ON spk.id_statusperforma = p.status_performa_id
            LEFT JOIN (
                SELECT dr.id_resi,
                       COUNT(DISTINCT dr.sku) AS unique_skus,
                       SUM(dr.jumlah) AS total_qty
                FROM tbldetailprintresi dr
                GROUP BY id_resi
            ) agg ON agg.id_resi = pr.id_printresi
            WHERE rk.tanggal_resikeluar >= " . $this->db->escape($start_date) . "
              AND rk.tanggal_resikeluar <= " . $this->db->escape($end_date) . "
        ";
        $row = $this->db->query($sql)->row_array();
        return $row ?: [];
    }

    /**
     * Get overall category totals of unique resi containing special SKU
     */
    function get_sku_special_report_category_totals($start_date, $end_date)
    {
        $sql = "
            SELECT
                COUNT(DISTINCT pr.id_printresi) AS total_resi,
                COUNT(DISTINCT CASE WHEN agg.unique_skus = 1 AND agg.total_qty_resi = 1 THEN pr.id_printresi END) AS total_special,
                COUNT(DISTINCT CASE WHEN agg.unique_skus = 1 AND agg.total_qty_resi BETWEEN 2 AND 9 THEN pr.id_printresi END) AS total_1sku,
                COUNT(DISTINCT CASE WHEN agg.unique_skus BETWEEN 2 AND 9 AND agg.total_qty_resi <= 9 THEN pr.id_printresi END) AS total_2_9sku,
                COUNT(DISTINCT CASE WHEN agg.total_qty_resi > 9 THEN pr.id_printresi END) AS total_qty_banyak
            FROM tblprintresi pr
            INNER JOIN tbldetailprintresi dr ON dr.id_resi = pr.id_printresi
            INNER JOIN tblsku s ON s.id_sku = dr.sku AND s.is_special = 1
            LEFT JOIN (
                SELECT id_resi,
                       COUNT(DISTINCT sku) AS unique_skus,
                       SUM(jumlah) AS total_qty_resi
                FROM tbldetailprintresi
                GROUP BY id_resi
            ) agg ON agg.id_resi = pr.id_printresi
            WHERE pr.created_at >= " . $this->db->escape($start_date) . "
              AND pr.created_at <= " . $this->db->escape($end_date) . "
        ";
        return $this->db->query($sql)->row_array();
    }

    public function get_picker_performance_detail_summary($id_picker, $tanggal)
    {
        $sql = "
            SELECT 
                SUM(CASE WHEN has_special = 1 AND distinct_skus = 1 AND total_qty <= 10 THEN 1 ELSE 0 END) as sku_special,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty > 9 THEN 1 ELSE 0 END) as resi_qty_banyak,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus = 1 OR distinct_skus IS NULL) THEN 1 ELSE 0 END) as resi_1_sku_sd_9,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus BETWEEN 2 AND 9) THEN 1 ELSE 0 END) as resi_2_9_sku_sd_9,
                COUNT(*) as total_resi
            FROM (
                SELECT 
                    pr.id_printresi,
                    m.distinct_skus,
                    m.total_qty,
                    COALESCE(m.has_special, 0) as has_special
                FROM tblresiambilbarang rab
                INNER JOIN tblprintresi pr ON pr.id_printresi = rab.id_resi
                LEFT JOIN (
                    SELECT 
                        dt.id_resi,
                        COUNT(DISTINCT dt.sku) as distinct_skus,
                        SUM(dt.jumlah) as total_qty,
                        MAX(CASE WHEN s.is_special = 1 THEN 1 ELSE 0 END) as has_special
                    FROM tbldetailprintresi dt
                    JOIN tblsku s ON s.id_sku = dt.sku
                    GROUP BY dt.id_resi
                ) m ON m.id_resi = pr.id_printresi
                WHERE rab.yangambil_pegawai = " . $this->db->escape($id_picker) . "
                  AND DATE(rab.tanggal_resiambilbarang) = " . $this->db->escape($tanggal) . "
            ) as t
        ";
        return $this->db->query($sql)->row_array();
    }

    public function get_picker_performance_detail_list($id_picker, $tanggal)
    {
        $sql = "
            SELECT 
                pr.id_printresi,
                pr.noresi,
                pr.tanggal_bataskirim,
                pr.status_pesanan,
                rab.tanggal_resiambilbarang as waktu_scan,
                m.distinct_skus,
                m.total_qty,
                COALESCE(m.has_special, 0) as has_special,
                m.detail_barang
            FROM tblresiambilbarang rab
            INNER JOIN tblprintresi pr ON pr.id_printresi = rab.id_resi
            LEFT JOIN (
                SELECT 
                    dt.id_resi,
                    COUNT(DISTINCT dt.sku) as distinct_skus,
                    SUM(dt.jumlah) as total_qty,
                    MAX(CASE WHEN s.is_special = 1 THEN 1 ELSE 0 END) as has_special,
                    GROUP_CONCAT(CONCAT(s.nama_sku, ' (x', dt.jumlah, ')') SEPARATOR ', ') as detail_barang
                FROM tbldetailprintresi dt
                JOIN tblsku s ON s.id_sku = dt.sku
                GROUP BY dt.id_resi
            ) m ON m.id_resi = pr.id_printresi
            WHERE rab.yangambil_pegawai = " . $this->db->escape($id_picker) . "
              AND DATE(rab.tanggal_resiambilbarang) = " . $this->db->escape($tanggal) . "
            ORDER BY rab.tanggal_resiambilbarang ASC
        ";
        return $this->db->query($sql)->result_array();
    }

    public function get_production_team_report_details_batch($start_date, $end_date)
    {
        $sql = "
            SELECT 
                rab.yangambil_pegawai as id_picker,
                DATE(rab.tanggal_resiambilbarang) as tanggal,
                SUM(CASE WHEN has_special = 1 AND distinct_skus = 1 AND total_qty <= 10 THEN 1 ELSE 0 END) as sku_special,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty > 9 THEN 1 ELSE 0 END) as resi_qty_banyak,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus = 1 OR distinct_skus IS NULL) THEN 1 ELSE 0 END) as resi_1_sku_sd_9,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus BETWEEN 2 AND 9) THEN 1 ELSE 0 END) as resi_2_9_sku_sd_9
            FROM tblresiambilbarang rab
            INNER JOIN tblprintresi pr ON pr.id_printresi = rab.id_resi
            LEFT JOIN (
                SELECT 
                    dt.id_resi,
                    COUNT(DISTINCT dt.sku) as distinct_skus,
                    SUM(dt.jumlah) as total_qty,
                    MAX(CASE WHEN s.is_special = 1 THEN 1 ELSE 0 END) as has_special
                FROM tbldetailprintresi dt
                JOIN tblsku s ON s.id_sku = dt.sku
                GROUP BY dt.id_resi
            ) m ON m.id_resi = pr.id_printresi
            WHERE rab.tanggal_resiambilbarang >= " . $this->db->escape($start_date) . "
              AND rab.tanggal_resiambilbarang <= " . $this->db->escape($end_date) . "
            GROUP BY rab.yangambil_pegawai, DATE(rab.tanggal_resiambilbarang)
        ";
        return $this->db->query($sql)->result_array();
    }

    public function get_picker_sku_summary_by_category($id_picker, $tanggal, $category)
    {
        $category_cond = "";
        if ($category == 'sku_special') {
            $category_cond = "AND m.has_special = 1 AND m.distinct_skus = 1 AND m.total_qty_resi <= 10";
        } elseif ($category == 'resi_qty_banyak') {
            $category_cond = "AND (COALESCE(m.has_special, 0) = 0 OR m.distinct_skus > 1 OR m.total_qty_resi > 10) AND m.total_qty_resi > 9";
        } elseif ($category == 'resi_1_sku_sd_9') {
            $category_cond = "AND (COALESCE(m.has_special, 0) = 0 OR m.distinct_skus > 1 OR m.total_qty_resi > 10) AND m.total_qty_resi <= 9 AND (m.distinct_skus = 1 OR m.distinct_skus IS NULL)";
        } elseif ($category == 'resi_2_9_sku_sd_9') {
            $category_cond = "AND (COALESCE(m.has_special, 0) = 0 OR m.distinct_skus > 1 OR m.total_qty_resi > 10) AND m.total_qty_resi <= 9 AND (m.distinct_skus BETWEEN 2 AND 9)";
        }

        $sql = "
            SELECT 
                dt.sku as id_sku,
                s.nama_sku,
                SUM(dt.jumlah) as total_qty,
                COUNT(DISTINCT pr.id_printresi) as resi_count
            FROM tblresiambilbarang rab
            INNER JOIN tblprintresi pr ON pr.id_printresi = rab.id_resi
            INNER JOIN tbldetailprintresi dt ON dt.id_resi = pr.id_printresi
            INNER JOIN tblsku s ON s.id_sku = dt.sku
            LEFT JOIN (
                SELECT 
                    dt2.id_resi,
                    COUNT(DISTINCT dt2.sku) as distinct_skus,
                    SUM(dt2.jumlah) as total_qty_resi,
                    MAX(CASE WHEN s2.is_special = 1 THEN 1 ELSE 0 END) as has_special
                FROM tbldetailprintresi dt2
                JOIN tblsku s2 ON s2.id_sku = dt2.sku
                GROUP BY dt2.id_resi
            ) m ON m.id_resi = pr.id_printresi
            WHERE rab.yangambil_pegawai = " . $this->db->escape($id_picker) . "
              AND DATE(rab.tanggal_resiambilbarang) = " . $this->db->escape($tanggal) . "
              $category_cond
            GROUP BY dt.sku, s.nama_sku
            ORDER BY total_qty DESC
        ";
        return $this->db->query($sql)->result_array();
    }

    public function get_picker_resi_list_by_sku_and_category($id_picker, $tanggal, $category, $id_sku)
    {
        $category_cond = "";
        if ($category == 'sku_special') {
            $category_cond = "AND m.has_special = 1 AND m.distinct_skus = 1 AND m.total_qty <= 10";
        } elseif ($category == 'resi_qty_banyak') {
            $category_cond = "AND (COALESCE(m.has_special, 0) = 0 OR m.distinct_skus > 1 OR m.total_qty > 10) AND m.total_qty > 9";
        } elseif ($category == 'resi_1_sku_sd_9') {
            $category_cond = "AND (COALESCE(m.has_special, 0) = 0 OR m.distinct_skus > 1 OR m.total_qty > 10) AND m.total_qty <= 9 AND (m.distinct_skus = 1 OR m.distinct_skus IS NULL)";
        } elseif ($category == 'resi_2_9_sku_sd_9') {
            $category_cond = "AND (COALESCE(m.has_special, 0) = 0 OR m.distinct_skus > 1 OR m.total_qty > 10) AND m.total_qty <= 9 AND (m.distinct_skus BETWEEN 2 AND 9)";
        }

        $sql = "
            SELECT DISTINCT
                pr.id_printresi,
                pr.noresi,
                pr.tanggal_bataskirim,
                pr.status_pesanan,
                rab.tanggal_resiambilbarang as waktu_scan,
                m.distinct_skus,
                m.total_qty,
                m.detail_barang
            FROM tblresiambilbarang rab
            INNER JOIN tblprintresi pr ON pr.id_printresi = rab.id_resi
            INNER JOIN tbldetailprintresi dt ON dt.id_resi = pr.id_printresi AND dt.sku = " . $this->db->escape($id_sku) . "
            LEFT JOIN (
                SELECT 
                    dt2.id_resi,
                    COUNT(DISTINCT dt2.sku) as distinct_skus,
                    SUM(dt2.jumlah) as total_qty,
                    MAX(CASE WHEN s2.is_special = 1 THEN 1 ELSE 0 END) as has_special,
                    GROUP_CONCAT(CONCAT(s2.nama_sku, ' (x', dt2.jumlah, ')') SEPARATOR ', ') as detail_barang
                FROM tbldetailprintresi dt2
                JOIN tblsku s2 ON s2.id_sku = dt2.sku
                GROUP BY dt2.id_resi
            ) m ON m.id_resi = pr.id_printresi
            WHERE rab.yangambil_pegawai = " . $this->db->escape($id_picker) . "
              AND DATE(rab.tanggal_resiambilbarang) = " . $this->db->escape($tanggal) . "
              $category_cond
            ORDER BY rab.tanggal_resiambilbarang ASC
        ";
        return $this->db->query($sql)->result_array();
    }

    public function get_packer_performance_detail_summary($id_packer, $tanggal)
    {
        $sql = "
            SELECT 
                SUM(CASE WHEN has_special = 1 AND distinct_skus = 1 AND total_qty <= 10 THEN 1 ELSE 0 END) as sku_special,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty > 9 THEN 1 ELSE 0 END) as resi_qty_banyak,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus = 1 OR distinct_skus IS NULL) THEN 1 ELSE 0 END) as resi_1_sku_sd_9,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus BETWEEN 2 AND 9) THEN 1 ELSE 0 END) as resi_2_9_sku_sd_9,
                COUNT(*) as total_resi
            FROM (
                SELECT 
                    pr.id_printresi,
                    m.distinct_skus,
                    m.total_qty,
                    COALESCE(m.has_special, 0) as has_special
                FROM tblpacking c
                INNER JOIN tblprintresi pr ON pr.id_printresi = c.id_resi
                LEFT JOIN (
                    SELECT 
                        dt.id_resi,
                        COUNT(DISTINCT dt.sku) as distinct_skus,
                        SUM(dt.jumlah) as total_qty,
                        MAX(CASE WHEN s.is_special = 1 THEN 1 ELSE 0 END) as has_special
                    FROM tbldetailprintresi dt
                    JOIN tblsku s ON s.id_sku = dt.sku
                    GROUP BY dt.id_resi
                ) m ON m.id_resi = pr.id_printresi
                WHERE c.packer_pegawai = " . $this->db->escape($id_packer) . "
                  AND DATE(c.tanggal_packing) = " . $this->db->escape($tanggal) . "
            ) as t
        ";
        return $this->db->query($sql)->row_array();
    }

    public function get_packer_sku_summary_by_category($id_packer, $tanggal, $category)
    {
        $category_cond = "";
        if ($category == 'sku_special') {
            $category_cond = "AND m.has_special = 1 AND m.distinct_skus = 1 AND m.total_qty_resi <= 10";
        } elseif ($category == 'resi_qty_banyak') {
            $category_cond = "AND (COALESCE(m.has_special, 0) = 0 OR m.distinct_skus > 1 OR m.total_qty_resi > 10) AND m.total_qty_resi > 9";
        } elseif ($category == 'resi_1_sku_sd_9') {
            $category_cond = "AND (COALESCE(m.has_special, 0) = 0 OR m.distinct_skus > 1 OR m.total_qty_resi > 10) AND m.total_qty_resi <= 9 AND (m.distinct_skus = 1 OR m.distinct_skus IS NULL)";
        } elseif ($category == 'resi_2_9_sku_sd_9') {
            $category_cond = "AND (COALESCE(m.has_special, 0) = 0 OR m.distinct_skus > 1 OR m.total_qty_resi > 10) AND m.total_qty_resi <= 9 AND (m.distinct_skus BETWEEN 2 AND 9)";
        }

        $sql = "
            SELECT 
                dt.sku as id_sku,
                s.nama_sku,
                SUM(dt.jumlah) as total_qty,
                COUNT(DISTINCT pr.id_printresi) as resi_count
            FROM tblpacking c
            INNER JOIN tblprintresi pr ON pr.id_printresi = c.id_resi
            INNER JOIN tbldetailprintresi dt ON dt.id_resi = pr.id_printresi
            INNER JOIN tblsku s ON s.id_sku = dt.sku
            LEFT JOIN (
                SELECT 
                    dt2.id_resi,
                    COUNT(DISTINCT dt2.sku) as distinct_skus,
                    SUM(dt2.jumlah) as total_qty_resi,
                    MAX(CASE WHEN s2.is_special = 1 THEN 1 ELSE 0 END) as has_special
                FROM tbldetailprintresi dt2
                JOIN tblsku s2 ON s2.id_sku = dt2.sku
                GROUP BY dt2.id_resi
            ) m ON m.id_resi = pr.id_printresi
            WHERE c.packer_pegawai = " . $this->db->escape($id_packer) . "
              AND DATE(c.tanggal_packing) = " . $this->db->escape($tanggal) . "
              $category_cond
            GROUP BY dt.sku, s.nama_sku
            ORDER BY total_qty DESC
        ";
        return $this->db->query($sql)->result_array();
    }

    public function get_packer_resi_list_by_sku_and_category($id_packer, $tanggal, $category, $id_sku)
    {
        $category_cond = "";
        if ($category == 'sku_special') {
            $category_cond = "AND m.has_special = 1 AND m.distinct_skus = 1 AND m.total_qty <= 10";
        } elseif ($category == 'resi_qty_banyak') {
            $category_cond = "AND (COALESCE(m.has_special, 0) = 0 OR m.distinct_skus > 1 OR m.total_qty > 10) AND m.total_qty > 9";
        } elseif ($category == 'resi_1_sku_sd_9') {
            $category_cond = "AND (COALESCE(m.has_special, 0) = 0 OR m.distinct_skus > 1 OR m.total_qty > 10) AND m.total_qty <= 9 AND (m.distinct_skus = 1 OR m.distinct_skus IS NULL)";
        } elseif ($category == 'resi_2_9_sku_sd_9') {
            $category_cond = "AND (COALESCE(m.has_special, 0) = 0 OR m.distinct_skus > 1 OR m.total_qty > 10) AND m.total_qty <= 9 AND (m.distinct_skus BETWEEN 2 AND 9)";
        }

        $sql = "
            SELECT DISTINCT
                pr.id_printresi,
                pr.noresi,
                pr.tanggal_bataskirim,
                pr.status_pesanan,
                c.tanggal_packing as waktu_scan,
                m.distinct_skus,
                m.total_qty,
                m.detail_barang
            FROM tblpacking c
            INNER JOIN tblprintresi pr ON pr.id_printresi = c.id_resi
            INNER JOIN tbldetailprintresi dt ON dt.id_resi = pr.id_printresi AND dt.sku = " . $this->db->escape($id_sku) . "
            LEFT JOIN (
                SELECT 
                    dt2.id_resi,
                    COUNT(DISTINCT dt2.sku) as distinct_skus,
                    SUM(dt2.jumlah) as total_qty,
                    MAX(CASE WHEN s2.is_special = 1 THEN 1 ELSE 0 END) as has_special,
                    GROUP_CONCAT(CONCAT(s2.nama_sku, ' (x', dt2.jumlah, ')') SEPARATOR ', ') as detail_barang
                FROM tbldetailprintresi dt2
                JOIN tblsku s2 ON s2.id_sku = dt2.sku
                GROUP BY dt2.id_resi
            ) m ON m.id_resi = pr.id_printresi
            WHERE c.packer_pegawai = " . $this->db->escape($id_packer) . "
              AND DATE(c.tanggal_packing) = " . $this->db->escape($tanggal) . "
              $category_cond
            ORDER BY c.tanggal_packing ASC
        ";
        return $this->db->query($sql)->result_array();
    }

    public function get_production_team_report_details_batch_tab1($start_date, $end_date)
    {
        $sql = "
            SELECT 
                c.packer_pegawai as id_packer,
                DATE(c.tanggal_packing) as tanggal,
                SUM(CASE WHEN has_special = 1 AND distinct_skus = 1 AND total_qty <= 10 THEN 1 ELSE 0 END) as sku_special,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty > 9 THEN 1 ELSE 0 END) as resi_qty_banyak,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus = 1 OR distinct_skus IS NULL) THEN 1 ELSE 0 END) as resi_1_sku_sd_9,
                SUM(CASE WHEN (has_special = 0 OR distinct_skus > 1 OR total_qty > 10) AND total_qty <= 9 AND (distinct_skus BETWEEN 2 AND 9) THEN 1 ELSE 0 END) as resi_2_9_sku_sd_9
            FROM tblpacking c
            INNER JOIN tblprintresi pr ON pr.id_printresi = c.id_resi
            LEFT JOIN (
                SELECT 
                    dt.id_resi,
                    COUNT(DISTINCT dt.sku) as distinct_skus,
                    SUM(dt.jumlah) as total_qty,
                    MAX(CASE WHEN s.is_special = 1 THEN 1 ELSE 0 END) as has_special
                FROM tbldetailprintresi dt
                JOIN tblsku s ON s.id_sku = dt.sku
                GROUP BY dt.id_resi
            ) m ON m.id_resi = pr.id_printresi
            WHERE c.tanggal_packing >= " . $this->db->escape($start_date) . "
              AND c.tanggal_packing <= " . $this->db->escape($end_date) . "
            GROUP BY c.packer_pegawai, DATE(c.tanggal_packing)
        ";
        return $this->db->query($sql)->result_array();
    }

    public function get_penyesuaian()
    {
        return $this->db->select('*')
                        ->from('pergantian_barang')
                        ->where('status_acc', 1)
                        ->group_start()
                        ->where('status_penyesuaian', 0)
                        ->or_where('status_penyesuaian IS NULL')
                        ->group_end()
                        ->get();
    }

    public function get_laporan_pergantian_barang()
    {
        return $this->db->get_where('pergantian_barang', ['status_acc' => 0]);
    }

    // ─────────────────────────────────────────────
    //  LAPORAN RESI CANCEL
    // ─────────────────────────────────────────────

    /**
     * Kondisi SQL "resi dianggap cancel". Dipakai count & data supaya seragam.
     *
     * Resi dianggap cancel bila salah satu terpenuhi:
     *  - tblprintresi.status_pesanan mengandung CANCEL (CANCELED / REQUEST_CANCEL
     *    hasil upload resi Jubelio),
     *  - tblprintresi.batal terisi (dibatalkan manual di iresis),
     *  - tercatat di tblcancelorder (sinkron Jubelio atau scan manual tim resi),
     *  - punya baris tblcancel_paket (pernah ditolak scan karena cancel;
     *    docs/PAKET_CANCEL.md) -- tetap tampil walau statusnya berubah lagi.
     */
    private function _kondisi_resi_cancel()
    {
        return "(pr.status_pesanan LIKE '%CANCEL%'
            OR (pr.batal IS NOT NULL AND pr.batal <> '' AND pr.batal <> '0')
            OR co.id_cancel IS NOT NULL
            OR cp.id_cancel_paket IS NOT NULL)";
    }

    /**
     * Query dasar laporan resi cancel: join tblcancelorder & tblcancel_paket
     * (wajib, bagian dari kondisi cancel) plus marketplace, kurir, picker,
     * packer, HO bila $join_nama TRUE (dibutuhkan untuk baris data &
     * pencarian; untuk hitung total tanpa pencarian dilewati supaya ringan).
     * Filter rentang pada tanggal_printresi.
     */
    private function _query_resi_cancel($start_date, $end_date, $search = '', $join_nama = true)
    {
        $this->db->from('tblprintresi pr');
        $this->db->join('tblcancelorder co', 'co.noresi = pr.noresi', 'left');
        $this->db->join('tblcancel_paket cp', 'cp.id_resi = pr.id_printresi', 'left');

        if ($join_nama || $search !== '') {
            $this->db->join('tblmarketplace m', 'm.id_marketplace = pr.id_marketplace', 'left');
            $this->db->join('tblkurir k', 'k.id_kurir = pr.id_kurir', 'left');
            $this->db->join('tblresiambilbarang rab', 'rab.id_resi = pr.id_printresi', 'left');
            $this->db->join('tblpegawai pg_picker', 'pg_picker.kode_pegawai = rab.yangambil_pegawai', 'left');
            $this->db->join('tblpacking pk', 'pk.id_resi = pr.id_printresi', 'left');
            $this->db->join('tbluser u_packer', 'u_packer.id_user = pk.packer_pegawai', 'left');
            $this->db->join('tblresikeluar rk', 'rk.id_resi = pr.id_printresi', 'left');
            $this->db->join('tblpegawai pg_ho', 'pg_ho.kode_pegawai = rk.id_pegawai', 'left');
            $this->db->join('tbluser u_penemu', 'u_penemu.id_user = cp.ditemukan_oleh', 'left');
        }

        $this->db->where('pr.tanggal_printresi >=', $start_date);
        $this->db->where('pr.tanggal_printresi <=', $end_date);
        $this->db->where($this->_kondisi_resi_cancel(), null, false);

        if ($search !== '' && $search !== null) {
            $this->db->group_start();
            $this->db->like('pr.noresi', $search);
            $this->db->or_like('m.nama_marketplace', $search);
            $this->db->or_like('k.nama_kurir', $search);
            $this->db->or_like('pr.status_pesanan', $search);
            $this->db->or_like('pr.keterangan', $search);
            $this->db->or_like('co.catatan', $search);
            $this->db->or_like('pg_picker.nama_pegawai', $search);
            $this->db->or_like('u_packer.name', $search);
            $this->db->or_like('pg_ho.nama_pegawai', $search);
            $this->db->group_end();
        }
    }

    /**
     * Data DataTable server-side + export Excel untuk Laporan Resi Cancel.
     *
     * Kolom (11): #, Marketplace, Tgl Print Resi, No Resi, Kurir,
     * Alasan Batal / Status, Cancel Diketahui, Tahap saat Cancel,
     * Picker, Packer, Scan By HO.
     *
     * "Cancel Diketahui" = kapan iresis pertama kali tahu resi ini cancel:
     * tblcancelorder.tanggal_cancel bila ada, kalau tidak tblprintresi.modified_at
     * (upload resi hanya menulis modified_at saat status_pesanan berubah).
     * "Tahap saat Cancel" dihitung dari scan yang terjadi SEBELUM waktu itu;
     * scan yang terjadi sesudahnya ditandai "(setelah cancel)" di kolomnya.
     *
     * @return array ['total' => int, 'filtered' => int, 'data' => array baris 11 kolom siap tampil]
     */
    public function get_resi_cancel_report_data($start_date, $end_date, $start = 0, $length = 10, $search = '')
    {
        $start  = max(0, (int) $start);
        $length = (int) $length;
        $search = trim((string) $search);

        // Total tanpa pencarian (tanpa join nama supaya ringan)
        $this->_query_resi_cancel($start_date, $end_date, '', false);
        $total = (int) $this->db->count_all_results();

        // Total sesuai pencarian
        if ($search !== '') {
            $this->_query_resi_cancel($start_date, $end_date, $search, false);
            $filtered = (int) $this->db->count_all_results();
        } else {
            $filtered = $total;
        }

        // Baris data
        $this->db->select("
            pr.noresi,
            pr.tanggal_printresi,
            pr.status_pesanan,
            pr.batal,
            pr.keterangan,
            pr.modified_at,
            pr.created_at,
            m.nama_marketplace,
            k.nama_kurir,
            co.sumber AS sumber_cancel,
            co.status_marketplace AS status_cancel,
            co.catatan AS catatan_cancel,
            co.tanggal_cancel,
            rab.tanggal_resiambilbarang AS waktu_picker,
            pk.tanggal_packing AS waktu_packer,
            rk.tanggal_resikeluar AS waktu_ho,
            pg_picker.nama_pegawai AS nama_picker,
            u_packer.name AS nama_packer,
            pg_ho.nama_pegawai AS nama_ho,
            cp.status AS paket_status,
            cp.ditemukan_di AS paket_ditemukan_di,
            cp.ditemukan_at AS paket_ditemukan_at,
            cp.jumlah_tolak AS paket_jumlah_tolak,
            cp.tolak_terakhir_di AS paket_tolak_terakhir_di,
            cp.tolak_terakhir_at AS paket_tolak_terakhir_at,
            u_penemu.name AS paket_ditemukan_oleh
        ", false);
        $this->_query_resi_cancel($start_date, $end_date, $search);
        $this->db->group_by('pr.id_printresi');
        $this->db->order_by('pr.tanggal_printresi', 'DESC');
        $this->db->order_by('pr.id_printresi', 'DESC');
        if ($length > 0) {
            $this->db->limit($length, $start);
        }
        $rows = $this->db->get()->result();

        $data = [];
        $no   = $start + 1;
        foreach ($rows as $r) {
            $waktu_cancel = $this->_waktu_cancel_diketahui($r);
            $tahap        = $this->_tahap_saat_cancel($r, $waktu_cancel);
            $jejak        = $this->_teks_paket_cancel($r);
            if ($jejak !== '') {
                $tahap .= '<br><small>' . $jejak . '</small>';
            }

            $data[] = [
                $no++ . '.',
                $r->nama_marketplace ?: '-',
                !empty($r->tanggal_printresi) ? date('d/m/Y H:i:s', strtotime($r->tanggal_printresi)) : '-',
                $r->noresi,
                $r->nama_kurir ?: '-',
                $this->_teks_status_cancel($r),
                $waktu_cancel !== null ? date('d/m/Y H:i:s', $waktu_cancel) : '-',
                $tahap,
                $this->_teks_scan($r->nama_picker, $r->waktu_picker, $waktu_cancel),
                $this->_teks_scan($r->nama_packer, $r->waktu_packer, $waktu_cancel),
                $this->_teks_scan($r->nama_ho, $r->waktu_ho, $waktu_cancel),
            ];
        }

        return [
            'total'    => $total,
            'filtered' => $filtered,
            'data'     => $data,
        ];
    }

    /**
     * Timestamp (unix) saat iresis pertama kali tahu resi ini cancel, atau NULL
     * kalau tidak ada satu pun penanda waktu.
     */
    private function _waktu_cancel_diketahui($r)
    {
        foreach ([$r->tanggal_cancel, $r->modified_at, $r->created_at, $r->tanggal_printresi] as $kandidat) {
            if (!empty($kandidat) && $kandidat !== '0000-00-00 00:00:00') {
                $ts = strtotime($kandidat);
                if ($ts !== false) {
                    return $ts;
                }
            }
        }
        return null;
    }

    /**
     * Baris kedua kolom "Tahap saat Cancel": jejak fisik dari tblcancel_paket
     * (docs/PAKET_CANCEL.md §7.3). Kosong bila resi belum pernah ditolak scan.
     *
     * Contoh: "Ditolak di HO 21/09 09:15 oleh GUNTUR (2×) — packer lupa scan"
     */
    private function _teks_paket_cancel($r)
    {
        if (empty($r->paket_status)) {
            return '';
        }

        $label_meja = [
            'PICKER'    => 'picker',
            'INBOUND'   => 'inbound',
            'PACKER'    => 'packer',
            'HO'        => 'HO',
            'LOST_SCAN' => 'lost scan',
        ];
        $meja  = $label_meja[$r->paket_ditemukan_di] ?? strtolower((string) $r->paket_ditemukan_di);
        $teks  = 'Ditolak di ' . $meja;
        if (!empty($r->paket_ditemukan_at)) {
            $teks .= ' ' . date('d/m H:i', strtotime($r->paket_ditemukan_at));
        }
        if (!empty($r->paket_ditemukan_oleh)) {
            $teks .= ' oleh ' . $r->paket_ditemukan_oleh;
        }

        $jumlah = (int) $r->paket_jumlah_tolak;
        if ($jumlah > 1) {
            $teks .= ' (' . $jumlah . '×';
            if (!empty($r->paket_tolak_terakhir_di) && $r->paket_tolak_terakhir_di !== $r->paket_ditemukan_di) {
                $teks .= ', terakhir ' . ($label_meja[$r->paket_tolak_terakhir_di] ?? strtolower($r->paket_tolak_terakhir_di));
            }
            $teks .= ')';
        }

        // Penanda lupa scan: paket sampai meja X tanpa scan meja sebelumnya.
        $penanda = [];
        $sampai_packer_atau_ho = in_array($r->paket_ditemukan_di, ['INBOUND', 'PACKER', 'HO'], true)
            || in_array($r->paket_tolak_terakhir_di, ['INBOUND', 'PACKER', 'HO'], true);
        $sampai_ho = $r->paket_ditemukan_di === 'HO' || $r->paket_tolak_terakhir_di === 'HO';
        if ($sampai_ho && empty($r->waktu_packer)) {
            $penanda[] = 'packer lupa scan';
        }
        if ($sampai_packer_atau_ho && empty($r->waktu_picker) && $r->paket_ditemukan_di !== 'INBOUND') {
            $penanda[] = 'picker lupa scan';
        }
        if ($penanda) {
            $teks .= ' — <b>' . implode(', ', $penanda) . '</b>';
        }

        if ($r->paket_status !== 'DITEMUKAN') {
            $teks .= ' · ' . $r->paket_status;
        }

        return $teks;
    }

    /**
     * Sampai tahap mana resi sudah diproses ketika cancel diketahui.
     * Hanya scan yang terjadi <= waktu cancel yang dihitung; kalau waktu cancel
     * tidak diketahui, semua scan yang ada dihitung.
     */
    private function _tahap_saat_cancel($r, $waktu_cancel)
    {
        $sebelum_cancel = function ($waktu_scan) use ($waktu_cancel) {
            if (empty($waktu_scan)) {
                return false;
            }
            if ($waktu_cancel === null) {
                return true;
            }
            $ts = strtotime($waktu_scan);
            return $ts !== false && $ts <= $waktu_cancel;
        };

        if ($sebelum_cancel($r->waktu_ho)) {
            return 'Setelah HO (sudah keluar)';
        }
        if ($sebelum_cancel($r->waktu_packer)) {
            return 'Setelah Packer';
        }
        if ($sebelum_cancel($r->waktu_picker)) {
            return 'Setelah Picker';
        }
        return 'Belum diproses';
    }

    /**
     * Isi sel Picker/Packer/HO: nama + jam scan; diberi tanda kalau scan
     * terjadi sesudah cancel diketahui (seharusnya tertolak).
     */
    private function _teks_scan($nama, $waktu_scan, $waktu_cancel)
    {
        if (empty($nama) && empty($waktu_scan)) {
            return '-';
        }
        $teks = $nama ?: '(tanpa nama)';
        $ts   = !empty($waktu_scan) ? strtotime($waktu_scan) : false;
        if ($ts !== false) {
            $teks .= ' ' . date('d/m H:i', $ts);
            if ($waktu_cancel !== null && $ts > $waktu_cancel) {
                $teks .= ' (setelah cancel)';
            }
        }
        return $teks;
    }

    /** Susun teks kolom "Alasan Batal / Status" dari satu baris laporan. */
    private function _teks_status_cancel($r)
    {
        $batal_manual  = $r->batal !== null && $r->batal !== '' && $r->batal !== '0';
        $status_cancel = !empty($r->status_pesanan) && stripos($r->status_pesanan, 'CANCEL') !== false;
        $bagian = [];

        if ($batal_manual) {
            $bagian[] = 'BATAL MANUAL' . (!empty($r->keterangan) ? ': ' . $r->keterangan : '');
        } elseif ($status_cancel) {
            $bagian[] = $r->status_pesanan;
        } else {
            // Hanya tercatat di tblcancelorder (snapshot saat cancel terdeteksi)
            $bagian[] = !empty($r->status_cancel) ? $r->status_cancel : 'CANCEL';
        }

        if (!empty($r->sumber_cancel)) {
            $label = $r->sumber_cancel === 'SCAN' ? 'scan tim resi' : 'Jubelio';
            $waktu = !empty($r->tanggal_cancel) ? ' ' . date('d/m/Y H:i', strtotime($r->tanggal_cancel)) : '';
            $bagian[] = '(' . $label . $waktu . ')';
        }

        // Status di tblprintresi sudah berubah lagi setelah cancel tercatat
        if (!$batal_manual && !$status_cancel && !empty($r->status_pesanan)) {
            $bagian[] = '- status kini: ' . $r->status_pesanan;
        }

        if (!empty($r->catatan_cancel)) {
            $bagian[] = '- ' . $r->catatan_cancel;
        }

        return implode(' ', $bagian);
    }
}

