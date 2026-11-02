<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cek barang gedung baru (GD-BR): Scan Resi GD-BR dan Rekap Resi GD-BR (docs/GDBR.md).
 *
 * Tiga tahap, masing-masing satu scan:
 *   RESI         tim resi menandai resi sebagai resi gedung baru (satu kali; resi yang sudah
 *                masuk tidak bisa discan lagi). Status awal: Belum dicek GD-BR.
 *   GEDUNG_BARU  pengecek gedung baru mencocokkan barang dengan resi.
 *   PACKER       packer mencocokkan ulang sebelum dipacking.
 * Data picker (no absen, nama, tanggal diambil) dibaca dari scan picker yang sudah ada
 * (tblresiambilbarang); model ini tidak pernah mengubahnya.
 */
class Gdbr_fcd extends CI_Model
{
    const TAHAP_RESI   = 'RESI';
    const TAHAP_GB     = 'GEDUNG_BARU';
    const TAHAP_PACKER = 'PACKER';

    /** Batas baris per periode di Rekap (pengaman memori). */
    const BATAS_REKAP = 20000;

    // ─────────────────────────────────────────────
    //  Nama pegawai: "NAMA - ROLE - 0426"
    // ─────────────────────────────────────────────

    /** @return array ['absen' => '0426', 'nama' => 'YUSUF', 'role' => 'PICKER'] */
    public static function urai_pegawai($teks)
    {
        $teks = trim((string) $teks);
        if (preg_match('/^(.*?)\s*-\s*(.*?)\s*-\s*(\d+)\s*$/u', $teks, $m)) {
            return ['absen' => $m[3], 'nama' => trim($m[1]), 'role' => trim($m[2])];
        }
        if (preg_match('/^(\d+)\s*-\s*(.+)$/u', $teks, $m)) {
            return ['absen' => $m[1], 'nama' => trim($m[2]), 'role' => ''];
        }
        return ['absen' => '', 'nama' => $teks, 'role' => ''];
    }

    /** "0426 - YUSUF - PICKER" (role dilewati bila kosong). */
    public static function label($absen, $nama, $role = '')
    {
        $absen = trim((string) $absen);
        if ($absen !== '' && ctype_digit($absen)) {
            $absen = str_pad($absen, 4, '0', STR_PAD_LEFT);
        }
        $bagian = array_filter([$absen, mb_strtoupper(trim((string) $nama), 'UTF-8'), mb_strtoupper(trim((string) $role), 'UTF-8')], function ($x) {
            return $x !== '';
        });
        return implode(' - ', $bagian);
    }

    /** Petugas dari akun yang login (tbluser.name = "NAMA - ROLE - ABSEN"). */
    public function petugas_dari_user(array $user)
    {
        $p = self::urai_pegawai(isset($user['name']) ? $user['name'] : '');
        if ($p['nama'] === '') {
            $p['nama'] = isset($user['username']) ? $user['username'] : '';
        }
        return [
            'id'    => (int) $user['id_user'],
            'absen' => $p['absen'],
            'nama'  => mb_strtoupper($p['nama'], 'UTF-8'),
            'role'  => mb_strtoupper($p['role'], 'UTF-8'),
            'label' => self::label($p['absen'], $p['nama'], $p['role']),
        ];
    }

    private function orang($absen, $nama, $role)
    {
        return ['absen' => $absen, 'nama' => $nama, 'role' => $role, 'label' => self::label($absen, $nama, $role)];
    }

    private function sekarang()
    {
        return date('Y-m-d H:i:s');
    }

    // ─────────────────────────────────────────────
    //  Pencarian resi, picker, isi resi
    // ─────────────────────────────────────────────

    /** Cari resi dari no. resi atau no. pesanan. */
    public function cari_resi($teks)
    {
        $teks = trim((string) $teks);
        if ($teks === '') {
            return null;
        }
        if (function_exists('pastikan_resi_live')) {
            pastikan_resi_live($teks);
        }
        $r = $this->db->get_where('tblprintresi', ['noresi' => $teks])->row();
        if ($r) {
            return $r;
        }
        $d = $this->db->select('id_resi')->limit(1)->get_where('tbldetailprintresi', ['no_pesanan' => $teks])->row();
        return $d ? $this->db->get_where('tblprintresi', ['id_printresi' => $d->id_resi])->row() : null;
    }

