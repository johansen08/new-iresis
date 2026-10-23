<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Stok top level: sisa barang dari restok yang tidak muat di rak display dan disimpan di rak lain.
 * Alur lengkap, status, dan aturan ada di docs/TOP_LEVEL.md.
 *
 * Tabel (dibuat MY_Controller::run_top_level_migration):
 *   tbltoplevel               stok per SKU per rak (baris yang qty-nya 0 tidak dihapus, hanya disembunyikan)
 *   tbltoplevel_request       request restok display (DIAJUKAN, DITUGASKAN, SELESAI, BATAL)
 *   tbltoplevel_request_resi  resi yang menunggu request itu
 *   tbltoplevel_import(_baris) riwayat upload Excel dari tim Restok
 *   tbltoplevel_transfer(_baris) nomor transfer TTL-TAHUN-NNNNN untuk setiap stok masuk dan keluar
 *   tbltoplevel_nomor         penghitung urutan nomor transfer per tahun
 *   tbltoplevel_log           jejak audit; belum punya tampilan web
 *
 * Tidak ada DELETE di model ini: stok yang habis cukup berqty 0, request yang batal berstatus BATAL.
 */
class Top_level_fcd extends CI_Model
{
    const AKTIF = array('DIAJUKAN', 'DITUGASKAN');

    /** Batas baris Excel per unggahan. */
    const MAKS_BARIS = 5000;

