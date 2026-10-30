<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Qc_return_fcd extends CI_Model
{
    function get_data($filters = array())
    {
        $this->db->select('t.*, u1.name as submitter_name, u2.name as acc_name');
        $this->db->from('tblpengembalian_qc t');
        $this->db->join('tbluser u1', 'u1.id_user = t.submit_by', 'left');
        $this->db->join('tbluser u2', 'u2.id_user = t.acc_by', 'left');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $this->db->where('t.tanggal >=', $filters['start_date']);
            $this->db->where('t.tanggal <=', $filters['end_date']);
        }

        if (!empty($filters['status'])) {
            $this->db->where('t.status', $filters['status']);
        }
        
        if (!empty($filters['kondisi'])) {
            $this->db->where('t.kondisi', $filters['kondisi']);
        }

        $this->db->order_by('t.id_pengembalian', 'DESC');
        return $this->db->get();
    }

    function save($data)
    {
        return $this->db->insert('tblpengembalian_qc', $data);
    }

    function approve($id, $user_id, $status = 'APPROVED')
    {
        $data = array(
            'status' => $status,
            'acc_by' => $user_id,
            'acc_at' => date('Y-m-d H:i:s')
        );
        $this->db->where('id_pengembalian', $id);
        return $this->db->update('tblpengembalian_qc', $data);
    }

    function approve_all($user_id)
    {
        $data = array(
            'status' => 'APPROVED',
            'acc_by' => $user_id,
            'acc_at' => date('Y-m-d H:i:s')
        );
        $this->db->where('status', 'PENDING');
        return $this->db->update('tblpengembalian_qc', $data);
    }

    function get_by_id($id)
    {
        $this->db->where('id_pengembalian', $id);
        return $this->db->get('tblpengembalian_qc')->row();
    }

    function get_sku_suggestions($search)
    {
        $this->db->select('id_sku, nama_sku, no_rak');
        $this->db->from('tblsku');
        $this->db->like('id_sku', $search);
        $this->db->order_by('id_sku');
        $this->db->limit(10);
        return $this->db->get()->result_array();
    }

    /**
     * Cari satu SKU dari teks yang diketik user (boleh tidak lengkap, huruf besar/kecil bebas).
     * Urutan pencocokan:
     *   1. persis sama     : "BM-AKS28-2"
     *   2. mengandung teks : "aks28-2" -> hanya BM-AKS28-2 yang cocok
     *   3. akhiran sama    : "aks28-1" -> mengandung 6 SKU (-1, -10, -11, ...), tapi yang berakhiran "-1" cuma BM-AKS28-1
     *
     * Hasil:
     *   ['status' => 'ok',        'sku' => baris tblsku]
     *   ['status' => 'ambigu',    'kandidat' => [baris, ...]]  -> lebih dari satu, user harus pilih
     *   ['status' => 'tidak_ada']
     */
    function cari_sku($teks)
    {
        $teks = trim((string) $teks);
        if ($teks === '') {
            return array('status' => 'tidak_ada');
        }

        // 1. Persis sama (kolasi id_sku sudah case-insensitive)
        $this->db->select('id_sku, nama_sku, no_rak');
        $this->db->where('id_sku', $teks);
        $row = $this->db->get('tblsku')->row_array();
        if ($row) {
            return array('status' => 'ok', 'sku' => $row);
        }

        // 2. Mengandung teks
        $kandidat = $this->get_sku_suggestions($teks);
        if (count($kandidat) === 0) {
            return array('status' => 'tidak_ada');
        }
        if (count($kandidat) === 1) {
            return array('status' => 'ok', 'sku' => $kandidat[0]);
        }

        // 3. Banyak kandidat: ambil yang akhirannya persis sama, asal cuma satu
        $this->db->select('id_sku, nama_sku, no_rak');
        $this->db->like('id_sku', $teks, 'before');
        $this->db->limit(2);
        $akhiran = $this->db->get('tblsku')->result_array();
        if (count($akhiran) === 1) {
            return array('status' => 'ok', 'sku' => $akhiran[0]);
        }

        return array('status' => 'ambigu', 'kandidat' => $kandidat);
    }

    function get_data_export($start_date, $end_date, $status = null, $kondisi = null)
    {
        $this->db->select('t.*, u1.username as submitter, u2.username as approver');
        $this->db->from('tblpengembalian_qc t');
        $this->db->join('tbluser u1', 'u1.id_user = t.submit_by', 'left');
        $this->db->join('tbluser u2', 'u2.id_user = t.acc_by', 'left');

        $this->db->where('t.tanggal >=', $start_date);
        $this->db->where('t.tanggal <=', $end_date);

        if (!empty($status)) {
            $this->db->where('t.status', $status);
        }

        if (!empty($kondisi)) {
            $this->db->where('t.kondisi', $kondisi);
        }

        $this->db->order_by('t.tanggal', 'DESC');
        $this->db->order_by('t.id_pengembalian', 'DESC');

        return $this->db->get();
    }

    // Rekap serah terima QC ke Restok: per rak + SKU, hanya kondisi SALAH AMBIL / LEBIH AMBIL
    function get_serah_terima($start_date, $end_date)
    {
        $this->db->select('no_rak, sku, SUM(qty) AS qty', FALSE);
        $this->db->from('tblpengembalian_qc');
        $this->db->where('tanggal >=', $start_date);
        $this->db->where('tanggal <=', $end_date);
        $this->db->where_in('kondisi', array('SALAH AMBIL', 'LEBIH AMBIL'));
        $this->db->group_by(array('no_rak', 'sku'));
        $this->db->order_by('no_rak', 'ASC');
        $this->db->order_by('sku', 'ASC');
        return $this->db->get()->result_array();
    }

    // Nama penyerah (submitter) pada rentang tanggal, urut dari yang paling banyak input
    function get_penyerah_serah_terima($start_date, $end_date)
    {
        $this->db->select('u.name');
        $this->db->from('tblpengembalian_qc t');
        $this->db->join('tbluser u', 'u.id_user = t.submit_by', 'left');
        $this->db->where('t.tanggal >=', $start_date);
        $this->db->where('t.tanggal <=', $end_date);
        $this->db->where_in('t.kondisi', array('SALAH AMBIL', 'LEBIH AMBIL'));
        $this->db->group_by('t.submit_by');
        $this->db->order_by('COUNT(*)', 'DESC', FALSE);
        return array_column($this->db->get()->result_array(), 'name');
    }

    // Catat satu kali cetak serah terima (snapshot isi yang dicetak)
    function simpan_riwayat_cetak($data)
    {
        return $this->db->insert('tblpengembalian_qc_cetak', $data);
    }

    function get_riwayat_cetak($limit = 100)
    {
        $this->db->select('c.id_cetak, c.tanggal_awal, c.tanggal_akhir, c.jumlah_sku, c.total_qty, c.penyerah, c.dicetak_at, u.name AS pencetak');
        $this->db->from('tblpengembalian_qc_cetak c');
        $this->db->join('tbluser u', 'u.id_user = c.dicetak_oleh', 'left');
        $this->db->order_by('c.id_cetak', 'DESC');
        $this->db->limit((int) $limit);
        return $this->db->get()->result_array();
    }

    // Pengerjaan harian: satu baris per tanggal data (30 hari terakhir) + status ambil/selesai + riwayat cetak
    function get_pengerjaan_harian($hari = 30)
    {
        $sql = "SELECT q.tanggal, COUNT(DISTINCT q.no_rak, q.sku) AS jumlah_sku, SUM(q.qty) AS total_qty,
                    s.diambil_at, ua.name AS diambil_nama, s.selesai_at, us.name AS selesai_nama,
                    c.jumlah_cetak, c.cetak_pertama, c.cetak_terakhir
                FROM tblpengembalian_qc q
                LEFT JOIN tblpengembalian_qc_serah s ON s.tanggal = q.tanggal
                LEFT JOIN tbluser ua ON ua.id_user = s.diambil_oleh
                LEFT JOIN tbluser us ON us.id_user = s.selesai_oleh
                LEFT JOIN (
                    SELECT tanggal_awal, COUNT(*) AS jumlah_cetak, MIN(dicetak_at) AS cetak_pertama, MAX(dicetak_at) AS cetak_terakhir
                    FROM tblpengembalian_qc_cetak
                    WHERE tanggal_awal = tanggal_akhir
                    GROUP BY tanggal_awal
                ) c ON c.tanggal_awal = q.tanggal
                WHERE q.kondisi IN ('SALAH AMBIL', 'LEBIH AMBIL') AND q.tanggal >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY q.tanggal
                ORDER BY q.tanggal DESC";
        return $this->db->query($sql, array((int) $hari))->result_array();
    }

    function get_status_serah($tanggal)
    {
        return $this->db->get_where('tblpengembalian_qc_serah', array('tanggal' => $tanggal))->row_array();
    }

    // Tandai barang diambil Restok. Hanya sekali per tanggal: FALSE bila sudah pernah.
    function tandai_diambil($tanggal, $id_user)
    {
        $this->db->query("INSERT IGNORE INTO tblpengembalian_qc_serah (tanggal) VALUES (?)", array($tanggal));
        $this->db->query("UPDATE tblpengembalian_qc_serah SET diambil_at = NOW(), diambil_oleh = ? WHERE tanggal = ? AND diambil_at IS NULL", array((int) $id_user, $tanggal));
        return $this->db->affected_rows() > 0;
    }

    // Tandai selesai. Hanya setelah diambil dan hanya sekali: FALSE bila tidak memenuhi.
    function tandai_selesai($tanggal, $id_user)
    {
        $this->db->query("UPDATE tblpengembalian_qc_serah SET selesai_at = NOW(), selesai_oleh = ? WHERE tanggal = ? AND diambil_at IS NOT NULL AND selesai_at IS NULL", array((int) $id_user, $tanggal));
        return $this->db->affected_rows() > 0;
    }

    // Tandai kesalahan picker (LEBIH/SALAH AMBIL) terpilih sebagai barang diambil Restok.
    // Baris yang sudah ditandai dilewati. Mengembalikan jumlah baris yang baru ditandai.
    function tandai_kesalahan_diambil($ids, $id_user)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $ids))));
        if (!$ids) {
            return 0;
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge(array((int) $id_user), $ids);
        $this->db->query("INSERT IGNORE INTO tblkesalahan_picker_diambil (id_masalahpicker, diambil_oleh, diambil_at)
                          SELECT id_masalahpicker, ?, NOW() FROM tblmasalahpicker
                          WHERE id_typemasalah IN (2, 4) AND id_masalahpicker IN ($ph)", $params);
        return $this->db->affected_rows();
    }

    function delete_data($id)
    {
        $this->db->where('id_pengembalian', $id);
        return $this->db->delete('tblpengembalian_qc');
    }

    function get_proses_barang_qc($filters = array())
    {
        $this->db->select('t.*, u1.name as submitter_name, u2.name as acc_name, 
                           pr.no_penyesuaian as reject_no_penyesuaian, pr.foto_barang as reject_foto, pr.created_at as reject_at, pr.keterangan as reject_keterangan,
                           pr_repair.no_penyesuaian as repair_no_penyesuaian, pr_repair.status as repair_status, pr_repair.foto_barang as repair_foto, pr_repair.created_at as repair_at, pr_repair.keterangan as repair_keterangan');
        $this->db->from('tblpengembalian_qc t');
        $this->db->join('tbluser u1', 'u1.id_user = t.submit_by', 'left');
        $this->db->join('tbluser u2', 'u2.id_user = t.acc_by', 'left');
        $this->db->join('purchasing_reject pr', 't.id_pengembalian = pr.id_pengembalian', 'left');
        $this->db->join('purchasing_repair pr_repair', 't.id_pengembalian = pr_repair.id_pengembalian', 'left');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $this->db->where('t.tanggal >=', $filters['start_date']);
            $this->db->where('t.tanggal <=', $filters['end_date']);
        }

        if (!empty($filters['status'])) {
            $this->db->where('t.status', $filters['status']);
        }
        
        $this->db->order_by('t.tanggal', 'DESC');
        $this->db->order_by('t.id_pengembalian', 'DESC');
        return $this->db->get();
    }

    /**
     * Kesalahan picker (tblmasalahpicker, dilaporkan Packer saat scan) pada rentang
     * tanggal. Picker dibaca dari tblresiambilbarang -- sama dengan menu Daftar
     * Masalah Picker New, jadi atribusinya konsisten. Hanya baca, semua status.
     */
    function get_kesalahan_picker($start_date, $end_date)
    {
        $this->db->select('
            mp.id_masalahpicker, mp.created, mp.noresi, mp.sku, mp.sku_salah, mp.qty, mp.qty_bermasalah,
            kd.diambil_at AS diambil_baris_at, sr.diambil_at, sr.selesai_at, ct.jumlah_cetak,
            tm.type_masalah,
            COALESCE(NULLIF(NULLIF(TRIM(s.nama_sku), \'\'), \'False\'), mp.sku) AS nama_barang,
            COALESCE(peg_picker.nama_pegawai,
                     (SELECT up.name FROM tbluser up WHERE up.id_pegawai = rab.yangambil_pegawai LIMIT 1)
            ) AS nama_picker,
            COALESCE(peg_packer.nama_pegawai, u.name) AS nama_packer
        ', FALSE);
        $this->db->from('tblmasalahpicker mp');
        $this->db->join('tbltypemasalah tm', 'tm.id_typemasalah = mp.id_typemasalah', 'left');
        $this->db->join('tblsku s', 's.id_sku = mp.sku', 'left');
        $this->db->join('tbluser u', 'u.id_user = mp.created_by', 'left');
        $this->db->join('tblpegawai peg_packer', 'peg_packer.kode_pegawai = u.id_pegawai', 'left');
        $this->db->join('tblresiambilbarang rab', 'rab.id_resi = mp.id_printresi', 'left');
        $this->db->join('tblpegawai peg_picker', 'peg_picker.kode_pegawai = rab.yangambil_pegawai', 'left');
        $this->db->join('tblkesalahan_picker_diambil kd', 'kd.id_masalahpicker = mp.id_masalahpicker', 'left');
        // Status pengembalian mengikuti pengerjaan serah terima harian pada tanggal yang sama
        $this->db->join('tblpengembalian_qc_serah sr', 'sr.tanggal = DATE(mp.created)', 'left');
        $this->db->join('(SELECT tanggal_awal, COUNT(*) AS jumlah_cetak FROM tblpengembalian_qc_cetak WHERE tanggal_awal = tanggal_akhir GROUP BY tanggal_awal) ct', 'ct.tanggal_awal = DATE(mp.created)', 'left', FALSE);
        $this->db->where_in('mp.id_typemasalah', array(2, 4)); // LEBIH AMBIL, SALAH AMBIL
        $this->db->where('mp.created >=', $start_date . ' 00:00:00');
        $this->db->where('mp.created <=', $end_date . ' 23:59:59');
        $this->db->order_by('mp.created', 'DESC');
        $this->db->limit(1000);
        return $this->db->get();
    }
}