    private function resi_batal($resi)
    {
        return strtoupper(trim((string) $resi->status_pesanan)) === 'CANCELED' || (string) $resi->batal === '1';
    }

    /** Data scan picker untuk satu resi (null bila belum discan picker). */
    private function data_picker($id_resi)
    {
        $a = $this->db
            ->select('a.tanggal_resiambilbarang AS waktu, p.nama_pegawai')
            ->from('tblresiambilbarang a')
            ->join('tblpegawai p', 'p.kode_pegawai = a.yangambil_pegawai', 'left')
            ->where('a.id_resi', (int) $id_resi)
            ->limit(1)->get()->row();
        if (!$a) {
            return null;
        }
        $p = self::urai_pegawai($a->nama_pegawai);
        return $this->orang($p['absen'], $p['nama'], $p['role']) + ['waktu' => $a->waktu];
    }

    private function nama_marketplace($id_mp)
    {
        $m = $this->db->select('nama_marketplace')->get_where('tblmarketplace', ['id_marketplace' => (int) $id_mp])->row();
        return $m ? trim($m->nama_marketplace) : '';
    }

    private function info_resi($resi)
    {
        $d = $this->db->select('no_pesanan')->order_by('id_detail_resi', 'ASC')->limit(1)->get_where('tbldetailprintresi', ['id_resi' => (int) $resi->id_printresi])->row();
        return [
            'id_resi' => (int) $resi->id_printresi,
            'noresi'  => $resi->noresi,
            'pesanan' => $d ? $d->no_pesanan : '',
            'toko'    => trim((string) $resi->toko),
            'mp'      => $this->nama_marketplace($resi->id_marketplace),
        ];
    }

    /** Foto SKU untuk klien: url asli + salinan lokal (assets/js/foto_sku.js memilih yang bisa dimuat). */
    private function tambah_foto(array $row)
    {
        $row['foto']       = trim((string) (isset($row['link_foto']) ? $row['link_foto'] : ''));
        $row['foto_lokal'] = foto_sku_lokal_url(isset($row['link_foto']) ? $row['link_foto'] : '', isset($row['foto_lokal']) ? $row['foto_lokal'] : '');
        unset($row['link_foto']);
        return $row;
    }

    /** Isi resi digabung per SKU: sku, no_rak, qty, foto. */
    public function isi_resi($id_resi)
    {
        $rows = $this->db
            ->select('d.sku, MAX(d.no_rak) AS no_rak, SUM(d.jumlah) AS qty, MAX(s.link_foto) AS link_foto, MAX(s.foto_lokal) AS foto_lokal', false)
            ->from('tbldetailprintresi d')
            ->join('tblsku s', 's.id_sku = d.sku', 'left')
            ->where('d.id_resi', (int) $id_resi)
            ->group_by('d.sku')
            ->order_by('MAX(d.no_rak)', 'ASC', false)
            ->order_by('d.sku', 'ASC')
            ->get()->result_array();
        foreach ($rows as &$r) {
            $r['qty'] = (int) $r['qty'];
            $r = $this->tambah_foto($r);
        }
        return $rows;
    }

    /** Info SKU (rak + foto) untuk SKU di luar resi. */
    private function info_sku(array $daftar)
    {
        $hasil = [];
        if (!$daftar) {
            return $hasil;
        }
        $rows = $this->db->select('id_sku, no_rak, link_foto, foto_lokal')->where_in('id_sku', array_values(array_unique($daftar)))->get('tblsku')->result_array();
        foreach ($rows as $r) {
            $hasil[strtoupper($r['id_sku'])] = $r;
        }
        return $hasil;
    }

    private function tandai_row($id_resi)
    {
        return $this->db->get_where('tblgdbr_resi', ['id_resi' => (int) $id_resi])->row();
    }

    private function cek_row($id_gdbr, $tahap)
    {
        return $this->db->get_where('tblgdbr_cek', ['id_gdbr' => (int) $id_gdbr, 'tahap' => $tahap])->row();
    }

    private function cek_items($id_cek)
    {
        return $this->db->order_by('di_luar_resi', 'ASC')->order_by('id_item', 'ASC')->get_where('tblgdbr_cek_item', ['id_cek' => (int) $id_cek])->result_array();
    }

