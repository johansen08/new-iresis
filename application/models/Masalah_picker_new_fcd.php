<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model untuk menu "Daftar Masalah Picker New" (TIM CS).
 *
 * Sengaja berdiri sendiri dari Cs.php / Restock_fcd.php: tabel sumbernya
 * (tblmasalahpicker, diisi Packer saat scan) tetap sama, tapi semua query,
 * tabel riwayat cetak, dan aturan prosesnya ada di sini supaya menu lama
 * "Daftar Masalah Picker" tidak tersentuh dan masih bisa dipakai kapan pun.
 *
 * Aturan yang membedakan dari versi lama:
 * - Picker tidak dipilih manual, melainkan dibaca dari tblresiambilbarang
 *   (pegawai yang scan ambil resi itu) -- sama dengan cara KPI picker
 *   menghitung kesalahan, jadi atribusinya konsisten.
 * - Packer/pelapor = user yang melaporkan masalah di Scan Resi Packer.
 * - LEBIH AMBIL diproses (status 1) tapi TIDAK dicetak: tidak ada barang yang
 *   perlu diambil; kelebihannya sudah ditangani QC lewat Input Pengembalian.
 * - Setiap klik proses dicatat di tblmasalahpicker_proses (+ _item sebagai
 *   snapshot) sehingga slip bisa dicetak ulang kalau printer bermasalah.
 */
class Masalah_picker_new_fcd extends CI_Model
{
    const TIPE_TIDAK_AMBIL   = 1;
    const TIPE_LEBIH_AMBIL   = 2;
    const TIPE_KURANG_AMBIL  = 3;
    const TIPE_SALAH_AMBIL   = 4;
    const TIPE_REJECT_DISPLAY = 5;

    /** Tipe masalah yang perlu barang diambil ulang oleh picker (dicetak di slip). */
    public static $tipe_perlu_ambil = [
        self::TIPE_TIDAK_AMBIL,
        self::TIPE_KURANG_AMBIL,
        self::TIPE_SALAH_AMBIL,
        self::TIPE_REJECT_DISPLAY,
    ];

