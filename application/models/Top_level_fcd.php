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
     * Urutan = nomor terakhir yang MASIH BERLAKU (tidak dibatalkan) di tbltoplevel_transfer pada tahun itu + 1, jadi kalau
     * transfer terakhir dibatalkan (batalkan_transfer), nomornya dipakai lagi (hapus TTL-2026-00002 saja: input berikutnya kembali bernomor 00002; hapus semuanya:
     * mulai lagi dari 00001). Lubang di tengah tidak diisi. Dihitung DI DALAM transaksi pemanggil, dengan baris
     * penghitung di tbltoplevel_nomor sebagai kunci (terkunci sampai commit) dan MAX dibaca dengan FOR UPDATE, jadi dua
     * transaksi bersamaan tidak pernah mendapat nomor yang sama, dan rollback tidak meninggalkan lubang nomor.
     * Nilai di tbltoplevel_nomor hanya catatan nomor terakhir yang diberikan, bukan sumber urutan.
     *
     * @param string $arah   MASUK atau KELUAR
     * @param string $sumber INPUT, UPLOAD, REQUEST, atau KOREKSI
     * @param array  $baris  [['sku'=>, 'rak'=>, 'qty'=>, 'sebelum'=>, 'sesudah'=>], ...]
     * @return string nomor transfer
     */
    private function buat_transfer($arah, $sumber, $nama_tim, $ref, $oleh, array $baris, $tgl_input = null, $nama_input = null, $tgl_list = null)
    {
        $tahun = (int) date('Y');
        $this->db->query('INSERT IGNORE INTO tbltoplevel_nomor (tahun, urut) VALUES (?, 0)', array($tahun));
        // Kunci baris penghitung (tanpa mengubah nilainya) supaya transaksi lain menunggu sampai commit.
        $this->db->query('SELECT urut FROM tbltoplevel_nomor WHERE tahun = ? FOR UPDATE', array($tahun));
        $akhir = $this->db->query('SELECT COALESCE(MAX(urut), 0) AS n FROM tbltoplevel_transfer WHERE tahun = ? AND aktif_key = 1 FOR UPDATE', array($tahun))->row();
        $urut = (int) $akhir->n + 1;
        $this->db->query('UPDATE tbltoplevel_nomor SET urut = ? WHERE tahun = ?', array($urut, $tahun));
        $nomor = self::kode_transfer($tahun, $urut);

        $qty = 0;
        foreach ($baris as $b) {
            $qty += $b['qty'];
        }
        $this->db->insert('tbltoplevel_transfer', array(
            'nomor' => $nomor, 'tahun' => $tahun, 'urut' => $urut, 'arah' => $arah, 'sumber' => $sumber,
            'nama_tim' => $nama_tim !== null ? mb_substr($nama_tim, 0, 150) : null,
            'nama_input' => $nama_input !== null ? mb_substr($nama_input, 0, 150) : null, 'ref' => $ref,
            'tgl_list' => $tgl_list,
            'tgl_input' => $tgl_input !== null ? $tgl_input : date('Y-m-d'),
            'dibuat_at' => date('Y-m-d H:i:s'), 'dibuat_by' => $oleh, 'diinput_at' => $baris ? date('Y-m-d H:i:s') : null,
            'jumlah_baris' => count($baris), 'jumlah_qty' => $qty,
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

    /**
     * Mengisi TTL keluar milik request yang dialokasikan saat pengajuan (baris belum ada): qty tiap rak disimpan MINUS
     * (keluar mengurangi stok). Request lama yang belum punya nomor mendapat nomor baru, juga bermuatan minus.
     *
     * @param array $trf [['sku'=>, 'rak'=>, 'qty'=>diambil (positif), 'sebelum'=>, 'sesudah'=>], ...]
     * @param string $petugas_list nama yang mendata di slip (diisi CS); petugas input = $oleh (akun login), tanggal = hari ini
     * @return string nomor transfer
     */
    private function isi_transfer_request($kode, $oleh, array $trf, $petugas_list)
    {
        $minus = array();
        $qty = 0;
        foreach ($trf as $b) {
            $b['qty'] = -abs((int) $b['qty']);
            $qty += $b['qty'];
            $minus[] = $b;
        }
        $t = $this->db->query("SELECT id, nomor FROM tbltoplevel_transfer WHERE sumber = 'REQUEST' AND ref = ? AND aktif_key = 1 AND jumlah_baris = 0 ORDER BY id DESC LIMIT 1 FOR UPDATE", array($kode))->row_array();
        if (!$t) {
            return $this->buat_transfer('KELUAR', 'REQUEST', $petugas_list, $kode, $oleh, $minus, date('Y-m-d'), $oleh, date('Y-m-d'));
        }
        foreach ($minus as $b) {
            $this->db->insert('tbltoplevel_transfer_baris', array(
                'id_transfer' => (int) $t['id'], 'sku' => $b['sku'], 'no_rak' => $b['rak'], 'qty' => $b['qty'],
                'qty_sebelum' => $b['sebelum'], 'qty_sesudah' => $b['sesudah'],
            ));
        }
        $this->db->where('id', (int) $t['id'])->update('tbltoplevel_transfer', array(
            'jumlah_baris' => count($minus), 'jumlah_qty' => $qty,
            // Petugas List dari isian CS; Petugas Input = akun IRESIS yang input qty; tanggal list dan input = hari ini (sistem).
            'nama_tim' => mb_substr($petugas_list, 0, 150), 'nama_input' => mb_substr((string) $oleh, 0, 150),
            'tgl_list' => date('Y-m-d'), 'tgl_input' => date('Y-m-d'), 'diinput_at' => date('Y-m-d H:i:s'),
        ));
        $this->catat('TRANSFER', null, null, null, null, $oleh, $t['nomor'], 'Transfer keluar ' . $t['nomor'] . ' (REQUEST ' . $kode . ') diisi, ' . count($minus) . ' baris, ' . $qty . ' pcs');
        return $t['nomor'];
    }

    // ------------------------------------------------------------ CS: Input Top Level

    /**
     * Rekomendasi nama petugas (Petugas List dan Petugas Input memakai daftar yang sama): yang terakhir dipakai
     * dulu (di kolom petugas list maupun petugas input), lalu pegawai aktif yang cocok dengan ketikan.
     */
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

        // Nama gabungan hasil upload ("Rina, Dewi") tidak ikut disarankan.
        $like = '%' . $this->db->escape_like_str($q) . '%';
        $terakhir = $this->db->query(
            'SELECT nama FROM (
                SELECT nama_tim AS nama, MAX(id) AS m FROM tbltoplevel_transfer
                  WHERE nama_tim IS NOT NULL AND nama_tim <> "" AND nama_tim NOT LIKE "%,%" AND nama_tim LIKE ? GROUP BY nama_tim
                UNION ALL
                SELECT nama_input AS nama, MAX(id) AS m FROM tbltoplevel_transfer
                  WHERE nama_input IS NOT NULL AND nama_input <> "" AND nama_input NOT LIKE "%,%" AND nama_input LIKE ? GROUP BY nama_input
             ) x GROUP BY nama ORDER BY MAX(m) DESC LIMIT 8',
            array($like, $like)
        )->result();
        foreach ($terakhir as $r) {
            $pakai($r->nama, 'terakhir');
        }

        if ($q !== '') {
            $this->db->select('nama_pegawai')->from('tblpegawai')->where('status_aktif', 'AKTIF')->like('nama_pegawai', $q);
            foreach ($this->db->order_by('nama_pegawai', 'ASC')->limit(10)->get()->result() as $r) {
                $pakai($r->nama_pegawai, 'pegawai');
            }
        }
        return array_slice(array_values($hasil), 0, 10);
    }

    /**
     * Pasangan SKU + no rak yang sudah tersimpan dengan qty bukan nol.
     *
     * @param array $pasang [['sku'=>, 'rak'=>], ...] (rak dibandingkan huruf besar)
     * @return array peta 'SKU|RAK' => qty tersimpan
     */
    public function cek_duplikat(array $pasang)
    {
        $kondisi = array();
        $bind = array();
        foreach ($pasang as $p) {
            $sku = trim((string) (isset($p['sku']) ? $p['sku'] : ''));
            $rak = strtoupper(trim((string) (isset($p['rak']) ? $p['rak'] : '')));
            if ($sku === '' || $rak === '') continue;
            $kondisi[] = '(sku = ? AND no_rak = ?)';
            $bind[] = $sku;
            $bind[] = $rak;
        }
        $hasil = array();
        foreach (array_chunk($kondisi, 100) as $i => $potong) {
            $q = $this->db->query(
                'SELECT sku, no_rak, qty FROM tbltoplevel WHERE qty <> 0 AND (' . implode(' OR ', $potong) . ')',
                array_slice($bind, $i * 200, count($potong) * 2)
            );
            foreach ($q->result() as $r) {
                $hasil[$r->sku . '|' . strtoupper($r->no_rak)] = (int) $r->qty;
            }
        }
        return $hasil;
    }

    /** Saran SKU saat mengetik: kode SKU saja (awalan lebih dulu, lalu yang memuat ketikan). */
    public function cari_sku($q)
    {
        $q = trim((string) $q);
        if ($q === '') {
            return array();
        }
        $like = $this->db->escape_like_str($q);
        return $this->db->query(
            'SELECT id_sku AS sku FROM tblsku WHERE id_sku LIKE ?
             ORDER BY (id_sku LIKE ?) DESC, id_sku ASC LIMIT 8',
            array('%' . $like . '%', $like . '%')
        )->result_array();
    }

    /**
     * Saran no rak di Input Top Level. KELUAR: rak yang menyimpan stok SKU itu (qty > 0), disaring ketikan.
     * MASUK: semua no rak yang pernah ada, disaring ketikan (awalan lebih dulu); ketikan kosong = tanpa saran.
     * Mengembalikan [['rak'=>, 'qty'=>], ...]; qty hanya terisi untuk KELUAR.
     */
    public function cari_rak($sku, $q, $arah)
    {
        $sku = trim((string) $sku);
        $q = strtoupper(trim((string) $q));
        $like = $this->db->escape_like_str($q);
        if ($arah === 'KELUAR') {
            if ($sku === '') {
                return array();
            }
            return $this->db->query(
                'SELECT no_rak AS rak, SUM(qty) AS qty FROM tbltoplevel WHERE sku = ? AND qty > 0 AND no_rak LIKE ?
                 GROUP BY no_rak ORDER BY (no_rak LIKE ?) DESC, no_rak ASC LIMIT 20',
                array($sku, '%' . $like . '%', $like . '%')
            )->result_array();
        }
        if ($q === '') {
            return array();
        }
        return $this->db->query(
            'SELECT DISTINCT no_rak AS rak, NULL AS qty FROM tbltoplevel WHERE no_rak LIKE ?
             ORDER BY (no_rak LIKE ?) DESC, no_rak ASC LIMIT 10',
            array('%' . $like . '%', $like . '%')
        )->result_array();
    }

    /**
     * Input stok top level manual oleh CS: qty ditambahkan ke rak (rak baru dibuat) dan dicatat sebagai
     * satu transfer MASUK dengan nomor TTL.
     *
     * @param array  $baris   [['sku'=>, 'rak'=>, 'qty'=>], ...]
     * @param string $tanggal       tanggal input 'Y-m-d' (bawaan hari ini); tidak boleh di masa depan
     * @param string $tanggal_list  tanggal list 'Y-m-d' (bawaan hari ini); tidak boleh di masa depan
     *                              dan tidak boleh setelah tanggal input
     */
    public function input_masuk($nama_tim, array $baris, $oleh, $tanggal = '', $nama_input = '', $tanggal_list = '', $arah = 'MASUK')
    {
        // KELUAR: stok rak dikurangi (qty transfer disimpan minus, sama seperti TTL keluar dari request).
        $keluar = ($arah === 'KELUAR');
        $nama_tim = trim(preg_replace('/\s+/', ' ', (string) $nama_tim));
        $nama_input = trim(preg_replace('/\s+/', ' ', (string) $nama_input));
        if ($nama_tim === '') {
            return array('ok' => false, 'pesan' => 'Nama petugas list wajib diisi.');
        }
        if ($nama_input === '') {
            return array('ok' => false, 'pesan' => 'Nama petugas input wajib diisi.');
        }
        $tanggal = trim((string) $tanggal);
        if ($tanggal === '') {
            $tanggal = date('Y-m-d');
        }
        $d = \DateTime::createFromFormat('Y-m-d', $tanggal);
        if (!$d || $d->format('Y-m-d') !== $tanggal) {
            return array('ok' => false, 'pesan' => 'Tanggal input tidak valid.');
        }
        if ($tanggal > date('Y-m-d')) {
            return array('ok' => false, 'pesan' => 'Tanggal input tidak boleh di masa depan.');
        }
        $tanggal_list = trim((string) $tanggal_list);
        if ($tanggal_list === '') {
            $tanggal_list = date('Y-m-d');
        }
        $dl = \DateTime::createFromFormat('Y-m-d', $tanggal_list);
        if (!$dl || $dl->format('Y-m-d') !== $tanggal_list) {
            return array('ok' => false, 'pesan' => 'Tanggal list tidak valid.');
        }
        if ($tanggal_list > date('Y-m-d')) {
            return array('ok' => false, 'pesan' => 'Tanggal list tidak boleh di masa depan.');
        }
        if ($tanggal_list > $tanggal) {
            return array('ok' => false, 'pesan' => 'Tanggal list tidak boleh setelah tanggal input.');
        }
        $bersih = array();
        $persis = array();
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
            // SKU + rak yang sama boleh muncul lebih dari sekali dengan qty berbeda (qty dijumlahkan);
            // yang sama persis, qty juga, dianggap input dobel.
            $kunci_baris = $sku . '|' . $rak . '|' . (int) $qty;
            if (isset($persis[$kunci_baris])) {
                return array('ok' => false, 'pesan' => 'Baris ' . $no . ': SKU, rak, dan qty sama persis dengan baris ' . $persis[$kunci_baris] . '.');
            }
            $persis[$kunci_baris] = $no;
            $bersih[] = array('sku' => $sku, 'rak' => $rak, 'qty' => (int) $qty, 'baris' => $no);
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

        // Guard duplikat: SKU + no rak yang sudah tersimpan dengan qty bukan nol tidak boleh diinput lagi.
        // Input ini bukan penambahan; koreksi stok memakai Edit di Stok Top Level. Arah KELUAR tidak memakai guard ini.
        $dobel = $keluar ? array() : $this->cek_duplikat($bersih);
        if ($dobel) {
            $baris_dobel = array();
            foreach ($bersih as $b) {
                if (isset($dobel[$b['sku'] . '|' . $b['rak']])) $baris_dobel[] = $b['baris'];
            }
            return array('ok' => false, 'duplikat' => true, 'pesan' => 'Duplikat: SKU dan no rak di baris ' . implode(', ', $baris_dobel) . ' sudah tersimpan dengan stok. Koreksi lewat Edit di Stok Top Level.');
        }

        $this->db->trans_start();
        $lines = array();
        $berjalan = array(); // stok per SKU + rak selama transaksi (baris kedua dst. dengan SKU + rak yang sama)
        foreach ($bersih as $b) {
            $kb = $b['sku'] . '|' . $b['rak'];
            if (!isset($berjalan[$kb])) {
                $row = $this->db->query('SELECT qty FROM tbltoplevel WHERE sku = ? AND no_rak = ? FOR UPDATE', array($b['sku'], $b['rak']))->row();
                $berjalan[$kb] = $row ? (int) $row->qty : 0;
                if ($keluar) {
                    // dicek per baris di bawah terhadap stok berjalan
                } elseif ($berjalan[$kb] !== 0) { // diperiksa ulang di dalam transaksi: ada yang menyimpan di sela pemeriksaan di atas
                    $this->db->trans_rollback();
                    return array('ok' => false, 'duplikat' => true, 'pesan' => 'Duplikat: SKU ' . $b['sku'] . ' di rak ' . $b['rak'] . ' baru saja tersimpan dengan stok ' . $berjalan[$kb] . '.');
                }
            }
            $lama = $berjalan[$kb];
            if ($keluar) {
                if ($b['qty'] > $lama) {
                    $this->db->trans_rollback();
                    return array('ok' => false, 'pesan' => 'Baris ' . $b['baris'] . ': stok ' . $b['sku'] . ' di rak ' . $b['rak'] . ' hanya ' . $lama . ' pcs, tidak cukup untuk keluar ' . $b['qty'] . '.');
                }
                $this->db->query('UPDATE tbltoplevel SET qty = qty - ?, updated_at = NOW() WHERE sku = ? AND no_rak = ?', array($b['qty'], $b['sku'], $b['rak']));
                $berjalan[$kb] = $lama - $b['qty'];
                $lines[] = array('sku' => $b['sku'], 'rak' => $b['rak'], 'qty' => -$b['qty'], 'sebelum' => $lama, 'sesudah' => $lama - $b['qty']);
                $this->catat('KELUAR', $b['sku'], $b['rak'], $lama, $lama - $b['qty'], $oleh, null, 'Keluar top level, petugas list ' . $nama_tim . ', petugas input ' . $nama_input);
                continue;
            }
            $this->db->query(
                'INSERT INTO tbltoplevel (sku, no_rak, qty, updated_at) VALUES (?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty), updated_at = VALUES(updated_at)',
                array($b['sku'], $b['rak'], $b['qty'])
            );
            $berjalan[$kb] = $lama + $b['qty'];
            $lines[] = array('sku' => $b['sku'], 'rak' => $b['rak'], 'qty' => $b['qty'], 'sebelum' => $lama, 'sesudah' => $lama + $b['qty']);
            $this->catat('INPUT', $b['sku'], $b['rak'], $lama, $lama + $b['qty'], $oleh, null, 'Input top level, petugas list ' . $nama_tim . ', petugas input ' . $nama_input);
        }
        $nomor = $this->buat_transfer($keluar ? 'KELUAR' : 'MASUK', 'INPUT', $nama_tim, null, $oleh, $lines, $tanggal, $nama_input, $tanggal_list);
        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            return array('ok' => false, 'pesan' => 'Penyimpanan gagal; tidak ada stok yang berubah.');
        }
        $qty = 0;
        foreach ($lines as $l) $qty += abs($l['qty']);
        return array('ok' => true, 'nomor' => $nomor, 'baris' => count($lines), 'qty' => $qty, 'tanggal' => $tanggal, 'tanggal_list' => $tanggal_list);
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
            $it['request'] = $r ? array('id' => (int) $r['id'], 'kode' => self::kode($r['id']), 'status' => $r['status'],
                'nomor' => $this->nomor_tampil($r['id'])) : null;
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
             WHERE x.noresi = ? AND r.sku = ? AND r.status <> "BATAL"
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
            'aktif'    => $aktif ? array('id' => (int) $aktif['id'], 'kode' => self::kode($aktif['id']), 'nomor' => $this->nomor_tampil($aktif['id']), 'status' => $aktif['status']) : null,
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
                return array('ok' => false, 'pesan' => 'Sudah ada request ' . $this->nomor_tampil($aktif['id']) . ' untuk SKU ini.', 'aktif' => (int) $aktif['id']);
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
            // Nomor TTL keluar dialokasikan sekarang (lanjut dari TTL terakhir); qty (minus) baru diisi saat CS input qty.
            $nomor = $this->buat_transfer('KELUAR', 'REQUEST', null, self::kode($id), $oleh, array());
            $this->catat('REQUEST', $sku, null, null, null, $oleh, self::kode($id), 'Request restok display ' . $now . ($noresi !== '' ? ' · resi ' . $noresi : '') . ' · ' . $nomor);
            $this->db->trans_complete();

            if ($this->db->trans_status() === false) {
                return array('ok' => false, 'pesan' => 'Gagal menyimpan request.');
            }
            return array('ok' => true, 'kode' => self::kode($id), 'nomor' => $nomor, 'id' => $id);
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
            return array('ok' => true, 'kode' => self::kode($id), 'nomor' => $this->nomor_tampil($id));
        }
        $this->db->insert('tbltoplevel_request_resi', array('id_request' => (int) $id, 'noresi' => $noresi, 'created_at' => date('Y-m-d H:i:s')));
        $this->catat('REQUEST', $r['sku'], null, null, null, $oleh, self::kode($id), 'Resi ' . $noresi . ' digabung');
        return array('ok' => true, 'kode' => self::kode($id), 'nomor' => $this->nomor_tampil($id));
    }

    /** Hanya request yang belum dicetak yang boleh dibatalkan. */
    public function batal($id, $oleh)
    {
        $r = $this->db->get_where('tbltoplevel_request', array('id' => (int) $id))->row_array();
        if (!$r) {
            return array('ok' => false, 'pesan' => 'Request tidak ditemukan.');
        }
        $this->db->trans_start();
        $this->db->where(array('id' => (int) $id, 'status' => 'DIAJUKAN'))
            ->update('tbltoplevel_request', array('status' => 'BATAL', 'batal_at' => date('Y-m-d H:i:s')));
        if ($this->db->affected_rows() < 1) {
            $this->db->trans_complete();
            return array('ok' => false, 'pesan' => 'Request sudah dicetak, tidak bisa dibatalkan.');
        }
        // TTL keluar milik request ini belum punya baris (qty baru diisi saat selesai): tandai batal, tanpa DELETE dan tanpa mengubah stok.
        $t = $this->db->query("SELECT id, tahun FROM tbltoplevel_transfer WHERE sumber = 'REQUEST' AND ref = ? AND aktif_key = 1 AND jumlah_baris = 0 FOR UPDATE", array(self::kode($id)))->row_array();
        if ($t) {
            $this->db->query('UPDATE tbltoplevel_transfer SET aktif_key = NULL, batal_at = NOW(), batal_by = ?, batal_alasan = ? WHERE id = ?', array($oleh, 'Request dibatalkan sebelum dicetak', (int) $t['id']));
            $this->db->query('UPDATE tbltoplevel_nomor SET urut = (SELECT COALESCE(MAX(urut), 0) FROM tbltoplevel_transfer WHERE tahun = ? AND aktif_key = 1) WHERE tahun = ?', array((int) $t['tahun'], (int) $t['tahun']));
        }
        $this->catat('REQUEST', $r['sku'], null, null, null, $oleh, self::kode($id), 'Request dibatalkan');
        $this->db->trans_complete();
        return array('ok' => $this->db->trans_status() !== false);
    }

    /**
     * Request HARI INI yang punya resi (semua picker memakai satu akun), terbaru di atas, untuk panel antrian di
     * halaman picker. Batal tidak ikut. 'nomor' = nomor TTL keluar bila request sudah selesai, kalau belum kode request.
     * Jam tiap tahap (diajukan, dicetak CS, selesai) dikirim dalam format H:i:s; tahap yang belum tercapai berisi ''.
     */
    public function terbaru($batas = 200)
    {
        $rows = $this->db->query(
            "SELECT r.id, r.sku, r.status, r.request_at, r.cetak_at, r.selesai_at,
                    (SELECT GROUP_CONCAT(noresi ORDER BY id SEPARATOR ', ') FROM tbltoplevel_request_resi WHERE id_request = r.id) AS resi
             FROM tbltoplevel_request r
             WHERE r.status <> 'BATAL' AND r.abaikan = 0 AND r.request_at >= CURDATE()
               AND EXISTS (SELECT 1 FROM tbltoplevel_request_resi x WHERE x.id_request = r.id)
             ORDER BY r.id DESC LIMIT " . (int) $batas
        )->result_array();
        $jam = function ($t) { return $t ? date('H:i:s', strtotime($t)) : ''; };
        $peta = $this->nomor_transfer_per_ref('REQUEST');
        foreach ($rows as &$r) {
            $r['kode'] = self::kode($r['id']);
            $r['nomor'] = isset($peta[$r['kode']]) ? $peta[$r['kode']] : $r['kode'];
            $r['jam_diajukan'] = $jam($r['request_at']);
            $r['jam_cetak'] = $jam($r['cetak_at']);
            $r['jam_selesai'] = $jam($r['selesai_at']);
            unset($r['request_at'], $r['cetak_at'], $r['selesai_at']);
        }
        return $rows;
    }

    // ------------------------------------------------------------ CS: Request Top Level

    /** Hitungan per kelompok tab: aktif, cetak (DIAJUKAN), restok (DITUGASKAN), selesai (SELESAI + BATAL). */
    public function hitung_request()
    {
        $h = array('aktif' => 0, 'cetak' => 0, 'restok' => 0, 'selesai' => 0);
        foreach ($this->db->query('SELECT status, COUNT(*) AS n FROM tbltoplevel_request WHERE abaikan = 0 GROUP BY status')->result() as $r) {
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
        $this->db->where('r.abaikan', 0);
        $cari = trim((string) $cari);
        if ($cari !== '') {
            $id = self::id_dari_kode($cari);
            $this->db->group_start()
                ->like('r.sku', $cari)
                ->or_where('EXISTS (SELECT 1 FROM tbltoplevel_request_resi x WHERE x.id_request = r.id AND x.noresi LIKE ' . $this->db->escape('%' . $this->db->escape_like_str($cari) . '%') . ')', null, false);
            if ($id > 0) {
                $this->db->or_where('r.id', $id);
            }
            $this->db->or_where("EXISTS (SELECT 1 FROM tbltoplevel_transfer tt WHERE tt.sumber = 'REQUEST' AND tt.aktif_key = 1 AND tt.ref = CONCAT('RT-', LPAD(r.id, 4, '0')) AND tt.nomor LIKE " . $this->db->escape('%' . $this->db->escape_like_str($cari) . '%') . ')', null, false);
            $this->db->group_end();
        }
        $rows = $this->db->order_by('r.id', 'DESC')->limit(500)->get()->result_array();
        $peta = $this->nomor_transfer_per_ref('REQUEST');
        foreach ($rows as &$r) {
            $r['kode'] = self::kode($r['id']);
            $r['nomor'] = isset($peta[$r['kode']]) ? $peta[$r['kode']] : $r['kode'];
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
        $r = $this->db->select('nomor')->where(array('sumber' => 'REQUEST', 'ref' => self::kode($id), 'aktif_key' => 1))->get('tbltoplevel_transfer')->row();
        return $r ? $r->nomor : '';
    }

    /** Nomor TTL milik request (dialokasikan saat pengajuan); kode request bila belum ada (request lama). */
    private function nomor_tampil($id)
    {
        return $this->nomor_request($id) ?: self::kode($id);
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
        // Petugas list (nama_tim) dan petugas input (nama_input) tersimpan di TTL keluar milik request ini, terisi saat selesai.
        $ttl = $this->db->select('nama_tim, nama_input')->where(array('sumber' => 'REQUEST', 'ref' => self::kode($r['id']), 'aktif_key' => 1))->get('tbltoplevel_transfer')->row();

        return array(
            'id'          => (int) $r['id'],
            'kode'        => self::kode($r['id']),
            'nomor'       => $this->nomor_tampil($r['id']),
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
            'petugas_list'  => ($ttl && $ttl->nama_tim !== null) ? $ttl->nama_tim : '',
            'petugas_input' => ($ttl && $ttl->nama_input !== null) ? $ttl->nama_input : '',
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
     * @param bool  $qty_nol true bila CS menegaskan tidak ada barang yang diambil (total qty 0)
     */
    public function selesai($id, array $rows, $oleh, $petugas_list = '', $qty_nol = false)
    {
        $r = $this->db->get_where('tbltoplevel_request', array('id' => (int) $id))->row_array();
        if (!$r || $r['status'] !== 'DITUGASKAN') {
            return array('ok' => false, 'pesan' => 'Request belum dicetak atau sudah selesai.');
        }
        // Petugas List (yang mendata di slip) wajib diisi; petugas input dan kedua tanggal dicatat sistem.
        $petugas_list = trim(preg_replace('/\s+/', ' ', (string) $petugas_list));
        if ($petugas_list === '') {
            return array('ok' => false, 'pesan' => 'Nama petugas list wajib diisi.');
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
            // Qty 0 diperbolehkan (mis. display sudah diisi Restok tidak lama sebelum TTL dikeluarkan), tapi harus dikonfirmasi
            // CS lewat $qty_nol supaya kolom yang tidak sengaja kosong tidak menutup request. TTL tetap tanpa baris, stok tidak dikurangi.
            if ($total <= 0 && !$qty_nol) {
                $this->db->trans_rollback();
                return array('ok' => false, 'pesan' => 'Qty diambil 0: centang konfirmasi qty 0 (TL sudah direstok tim Restok sebelum diterbitkan, tidak ada barang diambil).');
            }
            $nomor = $this->isi_transfer_request($kode, $oleh, $trf, $petugas_list);
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

    /**
     * Daftar stok untuk menu Stok Top Level: tiap SKU beserta rak-raknya yang berstok.
     *
     * @return array [['sku'=>, 'total'=>int, 'rak'=>[['rak'=>, 'stok'=>int], ...]], ...]
     */
    public function stok_list($cari)
    {
        // SKU yang total stoknya 0 (semua rak kosong) tidak ditampilkan.
        $this->db->select('sku, SUM(qty) AS total', false)->from('tbltoplevel')->group_by('sku')->having('SUM(qty) <> 0', null, false);
        $cari = trim((string) $cari);
        if ($cari !== '') {
            $this->db->like('sku', $cari);
        }
        $rows = $this->db->order_by('sku', 'ASC')->limit(5000)->get()->result_array();
        $peta = array();
        foreach ($rows as $i => $r) {
            $rows[$i]['total'] = (int) $r['total'];
            $rows[$i]['rak'] = array();
            $peta[$r['sku']] = $i;
        }
        foreach (array_chunk(array_keys($peta), 500) as $chunk) {
            foreach ($this->db->select('sku, no_rak, qty')->where('qty >', 0)->where_in('sku', $chunk)->order_by('no_rak', 'ASC')->get('tbltoplevel')->result() as $x) {
                $rows[$peta[$x->sku]]['rak'][] = array('rak' => $x->no_rak, 'stok' => (int) $x->qty);
            }
        }
        return $rows;
    }

    /** Riwayat perubahan stok satu SKU di satu rak (terbaru dulu), dari jejak audit tbltoplevel_log. */
    public function stok_riwayat($sku, $rak)
    {
        $rows = $this->db->query(
            "SELECT waktu, jenis, qty_sebelum, qty_sesudah, oleh, ref, keterangan FROM tbltoplevel_log
             WHERE sku = ? AND no_rak = ? AND jenis <> 'TRANSFER' ORDER BY id DESC LIMIT 30",
            array($sku, $rak)
        )->result_array();
        foreach ($rows as &$r) {
            $r['selisih'] = (int) $r['qty_sesudah'] - (int) $r['qty_sebelum'];
        }
        unset($r);
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

    /**
     * Ubah stok satu SKU sekaligus: qty per rak, ganti no rak, dan ganti SKU. SKU baru wajib ada di master (tblsku).
     * Tidak ada DELETE: rak lama cukup berqty 0. Ganti SKU memindahkan seluruh stok ke SKU baru (digabung bila SKU
     * baru sudah punya rak yang sama). Perubahan qty dicatat di log dan menghasilkan nomor transfer masuk/keluar.
     *
     * @param array $rows [['asal'=>no rak semula ('' untuk rak baru), 'rak'=>no rak sesudah, 'qty'=>], ...]
     */
    public function ubah_stok($sku, $sku_baru, array $rows, $alasan, $oleh)
    {
        $sku = trim((string) $sku);
        $sku_baru = trim((string) $sku_baru);
        if (!in_array($alasan, array('Opname', 'Salah catat', 'Barang rusak', 'Pindah rak'), true)) {
            return array('ok' => false, 'pesan' => 'Alasan tidak valid.');
        }
        if ($sku === '' || $sku_baru === '') {
            return array('ok' => false, 'pesan' => 'SKU kosong.');
        }
        $ada = $this->db->query('SELECT id_sku FROM tblsku WHERE id_sku = ? LIMIT 1', array($sku_baru))->row();
        if (!$ada) {
            return array('ok' => false, 'pesan' => 'SKU ' . $sku_baru . ' tidak ada di master (Jubelio).');
        }
        $sku_baru = $ada->id_sku;
        $ganti_sku = ($sku_baru !== $sku);

        // Normalisasi baris; rak yang sama digabung.
        $tujuan = array();
        $asal_set = array();
        $ganti_rak = array();
        foreach ($rows as $x) {
            $asal = strtoupper(trim((string) (isset($x['asal']) ? $x['asal'] : '')));
            $rak = strtoupper(trim((string) (isset($x['rak']) ? $x['rak'] : '')));
            $qty = isset($x['qty']) ? $x['qty'] : '';
            if ($rak === '' && $qty === '' && $asal === '') {
                continue;
            }
            if ($rak === '') {
                return array('ok' => false, 'pesan' => 'No rak tidak boleh kosong.');
            }
            if (!is_numeric($qty) || (int) $qty != $qty || $qty < 0) {
                return array('ok' => false, 'pesan' => 'Qty rak ' . $rak . ' harus bilangan bulat 0 atau lebih.');
            }
            $tujuan[$rak] = (isset($tujuan[$rak]) ? $tujuan[$rak] : 0) + (int) $qty;
            if ($asal !== '') {
                $asal_set[$asal] = true;
                if ($asal !== $rak) {
                    $ganti_rak[] = $asal . ' → ' . $rak;
                }
            }
        }

        $kunci = array();
        $nama_kunci = array_unique(array($sku, $sku_baru));
        sort($nama_kunci);
        foreach ($nama_kunci as $k) {
            $l = $this->kunci($k);
            if (!$l) {
                foreach ($kunci as $dipegang) {
                    $this->lepas($dipegang);
                }
                return array('ok' => false, 'pesan' => 'Sistem sibuk, coba lagi.');
            }
            $kunci[] = $l;
        }
        try {
            if ($ganti_sku) {
                $aktif = $this->db->query("SELECT id FROM tbltoplevel_request WHERE sku = ? AND status IN ('DIAJUKAN','DITUGASKAN') LIMIT 1", array($sku))->row();
                if ($aktif) {
                    return array('ok' => false, 'pesan' => 'SKU ' . $sku . ' masih punya request aktif (' . self::kode($aktif->id) . '). Selesaikan atau batalkan dulu.');
                }
            }
            $this->db->trans_start();
            $lama = array();
            foreach ($this->db->query('SELECT no_rak, qty FROM tbltoplevel WHERE sku = ? FOR UPDATE', array($sku))->result() as $r) {
                $lama[$r->no_rak] = (int) $r->qty;
            }
            // Layar harus sama dengan isi database; kalau berbeda, ada yang mengubah stok lebih dulu.
            $berstok = array();
            foreach ($lama as $rak => $q) {
                if ($q > 0) {
                    $berstok[$rak] = true;
                }
            }
            if (array_diff_key($berstok, $asal_set) || array_diff_key($asal_set, $berstok)) {
                $this->db->trans_rollback();
                return array('ok' => false, 'pesan' => 'Stok SKU ini sudah berubah. Muat ulang halaman lalu coba lagi.');
            }

            $ubah = array();
            if (!$ganti_sku) {
                foreach ($berstok as $rak => $t) {
                    if (!isset($tujuan[$rak])) {
                        $ubah[] = array($sku, $rak, $lama[$rak], 0);
                    }
                }
                foreach ($tujuan as $rak => $q) {
                    $ubah[] = array($sku, $rak, isset($lama[$rak]) ? $lama[$rak] : 0, $q);
                }
            } else {
                foreach ($berstok as $rak => $t) {
                    $ubah[] = array($sku, $rak, $lama[$rak], 0);
                }
                $lama_t = array();
                foreach ($this->db->query('SELECT no_rak, qty FROM tbltoplevel WHERE sku = ? FOR UPDATE', array($sku_baru))->result() as $r) {
                    $lama_t[$r->no_rak] = (int) $r->qty;
                }
                foreach ($tujuan as $rak => $q) {
                    $sebelum = isset($lama_t[$rak]) ? $lama_t[$rak] : 0;
                    $ubah[] = array($sku_baru, $rak, $sebelum, $sebelum + $q);
                }
            }

            $catatan = ($ganti_sku ? 'Ganti SKU ' . $sku . ' → ' . $sku_baru . '. ' : '') . ($ganti_rak ? 'Ganti no rak ' . implode(', ', $ganti_rak) . '. ' : '') . '(' . $alasan . ')';
            $masuk = array();
            $keluar = array();
            $berubah = 0;
            foreach ($ubah as $u) {
                list($s, $rak, $sebelum, $sesudah) = $u;
                if ($sebelum === $sesudah) {
                    continue;
                }
                $this->db->query(
                    'INSERT INTO tbltoplevel (sku, no_rak, qty, updated_at) VALUES (?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE qty = VALUES(qty), updated_at = VALUES(updated_at)',
                    array($s, $rak, $sesudah)
                );
                $this->catat('KOREKSI', $s, $rak, $sebelum, $sesudah, $oleh, null, 'Ubah stok. ' . $catatan);
                $b = array('sku' => $s, 'rak' => $rak, 'qty' => abs($sesudah - $sebelum), 'sebelum' => $sebelum, 'sesudah' => $sesudah);
                if ($sesudah > $sebelum) { $masuk[] = $b; } else { $keluar[] = $b; }
                $berubah++;
            }
            if ($berubah === 0) {
                $this->db->trans_rollback();
                return array('ok' => false, 'pesan' => 'Tidak ada perubahan.');
            }
            $nomor = array();
            if ($masuk)  { $nomor[] = $this->buat_transfer('MASUK', 'KOREKSI', null, $alasan, $oleh, $masuk); }
            if ($keluar) { $nomor[] = $this->buat_transfer('KELUAR', 'KOREKSI', null, $alasan, $oleh, $keluar); }
            $this->db->trans_complete();
            if ($this->db->trans_status() === false) {
                return array('ok' => false, 'pesan' => 'Penyimpanan gagal.');
            }
            return array('ok' => true, 'berubah' => $berubah, 'nomor' => $nomor);
        } finally {
            foreach ($kunci as $dipegang) {
                $this->lepas($dipegang);
            }
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
        $nama_masuk = array();
        $tgl_l = array();
        $tgl_i = array();

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
            if ($b['nama_input'] !== '') $nama_masuk[$b['nama_input']] = true;
            if ($b['tgl_list'] !== '') $tgl_l[] = substr($b['tgl_list'], 0, 10);
            if ($b['tgl_input'] !== '') $tgl_i[] = substr($b['tgl_input'], 0, 10);
        }
        // Tanggal transfer dari Excel = tanggal terawal di kolom TGL LIST dan TGL INPUT (kosong bila tidak ada).
        $nomor = $this->buat_transfer('MASUK', 'UPLOAD', $nama_tim ? implode(', ', array_keys($nama_tim)) : null, $kode, $oleh, $trf,
            $tgl_i ? min($tgl_i) : null, $nama_masuk ? implode(', ', array_keys($nama_masuk)) : null, $tgl_l ? min($tgl_l) : null);
        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            return array('ok' => false, 'pesan' => 'Penyimpanan gagal; tidak ada stok yang berubah.');
        }
        return array('ok' => true, 'kode' => $kode, 'nomor' => $nomor, 'baris' => count($ok), 'qty' => $qty_total, 'lewat' => count($baris) - count($ok));
    }

    // ------------------------------------------------------------ CS: Daftar Transfer Top Level

    const SUMBER_TRANSFER = array('INPUT', 'UPLOAD', 'REQUEST', 'KOREKSI');

    /**
     * Daftar transfer untuk menu Daftar Transfer Top Level. Filter: arah, sumber (boleh banyak), rentang tanggal input
     * (kosong = tanpa batas), dan pencarian (nomor, petugas, ref, atau SKU di baris). Terbaru di atas, per halaman.
     * 'hitung' = jumlah transfer per tab arah (mengikuti filter lain, tanpa filter arah) dan per sumber (tanpa filter sumber).
     *
     * @param array $sumber subset SUMBER_TRANSFER; kosong = semua
     * @return array ['rows'=>[...], 'total'=>int, 'halaman'=>int, 'ukuran'=>int, 'hitung'=>['arah'=>[...], 'sumber'=>[...]]]
     */
    public function daftar_transfer($arah, $sumber, $cari, $dari, $sampai, $halaman, $ukuran = 50)
    {
        $halaman = max(1, (int) $halaman);
        $ukuran = in_array((int) $ukuran, array(25, 50, 100), true) ? (int) $ukuran : 50;
        $tgl_ok = function ($t) { $d = \DateTime::createFromFormat('Y-m-d', (string) $t); return $d && $d->format('Y-m-d') === $t; };
        $sumber = array_values(array_intersect(is_array($sumber) ? $sumber : array(), self::SUMBER_TRANSFER));

        $saring = function ($pakai_arah = true, $pakai_sumber = true) use ($arah, $sumber, $cari, $dari, $sampai, $tgl_ok) {
            $this->db->where('t.aktif_key', 1);
            if ($pakai_arah && in_array($arah, array('MASUK', 'KELUAR'), true)) {
                $this->db->where('t.arah', $arah);
            }
            if ($pakai_sumber && $sumber) {
                $this->db->where_in('t.sumber', $sumber);
            }
            if ($tgl_ok($dari)) $this->db->where('t.tgl_input >=', $dari);
            if ($tgl_ok($sampai)) $this->db->where('t.tgl_input <=', $sampai);
            $cari = trim((string) $cari);
            if ($cari !== '') {
                $like = $this->db->escape('%' . $this->db->escape_like_str($cari) . '%');
                $this->db->where(
                    "(t.nomor LIKE $like OR t.nama_tim LIKE $like OR t.nama_input LIKE $like OR t.ref LIKE $like
                      OR EXISTS (SELECT 1 FROM tbltoplevel_transfer_baris b WHERE b.id_transfer = t.id AND b.dibatalkan = 0 AND b.sku LIKE $like))",
                    null, false
                );
            }
        };

        $saring();
        $total = (int) $this->db->from('tbltoplevel_transfer t')->count_all_results();

        $saring();
        $rows = $this->db->select('t.id, t.nomor, t.arah, t.sumber, t.nama_tim, t.nama_input, t.ref, t.tgl_list, t.tgl_input, t.diinput_at, t.dibuat_at, t.dibuat_by, t.jumlah_baris, t.jumlah_qty,
            (SELECT COUNT(*) FROM tbltoplevel_transfer_edit e WHERE e.id_transfer = t.id) AS jumlah_edit', false)
            ->from('tbltoplevel_transfer t')->order_by('t.id', 'DESC')->limit($ukuran, ($halaman - 1) * $ukuran)->get()->result_array();
        foreach ($rows as &$r) {
            $r['waktu_input'] = $this->waktu_input($r);
        }
        unset($r);

        $hitung = array('arah' => array('SEMUA' => 0, 'MASUK' => 0, 'KELUAR' => 0), 'sumber' => array_fill_keys(self::SUMBER_TRANSFER, 0));
        $saring(false, true);
        foreach ($this->db->select('t.arah, COUNT(*) AS n', false)->from('tbltoplevel_transfer t')->group_by('t.arah')->get()->result() as $x) {
            if (isset($hitung['arah'][$x->arah])) $hitung['arah'][$x->arah] = (int) $x->n;
            $hitung['arah']['SEMUA'] += (int) $x->n;
        }
        $saring(true, false);
        foreach ($this->db->select('t.sumber, COUNT(*) AS n', false)->from('tbltoplevel_transfer t')->group_by('t.sumber')->get()->result() as $x) {
            if (isset($hitung['sumber'][$x->sumber])) $hitung['sumber'][$x->sumber] = (int) $x->n;
        }

        return array('rows' => $rows, 'total' => $total, 'halaman' => $halaman, 'ukuran' => $ukuran, 'hitung' => $hitung);
    }

    /**
     * Waktu petugas input mengisi qty. Transfer baru menyimpannya di diinput_at. Transfer lama hanya punya tanggal; jam
     * dibuat_at dipakai untuk sumber selain REQUEST (dibuat_at transfer REQUEST = saat request diajukan, bukan saat qty diisi).
     */
    private function waktu_input(array $t)
    {
        if (!empty($t['diinput_at'])) {
            return $t['diinput_at'];
        }
        if ($t['sumber'] !== 'REQUEST' && !empty($t['dibuat_at']) && substr($t['dibuat_at'], 0, 10) === $t['tgl_input']) {
            return $t['dibuat_at'];
        }
        return $t['tgl_input'];
    }

    /**
     * Batalkan satu transfer (soft-delete): stok setiap baris dikembalikan seperti sebelum transfer (qty dikurangi untuk
     * MASUK, ditambah untuk KELUAR), transfer ditandai batal dan nomornya bebas dipakai lagi. Tidak ada DELETE; barisnya
     * tetap di tbltoplevel_transfer(_baris) dan jejaknya masuk tbltoplevel_log. Menolak bila stok akan menjadi negatif.
     *
     * @return array ['ok' => bool, 'pesan' => string]
     */
    public function batalkan_transfer($nomor, $oleh, $alasan = '')
    {
        $this->db->trans_start();
        $t = $this->db->query('SELECT * FROM tbltoplevel_transfer WHERE nomor = ? AND aktif_key = 1 FOR UPDATE', array($nomor))->row_array();
        if (!$t) {
            $this->db->trans_complete();
            return array('ok' => false, 'pesan' => 'Transfer ' . $nomor . ' tidak ditemukan atau sudah dibatalkan.');
        }
        $baris = $this->db->query('SELECT sku, no_rak, qty FROM tbltoplevel_transfer_baris WHERE id_transfer = ? AND dibatalkan = 0', array((int) $t['id']))->result_array();
        foreach ($baris as $b) {
            // MASUK (qty positif) dan KELUAR baru (qty minus): kembalikan dengan mengurangi qty; KELUAR lama (qty positif): menambah.
            $tanda = ($t['arah'] === 'MASUK' || (int) $b['qty'] < 0) ? -1 : 1;
            $s = $this->db->query('SELECT qty FROM tbltoplevel WHERE sku = ? AND no_rak = ? FOR UPDATE', array($b['sku'], $b['no_rak']))->row();
            $sebelum = $s ? (int) $s->qty : 0;
            $sesudah = $sebelum + $tanda * (int) $b['qty'];
            if (!$s || $sesudah < 0) {
                $this->db->trans_rollback();
                return array('ok' => false, 'pesan' => 'Stok ' . $b['sku'] . ' di rak ' . $b['no_rak'] . ' tidak cukup untuk membatalkan (' . $sebelum . ' pcs sekarang).');
            }
            $this->db->query('UPDATE tbltoplevel SET qty = ?, updated_at = NOW() WHERE sku = ? AND no_rak = ?', array($sesudah, $b['sku'], $b['no_rak']));
            $this->catat('BATAL_TRANSFER', $b['sku'], $b['no_rak'], $sebelum, $sesudah, $oleh, $nomor, 'Batal transfer ' . $nomor);
        }
        $this->db->query(
            'UPDATE tbltoplevel_transfer SET aktif_key = NULL, batal_at = NOW(), batal_by = ?, batal_alasan = ? WHERE id = ?',
            array($oleh, mb_substr((string) $alasan, 0, 255), (int) $t['id'])
        );
        $this->db->query(
            'UPDATE tbltoplevel_nomor SET urut = (SELECT COALESCE(MAX(urut), 0) FROM tbltoplevel_transfer WHERE tahun = ? AND aktif_key = 1) WHERE tahun = ?',
            array((int) $t['tahun'], (int) $t['tahun'])
        );
        $this->catat('BATAL_TRANSFER', null, null, null, null, $oleh, $nomor, 'Transfer ' . $nomor . ' dibatalkan: ' . $alasan);
        $this->db->trans_complete();
        return array('ok' => $this->db->trans_status() !== false, 'pesan' => 'Transfer ' . $nomor . ' dibatalkan.');
    }

    /**
     * Satu transfer beserta baris aktifnya per SKU dan rak dan riwayat editnya. NULL bila tidak ada.
     * bisa_edit: transfer sudah punya baris (request yang belum diisi qty-nya belum bisa diedit).
     * edit_baris: baris transfer REQUEST tidak boleh diedit di sini, karena hasil request (tbltoplevel_request.hasil) ikut
     * berubah; petugasnya tetap boleh. Qty request yang salah dikoreksi lewat menu Stok Top Level.
     */
    public function detail_transfer($id)
    {
        $t = $this->db->get_where('tbltoplevel_transfer', array('id' => (int) $id, 'aktif_key' => 1))->row_array();
        if (!$t) {
            return null;
        }
        $t['baris'] = $this->db->query(
            'SELECT id, sku, no_rak AS rak, ABS(qty) AS qty, qty_sebelum AS sebelum, qty_sesudah AS sesudah
             FROM tbltoplevel_transfer_baris WHERE id_transfer = ? AND dibatalkan = 0 ORDER BY id', array((int) $id)
        )->result_array();
        $t['waktu_input'] = $this->waktu_input($t);
        $t['bisa_edit'] = count($t['baris']) > 0;
        $t['edit_baris'] = $t['sumber'] !== 'REQUEST';
        $t['riwayat'] = array();
        foreach ($this->db->query('SELECT waktu, oleh, alasan, perubahan FROM tbltoplevel_transfer_edit WHERE id_transfer = ? ORDER BY id DESC', array((int) $id))->result_array() as $e) {
            $e['perubahan'] = json_decode($e['perubahan'], true) ?: array();
            $t['riwayat'][] = $e;
        }
        return $t;
    }

    /**
     * Edit transfer: nama petugas list dan petugas input, serta (kecuali transfer REQUEST) baris SKU, rak, dan qty:
     * ubah, tambah, atau hapus baris. Stok tbltoplevel digeser sesuai selisih (efek lama dibalik, efek baru diterapkan),
     * ditolak bila ada rak yang stoknya menjadi negatif. Baris yang dihapus cukup ditandai dibatalkan (tidak ada DELETE).
     * Setiap edit dicatat di tbltoplevel_transfer_edit (alasan wajib) dan tbltoplevel_log. Seluruhnya satu transaksi.
     *
     * @param array $baris [['id'=>id baris atau '' untuk baris baru, 'sku'=>, 'rak'=>, 'qty'=>], ...]
     * @return array ['ok'=>bool, 'pesan'=>string, 'nomor'=>string]
     */
    public function edit_transfer($id, $nama_tim, $nama_input, array $baris, $alasan, $oleh)
    {
        $alasan = trim(preg_replace('/\s+/', ' ', (string) $alasan));
        if (mb_strlen($alasan) < 5) {
            return array('ok' => false, 'pesan' => 'Alasan perubahan wajib diisi (minimal 5 huruf).');
        }
        $alasan = mb_substr($alasan, 0, 255);
        $nama_tim = trim(preg_replace('/\s+/', ' ', (string) $nama_tim));
        $nama_input = trim(preg_replace('/\s+/', ' ', (string) $nama_input));
        if (mb_strlen($nama_tim) > 150 || mb_strlen($nama_input) > 150) {
            return array('ok' => false, 'pesan' => 'Nama petugas maksimal 150 huruf.');
        }

        $t0 = $this->db->get_where('tbltoplevel_transfer', array('id' => (int) $id, 'aktif_key' => 1))->row_array();
        if (!$t0) {
            return array('ok' => false, 'pesan' => 'Transfer tidak ditemukan atau sudah dibatalkan.');
        }
        $edit_baris = $t0['sumber'] !== 'REQUEST';

        // Normalisasi baris kiriman.
        $baru = array();
        $seen = array();
        $sku_terlibat = array();
        if ($edit_baris) {
            foreach ($baris as $x) {
                $bid = isset($x['id']) && $x['id'] !== '' ? (int) $x['id'] : 0;
                $sku = trim((string) (isset($x['sku']) ? $x['sku'] : ''));
                $rak = strtoupper(trim((string) (isset($x['rak']) ? $x['rak'] : '')));
                $qty = isset($x['qty']) ? $x['qty'] : '';
                if ($sku === '' || $rak === '') {
                    return array('ok' => false, 'pesan' => 'SKU dan no rak tiap baris wajib diisi.');
                }
                if (mb_strlen($rak) > 100) {
                    return array('ok' => false, 'pesan' => 'No rak maksimal 100 huruf.');
                }
                if (!is_numeric($qty) || (int) $qty != $qty || $qty < 1) {
                    return array('ok' => false, 'pesan' => 'Qty ' . $sku . ' harus bilangan bulat 1 atau lebih.');
                }
                $ada = $this->db->query('SELECT id_sku FROM tblsku WHERE id_sku = ? LIMIT 1', array($sku))->row();
                if (!$ada) {
                    return array('ok' => false, 'pesan' => 'SKU ' . $sku . ' tidak ada di master (Jubelio).');
                }
                $sku = $ada->id_sku;
                $kunci_baris = $sku . '|' . $rak;
                if (isset($seen[$kunci_baris])) {
                    return array('ok' => false, 'pesan' => 'SKU ' . $sku . ' di rak ' . $rak . ' muncul dua kali. Gabungkan jadi satu baris.');
                }
                $seen[$kunci_baris] = true;
                $baru[] = array('id' => $bid, 'sku' => $sku, 'rak' => $rak, 'qty' => (int) $qty);
                $sku_terlibat[$sku] = true;
            }
            if (!$baru) {
                return array('ok' => false, 'pesan' => 'Transfer minimal punya satu baris.');
            }
        }
        foreach ($this->db->query('SELECT DISTINCT sku FROM tbltoplevel_transfer_baris WHERE id_transfer = ? AND dibatalkan = 0', array((int) $id))->result() as $r) {
            $sku_terlibat[$r->sku] = true;
        }

        // Kunci semua SKU yang terlibat, berurutan supaya tidak saling menunggu.
        $nama_kunci = array_keys($sku_terlibat);
        sort($nama_kunci);
        $kunci = array();
        foreach ($nama_kunci as $k) {
            $l = $this->kunci($k);
            if (!$l) {
                foreach ($kunci as $dipegang) {
                    $this->lepas($dipegang);
                }
                return array('ok' => false, 'pesan' => 'Sistem sibuk, coba lagi.');
            }
            $kunci[] = $l;
        }

        try {
            $this->db->trans_start();
            $t = $this->db->query('SELECT * FROM tbltoplevel_transfer WHERE id = ? AND aktif_key = 1 FOR UPDATE', array((int) $id))->row_array();
            if (!$t) {
                $this->db->trans_rollback();
                return array('ok' => false, 'pesan' => 'Transfer tidak ditemukan atau sudah dibatalkan.');
            }
            $nomor = $t['nomor'];
            $lama = array();
            foreach ($this->db->query('SELECT * FROM tbltoplevel_transfer_baris WHERE id_transfer = ? AND dibatalkan = 0 ORDER BY id FOR UPDATE', array((int) $id))->result_array() as $b) {
                $lama[(int) $b['id']] = $b;
            }
            if (!$lama) {
                $this->db->trans_rollback();
                return array('ok' => false, 'pesan' => 'Transfer ini belum punya baris, belum bisa diedit.');
            }
            $perubahan = array();

            // Petugas.
            foreach (array(array('nama_tim', $nama_tim, 'Petugas list'), array('nama_input', $nama_input, 'Petugas input')) as $p) {
                $sebelum = (string) $t[$p[0]];
                if ($p[1] === '' && $sebelum !== '') {
                    $this->db->trans_rollback();
                    return array('ok' => false, 'pesan' => $p[2] . ' tidak boleh dikosongkan.');
                }
                if ($p[1] !== $sebelum) {
                    $perubahan[] = $p[2] . ': ' . ($sebelum !== '' ? $sebelum : '—') . ' → ' . $p[1];
                }
            }

            $tanda = $t['arah'] === 'MASUK' ? 1 : -1;
            $tulis = array();      // baris yang berubah atau baru: id (0 = baru) => data
            $dihapus = array();    // id baris yang dilepas
            $selisih = array();    // "sku|rak" => perubahan stok bersih
            if ($edit_baris) {
                $ada_id = array();
                foreach ($baru as $b) {
                    if ($b['id'] > 0) {
                        if (!isset($lama[$b['id']]) || isset($ada_id[$b['id']])) {
                            $this->db->trans_rollback();
                            return array('ok' => false, 'pesan' => 'Data transfer sudah berubah. Muat ulang lalu coba lagi.');
                        }
                        $ada_id[$b['id']] = true;
                    }
                }
                foreach ($lama as $bid => $b) {
                    $key = $b['sku'] . '|' . $b['no_rak'];
                    $selisih[$key] = (isset($selisih[$key]) ? $selisih[$key] : 0) - $tanda * abs((int) $b['qty']);
                    if (!isset($ada_id[$bid])) {
                        $dihapus[] = $bid;
                        $perubahan[] = 'Baris dihapus: ' . $b['sku'] . ' @ ' . $b['no_rak'] . ' (' . abs((int) $b['qty']) . ' pcs)';
                    }
                }
                foreach ($baru as $b) {
                    $key = $b['sku'] . '|' . $b['rak'];
                    $selisih[$key] = (isset($selisih[$key]) ? $selisih[$key] : 0) + $tanda * $b['qty'];
                    if ($b['id'] === 0) {
                        $tulis[] = $b;
                        $perubahan[] = 'Baris baru: ' . $b['sku'] . ' @ ' . $b['rak'] . ' (' . $b['qty'] . ' pcs)';
                        continue;
                    }
                    $o = $lama[$b['id']];
                    $uraian = array();
                    if ($o['sku'] !== $b['sku']) $uraian[] = 'SKU ' . $o['sku'] . ' → ' . $b['sku'];
                    if ($o['no_rak'] !== $b['rak']) $uraian[] = 'rak ' . $o['no_rak'] . ' → ' . $b['rak'];
                    if (abs((int) $o['qty']) !== $b['qty']) $uraian[] = 'qty ' . abs((int) $o['qty']) . ' → ' . $b['qty'];
                    if ($uraian) {
                        $tulis[] = $b;
                        $perubahan[] = $o['sku'] . ' @ ' . $o['no_rak'] . ': ' . implode(', ', $uraian);
                    }
                }
            }
            if (!$perubahan) {
                $this->db->trans_rollback();
                return array('ok' => false, 'pesan' => 'Tidak ada perubahan.');
            }

            // Geser stok: hanya rak yang selisihnya bukan nol; tolak bila ada yang menjadi negatif.
            $stok_awal = array();
            $stok_akhir = array();
            foreach ($selisih as $key => $d) {
                list($sku, $rak) = explode('|', $key, 2);
                $s = $this->db->query('SELECT qty FROM tbltoplevel WHERE sku = ? AND no_rak = ? FOR UPDATE', array($sku, $rak))->row();
                $awal = $s ? (int) $s->qty : 0;
                $stok_awal[$key] = $awal;
                $stok_akhir[$key] = $awal + $d;
                if ($stok_akhir[$key] < 0) {
                    $this->db->trans_rollback();
                    return array('ok' => false, 'pesan' => 'Stok ' . $sku . ' di rak ' . $rak . ' tidak cukup untuk perubahan ini (stok sekarang ' . $awal . ' pcs, menjadi ' . $stok_akhir[$key] . ').');
                }
            }
            foreach ($selisih as $key => $d) {
                if ($d === 0) {
                    continue;
                }
                list($sku, $rak) = explode('|', $key, 2);
                $this->db->query(
                    'INSERT INTO tbltoplevel (sku, no_rak, qty, updated_at) VALUES (?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE qty = VALUES(qty), updated_at = VALUES(updated_at)',
                    array($sku, $rak, $stok_akhir[$key])
                );
                $this->catat('EDIT_TRANSFER', $sku, $rak, $stok_awal[$key], $stok_akhir[$key], $oleh, $nomor, 'Edit transfer ' . $nomor . ': ' . $alasan);
            }

            // Baris: lepas yang dihapus, ubah yang berubah (qty disimpan bertanda: MASUK plus, KELUAR minus), tambah yang baru.
            foreach ($dihapus as $bid) {
                $this->db->query('UPDATE tbltoplevel_transfer_baris SET dibatalkan = 1 WHERE id = ?', array($bid));
            }
            foreach ($tulis as $b) {
                // Catatan stok baris tetap koheren dengan transfernya (sebelum + qty = sesudah): baris yang masih di SKU dan rak
                // yang sama mempertahankan "sebelum" aslinya, baris yang pindah rak/SKU atau baru memakai stok saat edit.
                $key = $b['sku'] . '|' . $b['rak'];
                $sebelum_baris = $stok_awal[$key];
                if ($b['id'] > 0 && $lama[$b['id']]['sku'] === $b['sku'] && $lama[$b['id']]['no_rak'] === $b['rak']) {
                    $sebelum_baris = (int) $lama[$b['id']]['qty_sebelum'];
                }
                $data = array('sku' => $b['sku'], 'no_rak' => $b['rak'], 'qty' => $tanda * $b['qty'],
                    'qty_sebelum' => $sebelum_baris, 'qty_sesudah' => $sebelum_baris + $tanda * $b['qty']);
                if ($b['id'] > 0) {
                    $this->db->where('id', $b['id'])->update('tbltoplevel_transfer_baris', $data);
                } else {
                    $data['id_transfer'] = (int) $id;
                    $this->db->insert('tbltoplevel_transfer_baris', $data);
                }
            }
            $r = $this->db->query('SELECT COUNT(*) AS n, COALESCE(SUM(qty), 0) AS q FROM tbltoplevel_transfer_baris WHERE id_transfer = ? AND dibatalkan = 0', array((int) $id))->row();
            $this->db->where('id', (int) $id)->update('tbltoplevel_transfer', array(
                'nama_tim' => $nama_tim !== '' ? $nama_tim : $t['nama_tim'],
                'nama_input' => $nama_input !== '' ? $nama_input : $t['nama_input'],
                'jumlah_baris' => (int) $r->n, 'jumlah_qty' => (int) $r->q,
            ));
            $this->db->insert('tbltoplevel_transfer_edit', array(
                'id_transfer' => (int) $id, 'nomor' => $nomor, 'waktu' => date('Y-m-d H:i:s'), 'oleh' => mb_substr((string) $oleh, 0, 100),
                'alasan' => $alasan, 'perubahan' => json_encode($perubahan, JSON_UNESCAPED_UNICODE),
            ));
            $this->catat('EDIT_TRANSFER', null, null, null, null, $oleh, $nomor, 'Transfer ' . $nomor . ' diedit (' . count($perubahan) . ' perubahan): ' . $alasan);
            $this->db->trans_complete();
            if ($this->db->trans_status() === false) {
                return array('ok' => false, 'pesan' => 'Penyimpanan gagal; tidak ada yang berubah.');
            }
            return array('ok' => true, 'pesan' => 'Transfer ' . $nomor . ' diperbarui.', 'nomor' => $nomor);
        } finally {
            foreach ($kunci as $dipegang) {
                $this->lepas($dipegang);
            }
        }
    }

    /** Peta ref (RT-0001 / UP-0001) => nomor transfer untuk satu sumber. */
    private function nomor_transfer_per_ref($sumber)
    {
        $peta = array();
        foreach ($this->db->select('ref, nomor')->where(array('sumber' => $sumber, 'aktif_key' => 1))->get('tbltoplevel_transfer')->result() as $r) {
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