    private function ringkas_cek($c)
    {
        if (!$c) {
            return null;
        }
        return $this->orang($c->petugas_absen, $c->petugas_nama, $c->petugas_role) + [
            'waktu'    => $c->waktu_scan,
            'kurang'   => (int) $c->qty_kurang,
            'lebih'    => (int) $c->qty_lebih,
            'salah'    => (int) $c->qty_salah,
            'lain'     => (int) $c->lain,
            'sesuai'   => (int) $c->sesuai,
            'catatan'  => (string) $c->catatan,
        ];
    }

    // ─────────────────────────────────────────────
    //  Scan
    // ─────────────────────────────────────────────

    /**
     * Hasil scan satu resi pada satu tahap. 'status':
     * tidak_ada | belum_picker | batal | sudah | belum_tandai | belum_gb | ok
     */
    public function scan($tahap, $teks, array $petugas, $komputer)
    {
        if (!in_array($tahap, [self::TAHAP_RESI, self::TAHAP_GB, self::TAHAP_PACKER], true)) {
            return ['status' => 'tahap_salah'];
        }
        $resi = $this->cari_resi($teks);
        if (!$resi) {
            return ['status' => 'tidak_ada', 'teks' => trim((string) $teks)];
        }
        $info   = $this->info_resi($resi);
        $picker = $this->data_picker($resi->id_printresi);
        if (!$picker) {
            return ['status' => 'belum_picker', 'noresi' => $resi->noresi];
        }
        $tandai = $this->tandai_row($resi->id_printresi);

        if ($tahap === self::TAHAP_RESI) {
            if ($tandai) {
                return [
                    'status' => 'sudah', 'noresi' => $resi->noresi,
                    'oleh'   => self::label($tandai->petugas_absen, $tandai->petugas_nama, $tandai->petugas_role),
                    'waktu'  => $tandai->ditandai_at,
                ];
            }
            if ($this->resi_batal($resi)) {
                return ['status' => 'batal', 'noresi' => $resi->noresi];
            }
            return $this->tandai_resi($resi, $petugas);
        }

        if (!$tandai) {
            return ['status' => 'belum_tandai', 'noresi' => $resi->noresi];
        }
        $cek_sendiri = $this->cek_row($tandai->id_gdbr, $tahap);
        if ($cek_sendiri) {
            return [
                'status' => 'sudah', 'noresi' => $resi->noresi,
                'oleh'   => self::label($cek_sendiri->petugas_absen, $cek_sendiri->petugas_nama, $cek_sendiri->petugas_role),
                'waktu'  => $cek_sendiri->waktu_scan,
            ];
        }
        $gb = $this->cek_row($tandai->id_gdbr, self::TAHAP_GB);
        if ($tahap === self::TAHAP_PACKER && !$gb) {
            return ['status' => 'belum_gb', 'noresi' => $resi->noresi];
        }

        $hasil = [
            'status'     => 'ok',
            'resi'       => $info,
            'picker'     => $picker,
            'tandai'     => $this->orang($tandai->petugas_absen, $tandai->petugas_nama, $tandai->petugas_role) + ['waktu' => $tandai->ditandai_at],
            'isi'        => $this->isi_resi($resi->id_printresi),
            'gb'         => null,
        ];
        if ($gb) {
            $per_sku = [];
            $luar    = [];
            foreach ($this->cek_items($gb->id_cek) as $it) {
                if ((int) $it['di_luar_resi'] === 1) {
                    $luar[] = ['sku' => $it['sku'], 'qty' => (int) $it['qty_ada']];
                } else {
                    $per_sku[$it['sku']] = (int) $it['qty_ada'];
                }
            }
            $hasil['gb'] = $this->ringkas_cek($gb) + ['items' => $per_sku, 'luar' => $luar];
        }
        return $hasil;
    }

    /** Tahap RESI: simpan langsung (tanpa form). */
    private function tandai_resi($resi, array $petugas)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $waktu = $this->sekarang();
        $ok = $this->db->insert('tblgdbr_resi', [
            'id_resi'       => (int) $resi->id_printresi,
            'noresi'        => $resi->noresi,
            'ditandai_oleh' => $petugas['id'],
            'petugas_absen' => $petugas['absen'],
            'petugas_nama'  => $petugas['nama'],
            'petugas_role'  => $petugas['role'],
            'ditandai_at'   => $waktu,
        ]);
        $err = $this->db->error();
        $this->db->db_debug = $debug;