    public static function kode($id)
    {
        return 'RT-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    public static function kode_import($id)
    {
        return 'UP-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    /** Id dari 'RT-0007' atau '7'; 0 bila tidak terbaca. */
    public static function id_dari_kode($kode)
    {
        return (int) preg_replace('/\D/', '', (string) $kode);
    }

    public function catat($jenis, $sku, $rak, $sebelum, $sesudah, $oleh, $ref, $keterangan)
    {
        $this->db->insert('tbltoplevel_log', array(
            'waktu'       => date('Y-m-d H:i:s'),
            'jenis'       => $jenis,
            'sku'         => $sku,
            'no_rak'      => $rak,
            'qty_sebelum' => $sebelum,
            'qty_sesudah' => $sesudah,
            'oleh'        => $oleh,
            'ref'         => $ref,
            'keterangan'  => mb_substr((string) $keterangan, 0, 500),
        ));
    }

    public static function kode_transfer($tahun, $urut)
    {
        return 'TTL-' . $tahun . '-' . str_pad((string) $urut, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Nomor transfer top level (TTL-TAHUN-NNNNN): satu nomor untuk setiap perpindahan stok, masuk maupun keluar.
     * Urutan per tahun diambil dari tbltoplevel_nomor DI DALAM transaksi pemanggil. Baris penghitungnya terkunci
     * sampai commit, jadi dua transaksi bersamaan tidak pernah mendapat nomor yang sama, dan rollback tidak
     * meninggalkan lubang nomor.
     *
     * @param string $arah   MASUK atau KELUAR
     * @param string $sumber INPUT, UPLOAD, REQUEST, atau KOREKSI
     * @param array  $baris  [['sku'=>, 'rak'=>, 'qty'=>, 'sebelum'=>, 'sesudah'=>], ...]
     * @return string nomor transfer
     */
    private function buat_transfer($arah, $sumber, $nama_tim, $ref, $oleh, array $baris)
    {
        $tahun = (int) date('Y');
        $this->db->query('INSERT IGNORE INTO tbltoplevel_nomor (tahun, urut) VALUES (?, 0)', array($tahun));
        $this->db->query('UPDATE tbltoplevel_nomor SET urut = LAST_INSERT_ID(urut + 1) WHERE tahun = ?', array($tahun));
        $urut = (int) $this->db->query('SELECT LAST_INSERT_ID() AS n')->row()->n;
        $nomor = self::kode_transfer($tahun, $urut);

        $qty = 0;
        foreach ($baris as $b) {
            $qty += $b['qty'];
        }
        $this->db->insert('tbltoplevel_transfer', array(
            'nomor' => $nomor, 'tahun' => $tahun, 'urut' => $urut, 'arah' => $arah, 'sumber' => $sumber,
            'nama_tim' => $nama_tim !== null ? mb_substr($nama_tim, 0, 150) : null, 'ref' => $ref,
            'dibuat_at' => date('Y-m-d H:i:s'), 'dibuat_by' => $oleh, 'jumlah_baris' => count($baris), 'jumlah_qty' => $qty,
        ));
        $id = (int) $this->db->insert_id();
        foreach ($baris as $b) {
            $this->db->insert('tbltoplevel_transfer_baris', array(
                'id_transfer' => $id, 'sku' => $b['sku'], 'no_rak' => $b['rak'], 'qty' => $b['qty'],
                'qty_sebelum' => $b['sebelum'], 'qty_sesudah' => $b['sesudah'],
            ));
        }
        $this->catat('TRANSFER', null, null, null, null, $oleh, $nomor, 'Transfer ' . strtolower($arah) . ' ' . $nomor . ' (' . $sumber . ($ref ? ' ' . $ref : '') . '), ' . count($baris) . ' baris, ' . $qty . ' pcs');
        return $nomor;
    }

    // ------------------------------------------------------------ CS: Input Top Level

    /** Rekomendasi nama tim Restok: yang terakhir dipakai dulu, lalu pegawai aktif yang cocok dengan ketikan. */
    public function rekomendasi_tim($q)
    {
        $q = trim((string) $q);
        $hasil = array();
        $pakai = function ($nama, $dari) use (&$hasil) {
            $nama = trim((string) $nama);
            if ($nama !== '' && !isset($hasil[mb_strtolower($nama)])) {
                $hasil[mb_strtolower($nama)] = array('nama' => $nama, 'dari' => $dari);
            }
        };

        $this->db->select('nama_tim')->from('tbltoplevel_transfer')->where('nama_tim IS NOT NULL', null, false)->where("nama_tim <> ''", null, false);
        if ($q !== '') {
            $this->db->like('nama_tim', $q);
        }
        $terakhir = $this->db->group_by('nama_tim')->order_by('MAX(id)', 'DESC', false)->limit(8)->get()->result();
        foreach ($terakhir as $r) {
            $pakai($r->nama_tim, 'terakhir');
        }

        if ($q !== '') {
            $this->db->select('nama_pegawai')->from('tblpegawai')->where('status_aktif', 'AKTIF')->like('nama_pegawai', $q);
            foreach ($this->db->order_by('nama_pegawai', 'ASC')->limit(10)->get()->result() as $r) {
                $pakai($r->nama_pegawai, 'pegawai');
            }
        }
        return array_slice(array_values($hasil), 0, 10);
    }

    /** Saran SKU saat mengetik: awalan kode atau bagian nama, beserta rak display. */
    public function cari_sku($q)
    {
        $q = trim((string) $q);
        if ($q === '') {
            return array();
        }
        $like = '%' . $this->db->escape_like_str($q) . '%';
        return $this->db->query(
            'SELECT id_sku AS sku, COALESCE(nama_sku, "") AS nama, COALESCE(NULLIF(TRIM(no_rak), ""), "-") AS rak
             FROM tblsku WHERE id_sku LIKE ? OR nama_sku LIKE ?
             ORDER BY (id_sku LIKE ?) DESC, id_sku ASC LIMIT 8',
            array($q . '%', $like, $q . '%')
        )->result_array();
    }

    /**
     * Input stok top level manual oleh CS: qty ditambahkan ke rak (rak baru dibuat) dan dicatat sebagai
     * satu transfer MASUK dengan nomor TTL.
     *
     * @param array $baris [['sku'=>, 'rak'=>, 'qty'=>], ...]
     */
    public function input_masuk($nama_tim, array $baris, $oleh)
    {
        $nama_tim = trim(preg_replace('/\s+/', ' ', (string) $nama_tim));
        if ($nama_tim === '') {
            return array('ok' => false, 'pesan' => 'Nama tim Restok wajib diisi.');
        }
        $bersih = array();
        $skus = array();
        foreach ($baris as $i => $b) {
            $sku = trim((string) (isset($b['sku']) ? $b['sku'] : ''));
            $rak = strtoupper(trim((string) (isset($b['rak']) ? $b['rak'] : '')));
            $qty = isset($b['qty']) ? $b['qty'] : '';
            if ($sku === '' && $rak === '' && $qty === '') {
                continue;
            }
            $no = $i + 1;
            if ($sku === '' || $rak === '') {
                return array('ok' => false, 'pesan' => 'Baris ' . $no . ': SKU dan no rak wajib diisi.');
            }
            if (!is_numeric($qty) || (int) $qty != $qty || $qty <= 0) {
                return array('ok' => false, 'pesan' => 'Baris ' . $no . ': qty harus bilangan bulat lebih dari 0.');
            }
            $kunci_baris = $sku . '|' . $rak;
            if (isset($bersih[$kunci_baris])) {
                return array('ok' => false, 'pesan' => 'Baris ' . $no . ': SKU dan rak yang sama sudah ada di baris lain.');
            }
            $bersih[$kunci_baris] = array('sku' => $sku, 'rak' => $rak, 'qty' => (int) $qty, 'baris' => $no);
            $skus[$sku] = true;
        }
        if (!$bersih) {
            return array('ok' => false, 'pesan' => 'Isi minimal satu baris.');
        }
        if (count($bersih) > 200) {
            return array('ok' => false, 'pesan' => 'Maksimal 200 baris per transfer.');
        }
        $ada = array();
        foreach ($this->db->select('id_sku')->where_in('id_sku', array_keys($skus))->get('tblsku')->result() as $r) {
            $ada[$r->id_sku] = true;
        }
        foreach ($bersih as $b) {
            if (!isset($ada[$b['sku']])) {
                return array('ok' => false, 'pesan' => 'Baris ' . $b['baris'] . ': SKU ' . $b['sku'] . ' tidak ada di master.');
            }
        }

        $this->db->trans_start();
        $lines = array();
        foreach ($bersih as $b) {
            $row = $this->db->query('SELECT qty FROM tbltoplevel WHERE sku = ? AND no_rak = ? FOR UPDATE', array($b['sku'], $b['rak']))->row();
            $lama = $row ? (int) $row->qty : 0;
            $this->db->query(
                'INSERT INTO tbltoplevel (sku, no_rak, qty, updated_at) VALUES (?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty), updated_at = VALUES(updated_at)',
                array($b['sku'], $b['rak'], $b['qty'])
            );
            $lines[] = array('sku' => $b['sku'], 'rak' => $b['rak'], 'qty' => $b['qty'], 'sebelum' => $lama, 'sesudah' => $lama + $b['qty']);
            $this->catat('INPUT', $b['sku'], $b['rak'], $lama, $lama + $b['qty'], $oleh, null, 'Input top level oleh tim ' . $nama_tim);
        }
        $nomor = $this->buat_transfer('MASUK', 'INPUT', $nama_tim, null, $oleh, $lines);
        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            return array('ok' => false, 'pesan' => 'Penyimpanan gagal; tidak ada stok yang berubah.');
        }
        $qty = 0;
        foreach ($lines as $l) $qty += $l['qty'];
        return array('ok' => true, 'nomor' => $nomor, 'baris' => count($lines), 'qty' => $qty);
    }

    // ------------------------------------------------------------ Picker: Cek Top Level

    /**
     * Resi (dari nomor resi atau nomor pesanan) beserta SKU-nya dan request top level yang menempel.
     * NULL bila tidak ditemukan.
     */
    public function cari_resi($keyword)
    {
        $keyword = trim((string) $keyword);
        if ($keyword === '') {
            return null;
        }
        pastikan_resi_live($keyword); // resi lama yang sudah diarsipkan ditarik dulu ke prod (docs/ARSIP_DATA.md)

        $sql = 'SELECT t.id_printresi, t.noresi, m.nama_marketplace
                FROM tblprintresi t
                LEFT JOIN tblmarketplace m ON m.id_marketplace = t.id_marketplace';
        $resi = $this->db->query($sql . ' WHERE t.noresi = ? ORDER BY t.created_at DESC LIMIT 1', array($keyword))->row_array();
        if (!$resi) {
            $resi = $this->db->query(
                $sql . ' INNER JOIN tbldetailprintresi d ON d.id_resi = t.id_printresi
                         WHERE d.no_pesanan = ? ORDER BY t.created_at DESC LIMIT 1',
                array($keyword)
            )->row_array();
        }
        if (!$resi) {
            return null;
        }

        $items = $this->db->query(
            'SELECT d.sku, MAX(COALESCE(NULLIF(TRIM(s.no_rak), ""), "BELUM DITENTUKAN")) AS no_rak
             FROM tbldetailprintresi d
             LEFT JOIN tblsku s ON s.id_sku = d.sku
             WHERE d.id_resi = ?
             GROUP BY d.sku
             ORDER BY MIN(d.id_detail_resi)',
            array((int) $resi['id_printresi'])
        )->result_array();

        foreach ($items as &$it) {
            $r = $this->request_resi_sku($resi['noresi'], $it['sku']);
            $it['request'] = $r ? array('id' => (int) $r['id'], 'kode' => self::kode($r['id']), 'status' => $r['status']) : null;
        }
        unset($it);

        return array('noresi' => $resi['noresi'], 'mp' => (string) $resi['nama_marketplace'], 'items' => $items);
    }

    /** Request terakhir yang memuat resi dan SKU ini (apa pun statusnya). */
    private function request_resi_sku($noresi, $sku)
    {
        return $this->db->query(
            'SELECT r.id, r.status
             FROM tbltoplevel_request r
             INNER JOIN tbltoplevel_request_resi x ON x.id_request = r.id
             WHERE x.noresi = ? AND r.sku = ?
             ORDER BY r.id DESC LIMIT 1',
            array($noresi, $sku)
        )->row_array();
    }

    public function total_stok($sku)
    {
        $row = $this->db->query('SELECT COALESCE(SUM(qty), 0) AS n FROM tbltoplevel WHERE sku = ?', array($sku))->row();
        return (int) $row->n;
    }

    private function request_aktif($sku)
    {
        return $this->db->query(
            "SELECT id, status FROM tbltoplevel_request WHERE sku = ? AND status IN ('DIAJUKAN','DITUGASKAN') ORDER BY id DESC LIMIT 1",
            array($sku)
        )->row_array();
    }

    /** Hasil klik Cek: ada stok top level atau tidak, dan apakah sudah ada request aktif untuk SKU itu. */
    public function cek_sku($sku, $noresi, $oleh)
    {
        $total = $this->total_stok($sku);
        $aktif = $this->request_aktif($sku);
        if ($total <= 0) {
            $this->catat('CEK_KOSONG', $sku, null, null, null, $oleh, $noresi, 'Cek top level: tidak ada stok');
        }

        $joined = false;
        if ($aktif) {
            $joined = $this->db->where(array('id_request' => (int) $aktif['id'], 'noresi' => $noresi))
                ->count_all_results('tbltoplevel_request_resi') > 0;
        }

        return array(
            'ada_stok' => $total > 0,
            'aktif'    => $aktif ? array('id' => (int) $aktif['id'], 'kode' => self::kode($aktif['id']), 'status' => $aktif['status']) : null,
            'joined'   => $joined,
        );
    }

    /** Kunci per SKU supaya dua klik bersamaan tidak membuat dua request aktif. */
    private function kunci($sku)
    {
        $nama = 'toplevel_' . md5($sku);
        $r = $this->db->query('SELECT GET_LOCK(?, 5) AS k', array($nama))->row();
        return ($r && (int) $r->k === 1) ? $nama : null;
    }

    private function lepas($nama)
    {
        if ($nama) {
            $this->db->query('SELECT RELEASE_LOCK(?)', array($nama));
        }
    }

    /** @return array ['ok'=>bool, 'pesan'=>string, 'kode'=>string] */
    public function buat_request($sku, $noresi, $oleh, $asal = 'PICKER')
    {
        $kunci = $this->kunci($sku);
        if (!$kunci) {
            return array('ok' => false, 'pesan' => 'Sistem sibuk, coba lagi.');
        }
        try {
            if ($this->total_stok($sku) <= 0) {
                return array('ok' => false, 'pesan' => 'Tidak ada stok top level untuk SKU ini.');
            }
            $aktif = $this->request_aktif($sku);
            if ($aktif) {
                return array('ok' => false, 'pesan' => 'Sudah ada request ' . self::kode($aktif['id']) . ' untuk SKU ini.', 'aktif' => (int) $aktif['id']);
            }

            $now = date('Y-m-d H:i:s');
            $this->db->trans_start();
            $this->db->insert('tbltoplevel_request', array(
                'sku' => $sku, 'asal' => $asal, 'status' => 'DIAJUKAN', 'request_at' => $now, 'request_by' => $oleh,
            ));
            $id = (int) $this->db->insert_id();
            if ($noresi !== '') {
                $this->db->insert('tbltoplevel_request_resi', array('id_request' => $id, 'noresi' => $noresi, 'created_at' => $now));
            }
            $this->catat('REQUEST', $sku, null, null, null, $oleh, self::kode($id), 'Request restok display ' . $now . ($noresi !== '' ? ' · resi ' . $noresi : ''));
            $this->db->trans_complete();

            if ($this->db->trans_status() === false) {
                return array('ok' => false, 'pesan' => 'Gagal menyimpan request.');
            }
            return array('ok' => true, 'kode' => self::kode($id), 'id' => $id);
        } finally {
            $this->lepas($kunci);
        }
    }

    public function gabung($id, $noresi, $oleh)
    {
        $r = $this->db->get_where('tbltoplevel_request', array('id' => (int) $id))->row_array();
        if (!$r || !in_array($r['status'], self::AKTIF, true)) {
            return array('ok' => false, 'pesan' => 'Request sudah tidak aktif.');
        }
        $sudah = $this->db->where(array('id_request' => (int) $id, 'noresi' => $noresi))->count_all_results('tbltoplevel_request_resi');
        if ($sudah > 0) {
            return array('ok' => true, 'kode' => self::kode($id));
        }
        $this->db->insert('tbltoplevel_request_resi', array('id_request' => (int) $id, 'noresi' => $noresi, 'created_at' => date('Y-m-d H:i:s')));
        $this->catat('REQUEST', $r['sku'], null, null, null, $oleh, self::kode($id), 'Resi ' . $noresi . ' digabung');
        return array('ok' => true, 'kode' => self::kode($id));
    }

    /** Hanya request yang belum dicetak yang boleh dibatalkan. */
    public function batal($id, $oleh)
    {
        $r = $this->db->get_where('tbltoplevel_request', array('id' => (int) $id))->row_array();
        if (!$r) {
            return array('ok' => false, 'pesan' => 'Request tidak ditemukan.');
        }
        $this->db->where(array('id' => (int) $id, 'status' => 'DIAJUKAN'))
            ->update('tbltoplevel_request', array('status' => 'BATAL', 'batal_at' => date('Y-m-d H:i:s')));
        if ($this->db->affected_rows() < 1) {
            return array('ok' => false, 'pesan' => 'Request sudah dicetak, tidak bisa dibatalkan.');
        }
        $this->catat('REQUEST', $r['sku'], null, null, null, $oleh, self::kode($id), 'Request dibatalkan');
        return array('ok' => true);
    }

    /** Request terbaru yang punya resi, untuk daftar di halaman picker. */
    public function terbaru($batas = 6)
    {
        $rows = $this->db->query(
            "SELECT r.id, r.sku, r.status,
                    (SELECT GROUP_CONCAT(noresi ORDER BY id SEPARATOR ', ') FROM tbltoplevel_request_resi WHERE id_request = r.id) AS resi
             FROM tbltoplevel_request r
             WHERE r.status <> 'BATAL' AND EXISTS (SELECT 1 FROM tbltoplevel_request_resi x WHERE x.id_request = r.id)
             ORDER BY r.id DESC LIMIT " . (int) $batas
        )->result_array();
        foreach ($rows as &$r) {
            $r['kode'] = self::kode($r['id']);
        }
        return $rows;
    }

    // ------------------------------------------------------------ CS: Request Top Level

    /** Hitungan per kelompok tab: aktif, cetak (DIAJUKAN), restok (DITUGASKAN), selesai (SELESAI + BATAL). */
    public function hitung_request()
    {
        $h = array('aktif' => 0, 'cetak' => 0, 'restok' => 0, 'selesai' => 0);
        foreach ($this->db->query('SELECT status, COUNT(*) AS n FROM tbltoplevel_request GROUP BY status')->result() as $r) {
            if ($r->status === 'DIAJUKAN')   { $h['cetak'] += $r->n; $h['aktif'] += $r->n; }
            if ($r->status === 'DITUGASKAN') { $h['restok'] += $r->n; $h['aktif'] += $r->n; }
            if ($r->status === 'SELESAI' || $r->status === 'BATAL') { $h['selesai'] += $r->n; }
        }
        return $h;
    }

    public function daftar_request($filter, $cari)
    {
        $peta = array(
            'aktif'   => array('DIAJUKAN', 'DITUGASKAN'),
            'cetak'   => array('DIAJUKAN'),
            'restok'  => array('DITUGASKAN'),
            'selesai' => array('SELESAI', 'BATAL'),
        );
        $status = isset($peta[$filter]) ? $peta[$filter] : $peta['aktif'];

        $this->db->select("r.id, r.sku, r.asal, r.status, r.request_at, r.cetak_at, r.cetak_ke, r.selesai_at,
            (SELECT GROUP_CONCAT(noresi ORDER BY id SEPARATOR ', ') FROM tbltoplevel_request_resi WHERE id_request = r.id) AS resi", false);
        $this->db->from('tbltoplevel_request r');
        $this->db->where_in('r.status', $status);
        $cari = trim((string) $cari);
        if ($cari !== '') {
            $id = self::id_dari_kode($cari);
            $this->db->group_start()
                ->like('r.sku', $cari)
                ->or_where('EXISTS (SELECT 1 FROM tbltoplevel_request_resi x WHERE x.id_request = r.id AND x.noresi LIKE ' . $this->db->escape('%' . $this->db->escape_like_str($cari) . '%') . ')', null, false);
            if ($id > 0) {
                $this->db->or_where('r.id', $id);
            }
            $this->db->group_end();
        }
        $rows = $this->db->order_by('r.id', 'DESC')->limit(500)->get()->result_array();
        foreach ($rows as &$r) {
            $r['kode'] = self::kode($r['id']);
        }
        return $rows;
    }

    /** Rak yang berstok untuk satu SKU, urut nomor rak. */
    public function rak_sku($sku)
    {
        return $this->db->query('SELECT no_rak AS rak, qty AS stok FROM tbltoplevel WHERE sku = ? AND qty > 0 ORDER BY no_rak', array($sku))->result_array();
    }

    /** Nomor transfer keluar milik request ini ('' bila belum selesai). */
    private function nomor_request($id)
    {
        $r = $this->db->select('nomor')->where(array('sumber' => 'REQUEST', 'ref' => self::kode($id)))->get('tbltoplevel_transfer')->row();
        return $r ? $r->nomor : '';
    }

    public function detail_request($id)
    {
        $r = $this->db->get_where('tbltoplevel_request', array('id' => (int) $id))->row_array();
        if (!$r) {
            return null;
        }
        $resi = array();
        foreach ($this->db->query('SELECT noresi FROM tbltoplevel_request_resi WHERE id_request = ? ORDER BY id', array((int) $id))->result() as $x) {
            $resi[] = $x->noresi;
        }
        $slip = $r['slip'] !== null ? json_decode($r['slip'], true) : null;
        $rak_display = $this->db->query('SELECT COALESCE(NULLIF(TRIM(no_rak), ""), "-") AS v FROM tblsku WHERE id_sku = ?', array($r['sku']))->row();

        return array(
            'id'          => (int) $r['id'],
            'kode'        => self::kode($r['id']),
            'sku'         => $r['sku'],
            'asal'        => $r['asal'],
            'status'      => $r['status'],
            'request_at'  => $r['request_at'],
            'cetak_at'    => $r['cetak_at'],
            'cetak_ke'    => (int) $r['cetak_ke'],
            'selesai_at'  => $r['selesai_at'],
            'batal_at'    => $r['batal_at'],
            'resi'        => $resi,
            'rak_display' => $rak_display ? $rak_display->v : '-',
            'rak'         => is_array($slip) ? $slip : $this->rak_sku($r['sku']),
            'hasil'       => $r['hasil'] !== null ? json_decode($r['hasil'], true) : null,
            'transfer'    => $this->nomor_request($r['id']),
        );
    }

    /**
     * Mencetak slip. Cetak pertama mengubah status ke DITUGASKAN dan membekukan daftar rak di slip;
     * cetak berikutnya hanya menambah hitungan cetak.
     */
    public function cetak($id, $oleh)
    {
        $r = $this->db->get_where('tbltoplevel_request', array('id' => (int) $id))->row_array();
        if (!$r || !in_array($r['status'], self::AKTIF, true)) {
            return array('ok' => false, 'pesan' => 'Request sudah tidak aktif.');
        }
        $now = date('Y-m-d H:i:s');
        if ($r['cetak_at'] === null) {
            $rak = $this->rak_sku($r['sku']);
            if (!$rak) {
                return array('ok' => false, 'pesan' => 'Stok top level SKU ini sudah habis.');
            }
            $this->db->where('id', (int) $id)->update('tbltoplevel_request', array(
                'status' => 'DITUGASKAN', 'cetak_at' => $now, 'cetak_ke' => 1, 'cetak_by' => $oleh, 'slip' => json_encode($rak),
            ));
            $this->catat('SLIP', $r['sku'], null, null, null, $oleh, self::kode($id), 'Slip dicetak ' . $now);
        } else {
            $this->db->set('cetak_ke', 'cetak_ke + 1', false)->where('id', (int) $id)->update('tbltoplevel_request');
            $this->catat('SLIP', $r['sku'], null, null, null, $oleh, self::kode($id), 'Slip dicetak ulang ' . $now);
        }
        return array('ok' => true);
    }

    /**
     * Input qty dari slip yang sudah kembali: qty tiap rak dikurangi, stok mengikuti sisa fisik rak,
     * status langsung SELESAI.
     *
     * @param array $rows  [['rak'=>, 'amb'=>qty diambil, 'sisa'=>sisa fisik rak], ...]
     */
    public function selesai($id, array $rows, $oleh)
    {
        $r = $this->db->get_where('tbltoplevel_request', array('id' => (int) $id))->row_array();
        if (!$r || $r['status'] !== 'DITUGASKAN') {
            return array('ok' => false, 'pesan' => 'Request belum dicetak atau sudah selesai.');
        }
        $sku = $r['sku'];
        $kode = self::kode($id);

        $kunci = $this->kunci($sku);
        if (!$kunci) {
            return array('ok' => false, 'pesan' => 'Sistem sibuk, coba lagi.');
        }
        try {
            $this->db->trans_start();
            $total = 0;
            $hasil = array();
            $trf = array();
            $seen = array();
            foreach ($rows as $x) {
                $rak  = strtoupper(trim((string) (isset($x['rak']) ? $x['rak'] : '')));
                $amb  = isset($x['amb']) && $x['amb'] !== '' ? $x['amb'] : 0;
                $sisa = isset($x['sisa']) ? $x['sisa'] : '';
                if ($rak === '' || isset($seen[$rak])) {
                    $this->db->trans_rollback();
                    return array('ok' => false, 'pesan' => 'Rak tidak valid atau dobel.');
                }
                $seen[$rak] = true;
                if (!is_numeric($amb) || (int) $amb != $amb || $amb < 0 || !is_numeric($sisa) || (int) $sisa != $sisa || $sisa < 0) {
                    $this->db->trans_rollback();
                    return array('ok' => false, 'pesan' => 'Qty rak ' . $rak . ' harus bilangan bulat 0 atau lebih.');
                }
                $amb = (int) $amb;
                $sisa = (int) $sisa;
                $row = $this->db->query('SELECT qty FROM tbltoplevel WHERE sku = ? AND no_rak = ? FOR UPDATE', array($sku, $rak))->row();
                if (!$row) {
                    $this->db->trans_rollback();
                    return array('ok' => false, 'pesan' => 'Rak ' . $rak . ' tidak punya stok SKU ini.');
                }
                $sys = (int) $row->qty;
                if ($amb > $sys) {
                    $this->db->trans_rollback();
                    return array('ok' => false, 'pesan' => 'Qty diambil rak ' . $rak . ' melebihi stok sistem (' . $sys . ').');
                }
                if ($amb === 0 && $sisa === $sys) {
                    continue; // rak tidak disentuh
                }
                $total += $amb;
                $selisih = $sisa - ($sys - $amb);
                $this->db->where(array('sku' => $sku, 'no_rak' => $rak))->update('tbltoplevel', array('qty' => $sisa, 'updated_at' => date('Y-m-d H:i:s')));
                $this->catat('POSTING', $sku, $rak, $sys, $sisa, $oleh, $kode, 'Diambil ' . $amb . ' dari slip');
                if ($selisih !== 0) {
                    $this->catat('SELISIH', $sku, $rak, $sys - $amb, $sisa, $oleh, $kode, 'Sisa fisik ' . ($selisih > 0 ? '+' : '') . $selisih . ' dari hitungan sistem, stok mengikuti fisik');
                }
                $hasil[] = array('rak' => $rak, 'amb' => $amb, 'sisa' => $sisa);
                if ($amb > 0) {
                    $trf[] = array('sku' => $sku, 'rak' => $rak, 'qty' => $amb, 'sebelum' => $sys, 'sesudah' => $sisa);
                }
            }
            if ($total <= 0) {
                $this->db->trans_rollback();
                return array('ok' => false, 'pesan' => 'Isi qty yang diambil.');
            }
            $nomor = $this->buat_transfer('KELUAR', 'REQUEST', null, $kode, $oleh, $trf);
            $this->db->where('id', (int) $id)->update('tbltoplevel_request', array(
                'status' => 'SELESAI', 'selesai_at' => date('Y-m-d H:i:s'), 'selesai_by' => $oleh, 'hasil' => json_encode($hasil),
            ));
            $this->catat('REQUEST', $sku, null, null, null, $oleh, $kode, 'Selesai, diambil ' . $total . ' pcs');
            $this->db->trans_complete();
            if ($this->db->trans_status() === false) {
                return array('ok' => false, 'pesan' => 'Penyimpanan gagal; tidak ada stok yang berubah.');
            }
            return array('ok' => true, 'total' => $total, 'nomor' => $nomor);
        } finally {
            $this->lepas($kunci);
        }
    }

    // ------------------------------------------------------------ CS: Stok Top Level

    public function stok_list($cari)
    {
        $this->db->select('sku, SUM(qty) AS total', false)->from('tbltoplevel')->group_by('sku');
        $cari = trim((string) $cari);
        if ($cari !== '') {
            $this->db->like('sku', $cari);
        }
        $rows = $this->db->order_by('sku', 'ASC')->limit(1000)->get()->result_array();
        foreach ($rows as &$r) {
            $r['total'] = (int) $r['total'];
        }
        return $rows;
    }

    /**
     * Koreksi stok per rak (juga rak baru). Baris dengan qty yang sama dilewati; qty 0 menyembunyikan rak.
     *
     * @param array $rows [['rak'=>, 'qty'=>], ...]
     */
    public function koreksi($sku, array $rows, $alasan, $oleh)
    {
        if (!in_array($alasan, array('Opname', 'Salah catat', 'Barang rusak', 'Pindah rak'), true)) {
            return array('ok' => false, 'pesan' => 'Alasan tidak valid.');
        }
        $ada = $this->db->query('SELECT 1 AS n FROM tblsku WHERE id_sku = ? LIMIT 1', array($sku))->row();
        if (!$ada) {
            return array('ok' => false, 'pesan' => 'SKU tidak ada di master.');
        }
        $kunci = $this->kunci($sku);
        if (!$kunci) {
            return array('ok' => false, 'pesan' => 'Sistem sibuk, coba lagi.');
        }
        try {
            $this->db->trans_start();
            $berubah = 0;
            $masuk = array();
            $keluar = array();
            $seen = array();
            foreach ($rows as $x) {
                $rak = strtoupper(trim((string) (isset($x['rak']) ? $x['rak'] : '')));
                $qty = isset($x['qty']) ? $x['qty'] : '';
                if ($rak === '' && $qty === '') {
                    continue;
                }
                if ($rak === '' || isset($seen[$rak])) {
                    $this->db->trans_rollback();
                    return array('ok' => false, 'pesan' => 'No rak kosong atau dobel.');
                }
                $seen[$rak] = true;
                if (!is_numeric($qty) || (int) $qty != $qty || $qty < 0) {
                    $this->db->trans_rollback();
                    return array('ok' => false, 'pesan' => 'Qty rak ' . $rak . ' harus bilangan bulat 0 atau lebih.');
                }
                $qty = (int) $qty;
                $row = $this->db->query('SELECT qty FROM tbltoplevel WHERE sku = ? AND no_rak = ? FOR UPDATE', array($sku, $rak))->row();
                $lama = $row ? (int) $row->qty : 0;
                if ($lama === $qty) {
                    continue;
                }
                $this->db->query(
                    'INSERT INTO tbltoplevel (sku, no_rak, qty, updated_at) VALUES (?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE qty = VALUES(qty), updated_at = VALUES(updated_at)',
                    array($sku, $rak, $qty)
                );
                $this->catat('KOREKSI', $sku, $rak, $lama, $qty, $oleh, null, 'Koreksi stok (' . $alasan . ')');
                $baris_trf = array('sku' => $sku, 'rak' => $rak, 'qty' => abs($qty - $lama), 'sebelum' => $lama, 'sesudah' => $qty);
                if ($qty > $lama) { $masuk[] = $baris_trf; } else { $keluar[] = $baris_trf; }
                $berubah++;
            }
            if ($berubah === 0) {
                $this->db->trans_rollback();
                return array('ok' => false, 'pesan' => 'Tidak ada perubahan.');
            }
            // Kenaikan dan penurunan stok masing-masing mendapat nomor transfer sendiri.
            $nomor = array();
            if ($masuk)  { $nomor[] = $this->buat_transfer('MASUK', 'KOREKSI', null, $alasan, $oleh, $masuk); }
            if ($keluar) { $nomor[] = $this->buat_transfer('KELUAR', 'KOREKSI', null, $alasan, $oleh, $keluar); }
            $this->db->trans_complete();
            if ($this->db->trans_status() === false) {
                return array('ok' => false, 'pesan' => 'Penyimpanan gagal.');
            }
            return array('ok' => true, 'berubah' => $berubah, 'nomor' => $nomor);
        } finally {
            $this->lepas($kunci);
        }
    }

    // ------------------------------------------------------------ CS: Upload Top Level

    public static function hash_baris(array $b)
    {
        return sha1(implode('|', array($b['sku'], $b['rak'], $b['qty'], $b['ac_list'], $b['tgl_list'], $b['ac_input'], $b['tgl_input'])));
    }

    /**
     * Beri status tiap baris hasil baca Excel: ok, err, atau dup, plus catatan.
     * Dicek: SKU ada di master, rak terisi, qty bulat > 0, dobel di file, dan sudah pernah diimpor (hash baris).
     */
    public function periksa_baris(array $baris)
    {
        $sku_semua = array();
        $hash_semua = array();
        foreach ($baris as &$b) {
            $b['hash'] = self::hash_baris($b);
            if ($b['sku'] !== '') $sku_semua[$b['sku']] = true;
            $hash_semua[] = $b['hash'];
        }
        unset($b);

        $master = array();
        foreach (array_chunk(array_keys($sku_semua), 500) as $chunk) {
            foreach ($this->db->select('id_sku')->where_in('id_sku', $chunk)->get('tblsku')->result() as $r) {
                $master[$r->id_sku] = true;
            }
        }
        $sudah = array();
        foreach (array_chunk($hash_semua, 500) as $chunk) {
            foreach ($this->db->select('b.hash_baris, b.id_import')->from('tbltoplevel_import_baris b')->where_in('b.hash_baris', $chunk)->get()->result() as $r) {
                $sudah[$r->hash_baris] = self::kode_import($r->id_import);
            }
        }
        $rak_ada = array();
        foreach (array_chunk(array_keys($sku_semua), 500) as $chunk) {
            foreach ($this->db->select('sku, no_rak')->where_in('sku', $chunk)->get('tbltoplevel')->result() as $r) {
                $rak_ada[$r->sku . '|' . $r->no_rak] = true;
            }
        }

        $lihat = array();
        foreach ($baris as &$b) {
            $b['err'] = '';
            $b['warn'] = '';
            $b['baru'] = false;
            if ($b['sku'] === '') {
                $b['st'] = 'err'; $b['err'] = 'SKU kosong';
            } elseif (!isset($master[$b['sku']])) {
                $b['st'] = 'err'; $b['err'] = 'SKU tidak ada di master';
            } elseif ($b['rak'] === '') {
                $b['st'] = 'err'; $b['err'] = 'No rak kosong';
            } elseif (!is_int($b['qty']) || $b['qty'] <= 0) {
                $b['st'] = 'err'; $b['err'] = 'Qty harus bilangan bulat > 0';
            } elseif (isset($lihat[$b['hash']])) {
                $b['st'] = 'dup'; $b['err'] = 'Sama dengan baris ' . $lihat[$b['hash']];
            } elseif (isset($sudah[$b['hash']])) {
                $b['st'] = 'dup'; $b['err'] = 'Sudah diimpor (' . $sudah[$b['hash']] . ')';
            } else {
                $b['st'] = 'ok';
                $lihat[$b['hash']] = $b['baris'];
                $b['baru'] = !isset($rak_ada[$b['sku'] . '|' . $b['rak']]);
                if ($b['nama_list'] === '' || $b['tgl_list'] === '' || $b['nama_input'] === '' || $b['tgl_input'] === '') {
                    $b['warn'] = 'Pencatat atau tanggal belum lengkap';
                }
            }
        }
        unset($b);
        return $baris;
    }

    /**
     * Menyimpan baris berstatus ok: qty ditambahkan ke rak (rak baru dibuat), baris Excel disimpan beserta hash-nya.
     * Semua dalam satu transaksi; kalau ada yang gagal, tidak ada stok yang berubah.
     */
    public function impor(array $baris, $nama_file, $oleh)
    {
        $ok = array_values(array_filter($baris, function ($b) { return $b['st'] === 'ok'; }));
        if (!$ok) {
            return array('ok' => false, 'pesan' => 'Tidak ada baris yang siap diimpor.');
        }
        $qty_total = 0;
        foreach ($ok as $b) $qty_total += $b['qty'];

        $this->db->trans_start();
        $this->db->insert('tbltoplevel_import', array(
            'nama_file' => mb_substr($nama_file, 0, 255), 'import_by' => $oleh, 'import_at' => date('Y-m-d H:i:s'),
            'jumlah_baris' => count($ok), 'jumlah_qty' => $qty_total, 'jumlah_lewat' => count($baris) - count($ok),
        ));
        $id = (int) $this->db->insert_id();
        $kode = self::kode_import($id);
        $trf = array();
        $nama_tim = array();

        foreach ($ok as $b) {
            $this->db->insert('tbltoplevel_import_baris', array(
                'id_import' => $id, 'baris_excel' => $b['baris'], 'sku' => $b['sku'], 'no_rak' => $b['rak'], 'qty' => $b['qty'],
                'ac_no_list' => $b['ac_list'], 'nama_list' => $b['nama_list'], 'tgl_list' => $b['tgl_list'] !== '' ? $b['tgl_list'] : null,
                'ac_no_input' => $b['ac_input'], 'nama_input' => $b['nama_input'], 'tgl_input' => $b['tgl_input'] !== '' ? $b['tgl_input'] : null,
                'hash_baris' => $b['hash'],
            ));
            $row = $this->db->query('SELECT qty FROM tbltoplevel WHERE sku = ? AND no_rak = ? FOR UPDATE', array($b['sku'], $b['rak']))->row();
            $lama = $row ? (int) $row->qty : 0;
            $this->db->query(
                'INSERT INTO tbltoplevel (sku, no_rak, qty, updated_at) VALUES (?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty), updated_at = VALUES(updated_at)',
                array($b['sku'], $b['rak'], $b['qty'])
            );
            $this->catat('UPLOAD', $b['sku'], $b['rak'], $lama, $lama + $b['qty'], $oleh, $kode, 'Upload Excel baris ' . $b['baris'] . ' (' . $nama_file . ')');
            $trf[] = array('sku' => $b['sku'], 'rak' => $b['rak'], 'qty' => $b['qty'], 'sebelum' => $lama, 'sesudah' => $lama + $b['qty']);
            if ($b['nama_list'] !== '') $nama_tim[$b['nama_list']] = true;
        }
        $nomor = $this->buat_transfer('MASUK', 'UPLOAD', $nama_tim ? implode(', ', array_keys($nama_tim)) : null, $kode, $oleh, $trf);
        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            return array('ok' => false, 'pesan' => 'Penyimpanan gagal; tidak ada stok yang berubah.');
        }
        return array('ok' => true, 'kode' => $kode, 'nomor' => $nomor, 'baris' => count($ok), 'qty' => $qty_total, 'lewat' => count($baris) - count($ok));
    }

    /** Peta ref (RT-0001 / UP-0001) => nomor transfer untuk satu sumber. */
    private function nomor_transfer_per_ref($sumber)
    {
        $peta = array();
        foreach ($this->db->select('ref, nomor')->where('sumber', $sumber)->get('tbltoplevel_transfer')->result() as $r) {
            if ($r->ref !== null) $peta[$r->ref] = $r->nomor;
        }
        return $peta;
    }

    public function riwayat_import($batas = 20)
    {
        $rows = $this->db->query(
            'SELECT i.id, i.nama_file, i.import_at, i.import_by, i.jumlah_baris, i.jumlah_qty, i.jumlah_lewat,
                    (SELECT GROUP_CONCAT(DISTINCT NULLIF(b.nama_list, "") SEPARATOR ", ") FROM tbltoplevel_import_baris b WHERE b.id_import = i.id) AS dilist_oleh
             FROM tbltoplevel_import i ORDER BY i.id DESC LIMIT ' . (int) $batas
        )->result_array();
        $transfer = $this->nomor_transfer_per_ref('UPLOAD');
        foreach ($rows as &$r) {
            $r['kode'] = self::kode_import($r['id']);
            $r['transfer'] = isset($transfer[$r['kode']]) ? $transfer[$r['kode']] : '';
        }
        return $rows;
    }

    /**
     * Tanggal dari sel Excel: serial Excel, d/m/Y [H:i[:s]], d-m-Y, atau Y-m-d. 'Y-m-d H:i:s', atau '' bila kosong/tak terbaca.
     */
    public static function baca_tanggal($v)
    {
        if ($v === null || $v === '') return '';
        if (is_int($v) || is_float($v) || (is_string($v) && is_numeric($v))) {
            $n = (float) $v;
            if ($n > 20000 && $n < 80000) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($n)->format('Y-m-d H:i:s');
            }
            return '';
        }
        $s = trim((string) $v);
        if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})(?:[ T](\d{1,2}):(\d{2})(?::(\d{2}))?)?$/', $s, $m)) {
            if (checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
                return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $m[3], $m[2], $m[1], isset($m[4]) ? $m[4] : 0, isset($m[5]) ? $m[5] : 0, isset($m[6]) ? $m[6] : 0);
            }
        } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{1,2}):(\d{2})(?::(\d{2}))?)?/', $s, $m)) {
            if (checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $m[1], $m[2], $m[3], isset($m[4]) ? $m[4] : 0, isset($m[5]) ? $m[5] : 0, isset($m[6]) ? $m[6] : 0);
            }
        }
        return '';
    }
}
