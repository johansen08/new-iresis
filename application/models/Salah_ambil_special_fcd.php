<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model menu TIM PACKER -> "Salah Ambil Special".
 *
 * Sengaja tidak memakai Packer_fcd::save_masalah_picker(): fungsi itu
 * meng-update baris tblmasalahpicker yang sudah ada dan mereset statusnya ke
 * pending, sedangkan menu ini harus MENOLAK resi yang sudah pernah dilaporkan
 * (status apa pun). Tabel tujuannya tetap tblmasalahpicker supaya laporan
 * langsung muncul di Daftar Masalah Picker (lama & New), KPI picker, dan
 * Error Recap tanpa perubahan di sana.
 *
 * Semua method di sini hanya query; urutan validasinya ada di controller.
 */
class Salah_ambil_special_fcd extends CI_Model
{
    /** Sama dengan Masalah_picker_new_fcd::TIPE_SALAH_AMBIL (tbltypemasalah). */
    const TIPE_SALAH_AMBIL = 4;

    /** Satu baris master SKU (id_sku, nama_sku, no_rak), atau NULL kalau kode tidak ada. */
    public function cari_sku($kode)
    {
        $row = $this->db->select('id_sku, nama_sku, no_rak')
            ->get_where('tblsku', ['id_sku' => $kode])
            ->row_array();
        return $row ?: NULL;
    }

    /**
     * Saran live search untuk field SKU: kode yang DIAWALI ketikan didahulukan,
     * lalu kode/nama yang mengandung ketikan. Dibatasi supaya dropdown ringkas.
     */
    public function cari_sku_mirip($term, $limit = 15)
    {
        $term_awal = $this->db->escape_like_str($term) . '%';
        $term_isi  = '%' . $this->db->escape_like_str($term) . '%';
        $sql = "SELECT id_sku, nama_sku, no_rak
                FROM tblsku
                WHERE id_sku LIKE ? ESCAPE '!' OR nama_sku LIKE ? ESCAPE '!'
                ORDER BY (id_sku LIKE ? ESCAPE '!') DESC, id_sku ASC
                LIMIT " . (int) $limit;
        return $this->db->query($sql, [$term_isi, $term_isi, $term_awal])->result_array();
    }

    /**
     * id_printresi terbaru untuk satu noresi. Resi yang dicetak ulang punya
     * beberapa baris tblprintresi; yang dipakai yang terakhir dibuat, sama
     * dengan Packer::detail_resi() (ORDER BY created_at DESC, rows[0]).
     */
    public function resi_terbaru($noresi)
    {
        $row = $this->db->select('id_printresi, noresi')
            ->where('noresi', $noresi)
            ->order_by('created_at', 'DESC')
            ->order_by('id_printresi', 'DESC')
            ->limit(1)
            ->get('tblprintresi')
            ->row_array();
        return $row ?: NULL;
    }

    /** Semua baris SKU di resi: [['sku' => ..., 'jumlah' => ...], ...]. */
    public function detail_resi($id_printresi)
    {
        return $this->db->select('sku, jumlah')
            ->where('id_resi', $id_printresi)
            ->order_by('id_detail_resi', 'ASC')
            ->get('tbldetailprintresi')
            ->result_array();
    }

    public function sudah_packing($id_printresi)
    {
        return $this->db->where('id_resi', $id_printresi)->count_all_results('tblpacking') > 0;
    }

    /**
     * Picker yang scan ambil resi ini: kode pegawai + nama (tblpegawai, cadangan
     * tbluser.name). NULL kalau resi belum pernah di-scan ambil -- CS tidak
     * bisa mengaitkan laporan ke siapa pun, jadi controller menolaknya.
     */
    public function picker_resi($id_printresi)
    {
        $row = $this->db->select('rab.yangambil_pegawai AS kode_picker, COALESCE(peg.nama_pegawai, u.name) AS nama_picker', FALSE)
            ->from('tblresiambilbarang rab')
            ->join('tblpegawai peg', 'peg.kode_pegawai = rab.yangambil_pegawai', 'left')
            ->join('tbluser u', 'u.id_pegawai = rab.yangambil_pegawai', 'left')
            ->where('rab.id_resi', $id_printresi)
            ->order_by('rab.id_resiambilbarang', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();
        return $row ?: NULL;
    }

    /** Laporan masalah picker yang sudah ada untuk resi + SKU ini, status apa pun. */
    public function laporan_ada($id_printresi, $sku)
    {
        $row = $this->db->select('id_masalahpicker, status')
            ->where('id_printresi', $id_printresi)
            ->where('sku', $sku)
            ->order_by('id_masalahpicker', 'DESC')
            ->limit(1)
            ->get('tblmasalahpicker')
            ->row_array();
        return $row ?: NULL;
    }

    /**
     * Kunci baris resi selama transaksi berjalan. Dua scan resi yang sama yang
     * datang nyaris bersamaan jadi antre di sini: yang kedua baru lanjut
     * setelah yang pertama commit, lalu tertangkap laporan_ada().
     * Wajib dipanggil di antara trans_begin() dan trans_commit().
     */
    public function kunci_resi($id_printresi)
    {
        $this->db->query('SELECT id_printresi FROM tblprintresi WHERE id_printresi = ? FOR UPDATE', [(int) $id_printresi]);
    }

    /**
     * Satu baris SALAH AMBIL, bentuknya sama dengan yang dibuat modal Masalah
     * Picker di Scan Resi Packer (qty 1, qty_bermasalah 1, status 0 = pending).
     */
    public function simpan_salah_ambil(array $data)
    {
        $this->db->insert('tblmasalahpicker', [
            'id_printresi'   => (int) $data['id_printresi'],
            'noresi'         => $data['noresi'],
            'sku'            => $data['sku'],
            'qty'            => 1,
            'id_typemasalah' => self::TIPE_SALAH_AMBIL,
            'qty_bermasalah' => 1,
            'sku_salah'      => $data['sku_salah'],
            'status'         => 0,
            'created_by'     => (int) $data['id_user'],
            'created'        => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insert_id();
    }
}