    /**
     * SELECT + JOIN dasar untuk satu baris masalah lengkap dengan picker,
     * packer, nama barang, dan no rak. Dipakai daftar, detail, dan proses.
     */
    private function select_dasar()
    {
        $this->db->select('
            mp.id_masalahpicker,
            mp.id_printresi,
            mp.noresi,
            mp.sku,
            mp.qty,
            mp.qty_bermasalah,
            mp.sku_salah,
            mp.id_typemasalah,
            mp.status,
            mp.created,
            mp.updated,
            mp.created_by,
            tm.type_masalah,
            COALESCE(NULLIF(NULLIF(TRIM(s.nama_sku), \'\'), \'False\'), mp.sku) AS nama_barang,
            s.link_foto,
            rab.yangambil_pegawai AS kode_picker,
            COALESCE(peg_picker.nama_pegawai,
                     (SELECT up.name FROM tbluser up WHERE up.id_pegawai = rab.yangambil_pegawai LIMIT 1)
            ) AS nama_picker,
            COALESCE(peg_packer.nama_pegawai, u.name) AS nama_packer,
            COALESCE(
                NULLIF((SELECT dr.no_rak FROM tbldetailprintresi dr
                        WHERE dr.id_resi = mp.id_printresi AND dr.sku = mp.sku
                        ORDER BY dr.id_detail_resi ASC LIMIT 1), \'\'),
                s.no_rak
            ) AS no_rak
        ', FALSE);
        $this->db->from('tblmasalahpicker mp');
        $this->db->join('tbltypemasalah tm', 'tm.id_typemasalah = mp.id_typemasalah', 'left');
        $this->db->join('tblsku s', 's.id_sku = mp.sku', 'left');
        $this->db->join('tbluser u', 'u.id_user = mp.created_by', 'left');
        $this->db->join('tblpegawai peg_packer', 'peg_packer.kode_pegawai = u.id_pegawai', 'left');
        $this->db->join('tblresiambilbarang rab', 'rab.id_resi = mp.id_printresi', 'left');
        $this->db->join('tblpegawai peg_picker', 'peg_picker.kode_pegawai = rab.yangambil_pegawai', 'left');
    }

    private function filter_daftar($start_date, $end_date, $search = '', $tipe = '')
    {
        $this->db->where('mp.status', 0);
        $this->db->where('mp.created >=', $start_date);
        $this->db->where('mp.created <=', $end_date);

        if ($tipe !== '' && $tipe !== null) {
            $this->db->where('mp.id_typemasalah', (int) $tipe);
        }

        if ($search !== '') {
            $this->db->group_start();
            $this->db->like('mp.sku', $search);
            $this->db->or_like('mp.noresi', $search);
            $this->db->or_like('mp.sku_salah', $search);
            $this->db->or_like('tm.type_masalah', $search);
            $this->db->or_like('peg_picker.nama_pegawai', $search);
            $this->db->or_like('peg_packer.nama_pegawai', $search);
            $this->db->or_like('u.name', $search);
            $this->db->group_end();
        }
    }

    /**
     * Daftar masalah yang belum diproses (status 0) untuk DataTables.
     * Mengembalikan ['total' => n, 'rows' => [...]].
     */
    public function daftar_pending($params)
    {
        // Hitung total dengan join yang sama supaya pencarian nama picker/packer ikut.
        $this->db->select('mp.id_masalahpicker');
        $this->db->from('tblmasalahpicker mp');
        $this->db->join('tbltypemasalah tm', 'tm.id_typemasalah = mp.id_typemasalah', 'left');
        $this->db->join('tbluser u', 'u.id_user = mp.created_by', 'left');
        $this->db->join('tblpegawai peg_packer', 'peg_packer.kode_pegawai = u.id_pegawai', 'left');
        $this->db->join('tblresiambilbarang rab', 'rab.id_resi = mp.id_printresi', 'left');
        $this->db->join('tblpegawai peg_picker', 'peg_picker.kode_pegawai = rab.yangambil_pegawai', 'left');
        $this->filter_daftar($params['start_date'], $params['end_date'], $params['search'], $params['tipe']);
        $total = $this->db->count_all_results();

        $this->select_dasar();
        $this->filter_daftar($params['start_date'], $params['end_date'], $params['search'], $params['tipe']);

        if (!empty($params['order'])) {
            $this->db->order_by($params['order'], $params['dir'] === 'asc' ? 'ASC' : 'DESC');
        } else {
            $this->db->order_by('mp.created', 'DESC');
        }

        if ($params['length'] > 0) {
            $this->db->limit($params['length'], $params['start']);
        }

        return ['total' => $total, 'rows' => $this->db->get()->result_array()];
    }

    /**
     * Jumlah masalah pending di LUAR rentang tanggal yang sedang dilihat, plus
     * tanggal pending tertua. Dipakai peringatan di atas tabel supaya masalah
     * lama tidak terlupa hanya karena filter default-nya "hari ini".
     */
    public function pending_luar_rentang($start_date, $end_date)
    {
        $row = $this->db->query("
            SELECT COUNT(*) AS jumlah, MIN(created) AS tertua
            FROM tblmasalahpicker
            WHERE status = 0 AND (created < ? OR created > ?)
        ", [$start_date, $end_date])->row_array();

        return [
            'jumlah' => (int) ($row['jumlah'] ?? 0),
            'tertua' => $row['tertua'] ?? null,
        ];
    }

    public function detail($id_masalahpicker)
    {
        $this->select_dasar();
        $this->db->where('mp.id_masalahpicker', (int) $id_masalahpicker);
        return $this->db->get()->row_array();
    }

    /**
     * Baris pending yang akan diproses: kalau $ids diisi hanya yang dipilih,
     * kalau kosong semua pending di rentang tanggal. Urut picker lalu rak
     * supaya slip cetaknya enak dipakai jalan.
     */
    public function ambil_pending_untuk_proses($start_date, $end_date, $ids = [])
    {
        $this->select_dasar();
        $this->db->where('mp.status', 0);
        if (!empty($ids)) {
            $this->db->where_in('mp.id_masalahpicker', array_map('intval', $ids));
        } else {
            $this->db->where('mp.created >=', $start_date);
            $this->db->where('mp.created <=', $end_date);
        }
        $this->db->order_by('nama_picker', 'ASC');
        $this->urut_rak_lantai('no_rak');
        $this->db->order_by('mp.created', 'ASC');
        return $this->db->get()->result_array();
    }

    /**
     * Urutan rak untuk slip: lantai 1 -> 2 -> 3 (karakter pertama kode rak,
     * mis. 1B-A1-1, 2A-B6-3, 3C-A2-4), lalu zona/kolom/tingkat -- urut teks
     * biasa sudah tepat untuk format itu. Rak kosong atau '-' ditaruh paling
     * bawah supaya tidak mendahului lantai 1.
     */
    private function urut_rak_lantai($kolom)
    {
        $this->db->order_by("(" . $kolom . " IS NULL OR " . $kolom . " = '' OR " . $kolom . " = '-')", 'ASC', FALSE);
        $this->db->order_by($kolom, 'ASC');
    }

    /**
     * Kelompokkan baris masalah per picker menjadi struktur slip cetak.
     *
     * LEBIH AMBIL sengaja dipisah ke 'tanpa_cetak': tetap diproses, tapi tidak
     * masuk slip karena tidak ada barang yang harus diambil.
     */
    public function kelompokkan_per_picker(array $rows)
    {
        $slips = [];
        $tanpa_cetak = [];

        foreach ($rows as $r) {
            $item = $this->bentuk_item_slip($r);

            if ((int) $r['id_typemasalah'] === self::TIPE_LEBIH_AMBIL) {
                $tanpa_cetak[] = $item;
                continue;
            }

            $kode = $r['kode_picker'] !== null ? (string) $r['kode_picker'] : '0';
            if (!isset($slips[$kode])) {
                $slips[$kode] = [
                    'kode_picker' => $r['kode_picker'] !== null ? (int) $r['kode_picker'] : null,
                    'nama_picker' => $this->nama_picker_tampil($r),
                    'items'       => [],
                ];
            }
            $slips[$kode]['items'][] = $item;
        }

        return ['slips' => array_values($slips), 'tanpa_cetak' => $tanpa_cetak];
    }

    private function bentuk_item_slip(array $r)
    {
        return [
            'id_masalahpicker' => (int) $r['id_masalahpicker'],
            'noresi'           => $r['noresi'],
            'sku'              => $r['sku'],
            'nama_barang'      => $r['nama_barang'],
            'qty_bermasalah'   => (int) $r['qty_bermasalah'],
            'sku_salah'        => $r['sku_salah'],
            'no_rak'           => $r['no_rak'] ?: '-',
            'id_typemasalah'   => (int) $r['id_typemasalah'],
            'type_masalah'     => $r['type_masalah'],
            'nama_packer'      => $r['nama_packer'] ?: '-',
            'kode_picker'      => $r['kode_picker'] !== null ? (int) $r['kode_picker'] : null,
            'nama_picker'      => $this->nama_picker_tampil($r),
        ];
    }

    /**
     * Nama picker untuk slip. Kalau kode pegawainya ada tapi tidak punya baris
     * di tblpegawai maupun tbluser, tampilkan kodenya supaya CS masih bisa
     * menelusuri -- jangan disamakan dengan resi yang belum pernah di-scan
     * ambil sama sekali.
     */
    private function nama_picker_tampil(array $r)
    {
        if (!empty($r['nama_picker'])) {
            return $r['nama_picker'];
        }
        return $r['kode_picker'] !== null ? 'PEGAWAI #' . (int) $r['kode_picker'] : 'PICKER TIDAK TERDETEKSI';
    }

    /**
     * Tandai selesai (status 1) hanya kalau masih 0. Mengembalikan TRUE kalau
     * baris ini yang mengubahnya -- penjaga kalau dua user CS menekan proses
     * bersamaan, supaya satu masalah tidak tercetak di dua slip.
     */
    public function tandai_selesai($id_masalahpicker, $id_user)
    {
        $this->db->where('id_masalahpicker', (int) $id_masalahpicker);
        $this->db->where('status', 0);
        $this->db->update('tblmasalahpicker', [
            'status'     => 1,
            'updated_by' => $id_user,
            'updated'    => date('Y-m-d H:i:s'),
        ]);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Reject Display otomatis masuk antrean QC (tblpengembalian_qc), seperti di
     * menu lama. Bedanya penanda dedupnya per id masalah (ditulis di
     * keterangan_reject), bukan per sku+rak+tanggal: dulu dua reject SKU sama
     * di hari yang sama membuat yang kedua tidak pernah masuk QC.
     * Mengembalikan TRUE kalau baris QC baru dibuat.
     */
    public function kirim_reject_ke_qc(array $item, $id_user)
    {
        $penanda = '[MP#' . (int) $item['id_masalahpicker'] . ']';

        $sudah = $this->db->select('id_pengembalian')
            ->like('keterangan_reject', $penanda)
            ->get('tblpengembalian_qc')->row();
        if ($sudah) {
            return FALSE;
        }

        $this->db->insert('tblpengembalian_qc', [
            'tanggal'           => date('Y-m-d'),
            'sku'               => $item['sku'],
            'qty'               => (int) $item['qty_bermasalah'],
            'no_rak'            => $item['no_rak'] ?: '-',
            'kondisi'           => 'REJECT',
            'keterangan_reject' => 'Reject Display (Auto dari CS) ' . $penanda,
            'submit_by'         => $id_user,
            'status'            => 'PENDING',
        ]);
        return $this->db->affected_rows() > 0;
    }

    // ------------------------------------------------------------------
    // Riwayat proses / cetak ulang
    // ------------------------------------------------------------------

    public function simpan_proses($id_user, $nama_user, array $slips, array $tanpa_cetak)
    {
        $jumlah_item = count($tanpa_cetak);
        foreach ($slips as $s) {
            $jumlah_item += count($s['items']);
        }

        $this->db->insert('tblmasalahpicker_proses', [
            'waktu_proses'  => date('Y-m-d H:i:s'),
            'id_user'       => $id_user,
            'nama_user'     => $nama_user,
            'jumlah_picker' => count($slips),
            'jumlah_item'   => $jumlah_item,
        ]);
        $id_proses = $this->db->insert_id();

        $batch = [];
        foreach ($slips as $s) {
            foreach ($s['items'] as $it) {
                $batch[] = $this->baris_snapshot($id_proses, $it, 1);
            }
        }
        foreach ($tanpa_cetak as $it) {
            $batch[] = $this->baris_snapshot($id_proses, $it, 0);
        }
        if (!empty($batch)) {
            $this->db->insert_batch('tblmasalahpicker_proses_item', $batch);
        }

        return $id_proses;
    }

    private function baris_snapshot($id_proses, array $it, $dicetak)
    {
        return [
            'id_proses'        => $id_proses,
            'id_masalahpicker' => $it['id_masalahpicker'],
            'kode_picker'      => $it['kode_picker'],
            'nama_picker'      => $it['nama_picker'],
            'nama_packer'      => $it['nama_packer'],
            'noresi'           => $it['noresi'],
            'sku'              => $it['sku'],
            'nama_barang'      => $it['nama_barang'],
            'sku_salah'        => $it['sku_salah'],
            'qty_bermasalah'   => $it['qty_bermasalah'],
            'no_rak'           => $it['no_rak'],
            'id_typemasalah'   => $it['id_typemasalah'],
            'type_masalah'     => $it['type_masalah'],
            'dicetak'          => $dicetak,
        ];
    }

    public function riwayat_proses($limit = 30)
    {
        return $this->db->query("
            SELECT p.id_proses, p.waktu_proses, p.nama_user, p.jumlah_picker, p.jumlah_item,
                   GROUP_CONCAT(DISTINCT CASE WHEN i.dicetak = 1 THEN i.nama_picker END
                                ORDER BY i.nama_picker SEPARATOR ', ') AS daftar_picker
            FROM tblmasalahpicker_proses p
            LEFT JOIN tblmasalahpicker_proses_item i ON i.id_proses = p.id_proses
            GROUP BY p.id_proses
            ORDER BY p.id_proses DESC
            LIMIT " . (int) $limit)->result_array();
    }

    public function proses_header($id_proses)
    {
        return $this->db->get_where('tblmasalahpicker_proses', ['id_proses' => (int) $id_proses])->row_array();
    }

    /** Bangun ulang struktur slip dari snapshot -- untuk cetak ulang. */
    public function slip_dari_snapshot($id_proses, $kode_picker = null)
    {
        $this->db->from('tblmasalahpicker_proses_item');
        $this->db->where('id_proses', (int) $id_proses);
        $this->db->where('dicetak', 1);
        if ($kode_picker !== null && $kode_picker !== '') {
            if ((int) $kode_picker === 0) {
                $this->db->where('kode_picker IS NULL', NULL, FALSE);
            } else {
                $this->db->where('kode_picker', (int) $kode_picker);
            }
        }
        $this->db->order_by('nama_picker', 'ASC');
        $this->urut_rak_lantai('no_rak');
        $this->db->order_by('id_proses_item', 'ASC');
        $rows = $this->db->get()->result_array();

        $slips = [];
        foreach ($rows as $r) {
            $kode = $r['kode_picker'] !== null ? (string) $r['kode_picker'] : '0';
            if (!isset($slips[$kode])) {
                $slips[$kode] = [
                    'kode_picker' => $r['kode_picker'] !== null ? (int) $r['kode_picker'] : null,
                    'nama_picker' => $r['nama_picker'],
                    'items'       => [],
                ];
            }
            $slips[$kode]['items'][] = [
                'id_masalahpicker' => (int) $r['id_masalahpicker'],
                'noresi'           => $r['noresi'],
                'sku'              => $r['sku'],
                'nama_barang'      => $r['nama_barang'],
                'qty_bermasalah'   => (int) $r['qty_bermasalah'],
                'sku_salah'        => $r['sku_salah'],
                'no_rak'           => $r['no_rak'],
                'id_typemasalah'   => (int) $r['id_typemasalah'],
                'type_masalah'     => $r['type_masalah'],
                'nama_packer'      => $r['nama_packer'],
                'kode_picker'      => $r['kode_picker'] !== null ? (int) $r['kode_picker'] : null,
                'nama_picker'      => $r['nama_picker'],
            ];
        }

        return array_values($slips);
    }
}