        if (!$ok) {
            if ((int) $err['code'] === 1062) {
                $t = $this->tandai_row($resi->id_printresi);
                return [
                    'status' => 'sudah', 'noresi' => $resi->noresi,
                    'oleh'   => $t ? self::label($t->petugas_absen, $t->petugas_nama, $t->petugas_role) : '',
                    'waktu'  => $t ? $t->ditandai_at : $waktu,
                ];
            }
            return ['status' => 'gagal', 'noresi' => $resi->noresi];
        }
        return ['status' => 'ok_tandai', 'noresi' => $resi->noresi, 'oleh' => $petugas['label'], 'waktu' => $waktu];
    }

    // ─────────────────────────────────────────────
    //  Simpan pengecekan (GEDUNG_BARU / PACKER)
    // ─────────────────────────────────────────────

    /**
     * @param array $items  [['sku' => ..., 'qty_ada' => n], ...] untuk SKU di resi
     * @param array $extra  [['sku' => ..., 'qty' => n], ...] untuk SKU di luar resi
     * @return array ['ok' => bool, 'message' => string, ...]
     */
    public function simpan($tahap, $noresi, array $items, array $extra, $lain, $catatan, array $petugas, $komputer)
    {
        if (!in_array($tahap, [self::TAHAP_GB, self::TAHAP_PACKER], true)) {
            return ['ok' => false, 'message' => 'Tahap tidak dikenal.'];
        }
        $resi = $this->cari_resi($noresi);
        if (!$resi) {
            return ['ok' => false, 'message' => 'Resi tidak ditemukan.'];
        }
        $tandai = $this->tandai_row($resi->id_printresi);
        if (!$tandai) {
            return ['ok' => false, 'message' => 'Resi belum ditandai tim resi. Scan di tahap RESI dulu.'];
        }
        if ($this->cek_row($tandai->id_gdbr, $tahap)) {
            return ['ok' => false, 'message' => 'Resi ini sudah dicek di tahap ini.'];
        }
        if ($tahap === self::TAHAP_PACKER && !$this->cek_row($tandai->id_gdbr, self::TAHAP_GB)) {
            return ['ok' => false, 'message' => 'Resi belum dicek di gedung baru.'];
        }

        // Qty resi selalu dari database, bukan dari kiriman klien.
        $isi = [];
        foreach ($this->isi_resi($resi->id_printresi) as $r) {
            $isi[strtoupper($r['sku'])] = $r;
        }
        $ada = [];
        foreach ($items as $it) {
            $kode = strtoupper(trim((string) (isset($it['sku']) ? $it['sku'] : '')));
            $q    = isset($it['qty_ada']) ? $it['qty_ada'] : null;
            if ($kode === '' || !isset($isi[$kode]) || !is_numeric($q) || (int) $q < 0 || (int) $q > 99999) {
                return ['ok' => false, 'message' => 'Data barang tidak sah. Muat ulang resi lalu coba lagi.'];
            }
            $ada[$kode] = (int) $q;
        }
        if (count($ada) !== count($isi)) {
            return ['ok' => false, 'message' => 'Ada SKU resi yang belum diisi. Muat ulang resi lalu coba lagi.'];
        }

        $luar = [];
        foreach ($extra as $e) {
            $kode = strtoupper(trim((string) (isset($e['sku']) ? $e['sku'] : '')));
            $q    = isset($e['qty']) ? $e['qty'] : null;
            if ($kode === '' || !is_numeric($q) || (int) $q < 1 || (int) $q > 99999) {
                return ['ok' => false, 'message' => 'Barang di luar resi tidak sah (SKU kosong atau qty kurang dari 1).'];
            }
            if (isset($isi[$kode])) {
                return ['ok' => false, 'message' => 'SKU ' . $kode . ' ada di resi: ubah Qty ada di tabel atas.'];
            }
            $luar[$kode] = (isset($luar[$kode]) ? $luar[$kode] : 0) + (int) $q;
        }
        $info_luar = $this->info_sku(array_keys($luar));
        foreach (array_keys($luar) as $kode) {
            if (!isset($info_luar[$kode])) {
                return ['ok' => false, 'message' => 'SKU ' . $kode . ' tidak ditemukan.'];
            }
        }

        $kurang = $lebih = $salah = 0;
        foreach ($isi as $kode => $r) {
            $d = $ada[$kode] - $r['qty'];
            if ($d < 0) {
                $kurang -= $d;
            } else {
                $lebih += $d;
            }
        }
        foreach ($luar as $q) {
            $salah += $q;
        }
        $lain   = $lain ? 1 : 0;
        $sesuai = (!$kurang && !$lebih && !$salah && !$lain) ? 1 : 0;

        $catatan = trim((string) $catatan);
        if (!$sesuai && $catatan === '') {
            return ['ok' => false, 'message' => 'Ada yang tidak sesuai: isi catatan penyebabnya.'];
        }
        if (mb_strlen($catatan, 'UTF-8') > 255) {
            $catatan = mb_substr($catatan, 0, 255, 'UTF-8');
        }

        // Waktu cek = saat petugas menekan Simpan (setelah pratinjau), diambil dari jam server.
        $waktu = $this->sekarang();

        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->trans_start();
        $this->db->insert('tblgdbr_cek', [
            'id_gdbr'       => (int) $tandai->id_gdbr,
            'tahap'         => $tahap,
            'id_user'       => $petugas['id'],
            'petugas_absen' => $petugas['absen'],
            'petugas_nama'  => $petugas['nama'],
            'petugas_role'  => $petugas['role'],
            'waktu_scan'    => $waktu,
            'qty_kurang'    => $kurang,
            'qty_lebih'     => $lebih,
            'qty_salah'     => $salah,
            'lain'          => $lain,
            'sesuai'        => $sesuai,
            'catatan'       => $catatan === '' ? null : $catatan,
            'nama_komputer' => $komputer,
            'created_at'    => $this->sekarang(),
        ]);
        $id_cek = (int) $this->db->insert_id();
        if ($id_cek) {
            foreach ($isi as $kode => $r) {
                $this->db->insert('tblgdbr_cek_item', ['id_cek' => $id_cek, 'sku' => $r['sku'], 'qty_resi' => $r['qty'], 'qty_ada' => $ada[$kode], 'di_luar_resi' => 0]);
            }
            foreach ($luar as $kode => $q) {
                $this->db->insert('tblgdbr_cek_item', ['id_cek' => $id_cek, 'sku' => $info_luar[$kode]['id_sku'], 'qty_resi' => 0, 'qty_ada' => $q, 'di_luar_resi' => 1]);
            }
        }
        $this->db->trans_complete();
        $ok = $this->db->trans_status() && $id_cek > 0;
        $err = $this->db->error();
        $this->db->db_debug = $debug;

        if (!$ok) {
            if ((int) $err['code'] === 1062) {
                return ['ok' => false, 'message' => 'Resi ini baru saja dicek oleh petugas lain di tahap yang sama.'];
            }
            return ['ok' => false, 'message' => 'Gagal menyimpan. Coba scan ulang resi.'];
        }
        return [
            'ok' => true, 'message' => 'Tersimpan', 'noresi' => $resi->noresi, 'waktu' => $waktu, 'oleh' => $petugas['label'],
            'hasil' => ['kurang' => $kurang, 'lebih' => $lebih, 'salah' => $salah, 'lain' => $lain, 'sesuai' => $sesuai],
        ];
    }

    // ─────────────────────────────────────────────
    //  Rekap
    // ─────────────────────────────────────────────

    private function progres(array $baris)
    {
        if ($baris['pk']) {
            return 'Selesai dicek';
        }
        return $baris['gb'] ? 'Menunggu packer' : 'Belum dicek GD-BR';
    }

    private function cek_dari_join($r, $awalan)
    {
        if (empty($r[$awalan . '_id'])) {
            return null;
        }
        return $this->orang($r[$awalan . '_absen'], $r[$awalan . '_nama'], $r[$awalan . '_role']) + [
            'waktu'  => $r[$awalan . '_waktu'],
            'kurang' => (int) $r[$awalan . '_k'],
            'lebih'  => (int) $r[$awalan . '_l'],
            'salah'  => (int) $r[$awalan . '_x'],
            'lain'   => (int) $r[$awalan . '_o'],
            'sesuai' => (int) $r[$awalan . '_s'],
        ];
    }

    private function kolom_cek($alias, $awalan)
    {
        return "{$alias}.id_cek AS {$awalan}_id, {$alias}.petugas_absen AS {$awalan}_absen, {$alias}.petugas_nama AS {$awalan}_nama, {$alias}.petugas_role AS {$awalan}_role, "
            . "{$alias}.waktu_scan AS {$awalan}_waktu, {$alias}.qty_kurang AS {$awalan}_k, {$alias}.qty_lebih AS {$awalan}_l, {$alias}.qty_salah AS {$awalan}_x, "
            . "{$alias}.lain AS {$awalan}_o, {$alias}.sesuai AS {$awalan}_s";
    }

    /** Himpunan hasil cek sebuah baris: Sesuai/Kurang/Lebih/Salah/Lainnya/Belum dicek. */
    private function himpunan_hasil(array $b)
    {
        $s = [];
        foreach (['gb', 'pk'] as $k) {
            $c = $b[$k];
            if ($c) {
                if ($c['sesuai']) $s[] = 'Sesuai';
                if ($c['kurang']) $s[] = 'Kurang';
                if ($c['lebih'])  $s[] = 'Lebih';
                if ($c['salah'])  $s[] = 'Salah';
                if ($c['lain'])   $s[] = 'Lainnya';
            }
        }
        if (!$b['gb'] && !$b['pk']) {
            $s[] = 'Belum dicek';
        }
        return $s;
    }

    /**
     * Rekap resi GD-BR yang ditandai pada rentang tanggal. KPI mengikuti periode saja;
     * filter, pencarian, dan halaman dikerjakan di sini.
     *
     * @param array $f ['progres' => [], 'hasil' => [], 'picker' => [], 'marketplace' => [], 'toko' => []]
     */
    public function rekap($start, $end, array $f, $cari, $halaman, $per)
    {
        $kolom = 'g.id_gdbr, g.id_resi, g.noresi, g.ditandai_at, g.petugas_absen AS td_absen, g.petugas_nama AS td_nama, g.petugas_role AS td_role, '
            . 'pr.toko, pr.id_marketplace, ' . $this->kolom_cek('gb', 'gb') . ', ' . $this->kolom_cek('pk', 'pk');
        $rows = $this->db
            ->select($kolom, false)
            ->from('tblgdbr_resi g')
            ->join('tblprintresi pr', 'pr.id_printresi = g.id_resi', 'inner')
            ->join('tblgdbr_cek gb', "gb.id_gdbr = g.id_gdbr AND gb.tahap = 'GEDUNG_BARU'", 'left')
            ->join('tblgdbr_cek pk', "pk.id_gdbr = g.id_gdbr AND pk.tahap = 'PACKER'", 'left')
            ->where('g.ditandai_at >=', $start . ' 00:00:00')
            ->where('g.ditandai_at <=', $end . ' 23:59:59')
            ->order_by('g.ditandai_at', 'DESC')
            ->limit(self::BATAS_REKAP)
            ->get()->result_array();

        $ids = array_map('intval', array_column($rows, 'id_resi'));
        $picker = [];
        $detail = [];
        foreach (array_chunk($ids, 1000) as $potong) {
            $q = $this->db
                ->select('a.id_resi, a.tanggal_resiambilbarang AS waktu, p.nama_pegawai')
                ->from('tblresiambilbarang a')
                ->join('tblpegawai p', 'p.kode_pegawai = a.yangambil_pegawai', 'left')
                ->where_in('a.id_resi', $potong)->get()->result_array();
            foreach ($q as $x) {
                $u = self::urai_pegawai($x['nama_pegawai']);
                $picker[(int) $x['id_resi']] = $this->orang($u['absen'], $u['nama'], $u['role']) + ['waktu' => $x['waktu']];
            }
            $q = $this->db
                ->select('id_resi, MIN(no_pesanan) AS pesanan, COUNT(DISTINCT sku) AS n_sku, SUM(jumlah) AS qty', false)
                ->where_in('id_resi', $potong)->group_by('id_resi')->get('tbldetailprintresi')->result_array();
            foreach ($q as $x) {
                $detail[(int) $x['id_resi']] = $x;
            }
        }
        $mp = [];
        foreach ($this->db->get('tblmarketplace')->result_array() as $m) {
            $mp[(int) $m['id_marketplace']] = trim($m['nama_marketplace']);
        }

        $semua = [];
        foreach ($rows as $r) {
            $id = (int) $r['id_resi'];
            $d  = isset($detail[$id]) ? $detail[$id] : ['pesanan' => '', 'n_sku' => 0, 'qty' => 0];
            $b = [
                'noresi'  => $r['noresi'],
                'pesanan' => $d['pesanan'],
                'toko'    => trim((string) $r['toko']),
                'mp'      => isset($mp[(int) $r['id_marketplace']]) ? $mp[(int) $r['id_marketplace']] : '',
                'n_sku'   => (int) $d['n_sku'],
                'qty'     => (int) $d['qty'],
                'picker'  => isset($picker[$id]) ? $picker[$id] : null,
                'tandai'  => $this->orang($r['td_absen'], $r['td_nama'], $r['td_role']) + ['waktu' => $r['ditandai_at']],
                'gb'      => $this->cek_dari_join($r, 'gb'),
                'pk'      => $this->cek_dari_join($r, 'pk'),
            ];
            $b['progres'] = $this->progres($b);
            $semua[] = $b;
        }
        usort($semua, function ($a, $b) {
            $wa = $a['picker'] ? $a['picker']['waktu'] : $a['tandai']['waktu'];
            $wb = $b['picker'] ? $b['picker']['waktu'] : $b['tandai']['waktu'];
            return strcmp($wb, $wa);
        });

        // KPI: periode saja (tidak terpengaruh filter).
        $kpi = ['total' => count($semua), 'belum' => 0, 'tunggu' => 0, 'selisih' => 0, 'dicek' => 0];
        foreach ($semua as $b) {
            if (!$b['gb']) $kpi['belum']++;
            elseif (!$b['pk']) $kpi['tunggu']++;
            if ($b['gb']) $kpi['dicek']++;
            foreach (['gb', 'pk'] as $k) {
                if ($b[$k] && !$b[$k]['sesuai']) { $kpi['selisih']++; break; }
            }
        }

        // Daftar pilihan filter diambil dari data periode ini.
        $opsi = ['picker' => [], 'marketplace' => [], 'toko' => []];
        foreach ($semua as $b) {
            if ($b['picker']) $opsi['picker'][$b['picker']['label']] = true;
            if ($b['mp'] !== '') $opsi['marketplace'][$b['mp']] = true;
            if ($b['toko'] !== '') $opsi['toko'][$b['toko']] = true;
        }
        foreach ($opsi as $k => $v) {
            $opsi[$k] = array_keys($v);
            sort($opsi[$k], SORT_NATURAL | SORT_FLAG_CASE);
        }

        $cari = mb_strtolower(trim((string) $cari), 'UTF-8');
        $hasil = [];
        foreach ($semua as $b) {
            if ($f['progres'] && !in_array($b['progres'], $f['progres'], true)) continue;
            if ($f['hasil'] && !array_intersect($this->himpunan_hasil($b), $f['hasil'])) continue;
            if ($f['picker'] && !($b['picker'] && in_array($b['picker']['label'], $f['picker'], true))) continue;
            if ($f['marketplace'] && !in_array($b['mp'], $f['marketplace'], true)) continue;
            if ($f['toko'] && !in_array($b['toko'], $f['toko'], true)) continue;
            if ($cari !== '') {
                $jerami = mb_strtolower(implode(' ', [
                    $b['noresi'], $b['pesanan'], $b['toko'], $b['mp'],
                    $b['picker'] ? $b['picker']['label'] : '', $b['tandai']['label'],
                    $b['gb'] ? $b['gb']['label'] : '', $b['pk'] ? $b['pk']['label'] : '',
                ]), 'UTF-8');
                if (mb_strpos($jerami, $cari) === false) continue;
            }
            $hasil[] = $b;
        }

        $total   = count($hasil);
        $semua_baris = ((int) $per) <= 0;   // export: seluruh hasil filter, tanpa halaman
        $per     = $semua_baris ? max(1, $total) : max(1, min(100, (int) $per));
        $halaman = $semua_baris ? 1 : max(1, (int) $halaman);
        return [
            'kpi'     => $kpi,
            'opsi'    => $opsi,
            'total'   => $total,
            'halaman' => $halaman,
            'per'     => $per,
            'rows'    => array_slice($hasil, ($halaman - 1) * $per, $per),
            'terpotong' => count($rows) >= self::BATAS_REKAP,
        ];
    }

    /** Detail satu resi untuk drawer Rekap: alur dan perbandingan qty resi / gedung baru / packer. */
    public function detail($noresi)
    {
        $g = $this->db->get_where('tblgdbr_resi', ['noresi' => trim((string) $noresi)])->row();
        if (!$g) {
            return null;
        }
        $resi = $this->db->get_where('tblprintresi', ['id_printresi' => $g->id_resi])->row();
        if (!$resi) {
            return null;
        }
        $gb = $this->cek_row($g->id_gdbr, self::TAHAP_GB);
        $pk = $this->cek_row($g->id_gdbr, self::TAHAP_PACKER);

        $peta = ['gb' => null, 'pk' => null];
        foreach (['gb' => $gb, 'pk' => $pk] as $k => $c) {
            if ($c) {
                $peta[$k] = [];
                foreach ($this->cek_items($c->id_cek) as $it) {
                    $kode = strtoupper($it['sku']);
                    $peta[$k][$kode] = (isset($peta[$k][$kode]) ? $peta[$k][$kode] : 0) + (int) $it['qty_ada'];
                }
            }
        }

        $isi = $this->isi_resi($g->id_resi);
        $baris = [];
        $dalam = [];
        foreach ($isi as $r) {
            $kode = strtoupper($r['sku']);
            $dalam[$kode] = true;
            $baris[] = ['sku' => $r['sku'], 'no_rak' => $r['no_rak'], 'foto' => $r['foto'], 'foto_lokal' => $r['foto_lokal'], 'resi' => $r['qty'], 'luar' => 0,
                'gb' => $peta['gb'] === null ? null : (isset($peta['gb'][$kode]) ? $peta['gb'][$kode] : 0),
                'pk' => $peta['pk'] === null ? null : (isset($peta['pk'][$kode]) ? $peta['pk'][$kode] : 0)];
        }
        $tambahan = [];
        foreach (['gb', 'pk'] as $k) {
            if ($peta[$k]) {
                foreach (array_keys($peta[$k]) as $kode) {
                    if (!isset($dalam[$kode]) && !in_array($kode, $tambahan, true)) {
                        $tambahan[] = $kode;
                    }
                }
            }
        }
        $info = $this->info_sku($tambahan);
        foreach ($tambahan as $kode) {
            $s = isset($info[$kode]) ? $this->tambah_foto($info[$kode] + ['link_foto' => '', 'foto_lokal' => '']) : ['foto' => '', 'foto_lokal' => '', 'no_rak' => ''];
            $baris[] = ['sku' => $kode, 'no_rak' => isset($s['no_rak']) ? $s['no_rak'] : '', 'foto' => $s['foto'], 'foto_lokal' => $s['foto_lokal'], 'resi' => 0, 'luar' => 1,
                'gb' => $peta['gb'] === null ? null : (isset($peta['gb'][$kode]) ? $peta['gb'][$kode] : 0),
                'pk' => $peta['pk'] === null ? null : (isset($peta['pk'][$kode]) ? $peta['pk'][$kode] : 0)];
        }

        $info_resi = $this->info_resi($resi);
        $c = function ($row) {
            return $row ? $this->ringkas_cek($row) : null;
        };
        $item = ['noresi' => $g->noresi, 'pesanan' => $info_resi['pesanan'], 'toko' => $info_resi['toko'], 'mp' => $info_resi['mp'],
            'picker' => $this->data_picker($g->id_resi),
            'tandai' => $this->orang($g->petugas_absen, $g->petugas_nama, $g->petugas_role) + ['waktu' => $g->ditandai_at],
            'gb' => $c($gb), 'pk' => $c($pk), 'baris' => $baris];
        $item['progres'] = $this->progres($item);
        return $item;
    }
}
