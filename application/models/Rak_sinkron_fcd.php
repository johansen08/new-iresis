<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rak_sinkron_fcd — sinkron nomor rak display (tblsku.no_rak) dari Excel "persediaan gudang" Jubelio,
 * dengan riwayat per batch supaya bisa dikembalikan.
 *
 * Alur:
 *  1. pratinjau($file)  : baca Excel, bandingkan dengan tblsku, simpan hasil ke cache. TIDAK mengubah data.
 *  2. terapkan($token)  : buat tabel cadangan, buka batch, UPDATE tblsku.no_rak + catat tblrak_history (batch_id).
 *  3. kembalikan($id)   : tulis balik rak_lama dari riwayat batch itu (hanya SKU yang rak-nya masih = rak_baru).
 *
 * Tidak ada DELETE/DROP/TRUNCATE. Rollback juga sebuah batch (jenis ROLLBACK) sehingga ikut tercatat.
 * Aturan: nilai rak kosong di Excel DIABAIKAN (tidak pernah mengosongkan rak di DB).
 */
class Rak_sinkron_fcd extends CI_Model
{
    const SUMBER_SINKRON = 'SINKRON_JUBELIO';
    const SUMBER_ROLLBACK = 'ROLLBACK';

    private function dir_cache()
    {
        $d = APPPATH . 'cache/sinkron_rak/';
        if (!is_dir($d)) {
            @mkdir($d, 0775, true);
        }
        return $d;
    }

    /** Kategori satu perubahan: CABUT / DB_KOSONG / BEDA. */
    private function kategori($rak_db, $rak_baru)
    {
        if (strcasecmp($rak_baru, 'CABUT') === 0) {
            return 'CABUT';
        }
        return $rak_db === '' ? 'DB_KOSONG' : 'BEDA';
    }

    /**
     * Baca Excel dan hitung selisih. Menyimpan daftar lengkap ke cache dan mengembalikan ringkasan + token.
     *
     * @throws Exception pesan Indonesia yang aman ditampilkan
     */
    public function pratinjau($file)
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(\PhpOffice\PhpSpreadsheet\IOFactory::identify($file));
        $reader->setReadDataOnly(true);
        $baris = $reader->load($file)->getActiveSheet()->toArray(null, true, false, false);
        if (empty($baris)) {
            throw new Exception('File excel kosong.');
        }

        $header = array_map(function ($h) {
            return strtolower(trim((string) $h));
        }, $baris[0]);
        $col_sku = $col_rak = -1;
        foreach ($header as $i => $h) {
            if ($col_sku < 0 && $h === 'sku') {
                $col_sku = $i;
            }
            if ($col_rak < 0 && $h === 'no rak') {
                $col_rak = $i;
            }
        }
        if ($col_sku < 0 || $col_rak < 0) {
            throw new Exception('Kolom "SKU" atau "No Rak" tidak ditemukan pada baris pertama file.');
        }

        // Satu SKU bisa muncul per lokasi; ambil nilai terisi pertama. Bila dua baris berisi nilai beda -> konflik, SKU dilewati.
        $excel = array();
        $konflik = array();
        for ($r = 1; $r < count($baris); $r++) {
            $sku = trim((string) $baris[$r][$col_sku]);
            $rak = trim((string) $baris[$r][$col_rak]);
            if ($sku === '' || $rak === '') {
                continue;
            }
            if (isset($excel[$sku]) && $excel[$sku] !== $rak) {
                $konflik[$sku] = true;
                continue;
            }
            $excel[$sku] = $rak;
        }

        $db = array();
        foreach ($this->db->select('id_sku, no_rak')->get('tblsku')->result_array() as $s) {
            $db[$s['id_sku']] = trim((string) $s['no_rak']);
        }

        $ubah = array();
        $hitung = array('BEDA' => 0, 'CABUT' => 0, 'DB_KOSONG' => 0);
        $sama = 0;
        $tidak_ada = 0;
        foreach ($excel as $sku => $rak) {
            if (isset($konflik[$sku])) {
                continue;
            }
            if (!array_key_exists($sku, $db)) {
                $tidak_ada++;
                continue;
            }
            if ($db[$sku] === $rak) {
                $sama++;
                continue;
            }
            $k = $this->kategori($db[$sku], $rak);
            $hitung[$k]++;
            $ubah[] = array('sku' => $sku, 'lama' => $db[$sku], 'baru' => $rak, 'kat' => $k);
        }

        $token = bin2hex(random_bytes(8));
        file_put_contents($this->dir_cache() . $token . '.json', json_encode(array(
            'dibuat' => time(),
            'ubah'   => $ubah,
        )), LOCK_EX);

        return array(
            'token'     => $token,
            'hitung'    => $hitung,
            'total'     => count($ubah),
            'sama'      => $sama,
            'tidak_ada' => $tidak_ada,
            'konflik'   => count($konflik),
            'contoh'    => array_slice($ubah, 0, 40),
        );
    }

    /**
     * Terapkan hasil pratinjau. $kategori = subset dari BEDA / CABUT / DB_KOSONG.
     * Mengembalikan {batch_id, diterapkan, dilewati, tabel_cadangan}.
     */
    public function terapkan($token, array $kategori, $id_pegawai, $nama_file)
    {
        if (!preg_match('/^[a-f0-9]{16}$/', (string) $token)) {
            throw new Exception('Token pratinjau tidak valid.');
        }
        $f = $this->dir_cache() . $token . '.json';
        if (!is_file($f)) {
            throw new Exception('Hasil pratinjau tidak ditemukan atau sudah kedaluwarsa. Unggah ulang file.');
        }
        $isi = json_decode(file_get_contents($f), true);
        if (!$isi || time() - (int) $isi['dibuat'] > 7200) {
            throw new Exception('Hasil pratinjau sudah lebih dari 2 jam. Unggah ulang file.');
        }
        $kategori = array_values(array_intersect($kategori, array('BEDA', 'CABUT', 'DB_KOSONG')));
        if (!$kategori) {
            throw new Exception('Pilih minimal satu kategori.');
        }
        $daftar = array_values(array_filter($isi['ubah'], function ($u) use ($kategori) {
            return in_array($u['kat'], $kategori, true);
        }));
        if (!$daftar) {
            throw new Exception('Tidak ada perubahan pada kategori yang dipilih.');
        }

        $now = date('Y-m-d H:i:s');

        // 1. Cadangan: salinan rak seluruh SKU sebelum update (sesuai aturan backup sebelum update massal).
        $cadangan = 'tblsku_rak_bak_' . date('Ymd_His');
        $this->db->query("CREATE TABLE `$cadangan` AS SELECT id_sku, no_rak, no_rak_gudang, NOW() AS dicadangkan FROM tblsku");

        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->trans_start();

        $this->db->insert('tblrak_batch', array(
            'jenis'          => 'SINKRON',
            'keterangan'     => 'Sinkron No Rak dari Jubelio: ' . $nama_file . ' [' . implode(',', $kategori) . ']',
            'tabel_cadangan' => $cadangan,
            'id_pegawai'     => $id_pegawai,
            'created_at'     => $now,
        ));
        $batch_id = (int) $this->db->insert_id();

        $ok = 0;
        $lewati = 0;
        foreach ($daftar as $u) {
            // Syarat rak_lama masih sama: kalau sudah diubah orang lain sejak pratinjau, jangan timpa.
            $this->db->query(
                'UPDATE tblsku SET no_rak = ? WHERE id_sku = ? AND TRIM(IFNULL(no_rak, \'\')) = ?',
                array($u['baru'], $u['sku'], $u['lama'])
            );
            if ($this->db->affected_rows() < 1) {
                $lewati++;
                continue;
            }
            $this->catat($u['sku'], $u['lama'], $u['baru'], self::SUMBER_SINKRON, $id_pegawai, $batch_id, $now);
            $ok++;
        }

        $this->db->where('id', $batch_id)->update('tblrak_batch', array('jumlah_sku' => $ok, 'jumlah_lewati' => $lewati));
        $this->db->trans_complete();
        $sukses = $this->db->trans_status();
        $this->db->db_debug = $db_debug;
        if (!$sukses) {
            throw new Exception('Penerapan gagal dan dibatalkan (transaksi di-rollback). Data tidak berubah.');
        }

        @unlink($f);
        return array('batch_id' => $batch_id, 'diterapkan' => $ok, 'dilewati' => $lewati, 'tabel_cadangan' => $cadangan);
    }

    /**
     * Kembalikan satu batch ke rak_lama. Hanya SKU yang no_rak-nya masih = rak_baru batch itu.
     */
    public function kembalikan($batch_id, $id_pegawai)
    {
        $batch = $this->db->get_where('tblrak_batch', array('id' => (int) $batch_id))->row_array();
        if (!$batch) {
            throw new Exception('Batch tidak ditemukan.');
        }
        if ($batch['status'] !== 'AKTIF') {
            throw new Exception('Batch ini sudah dikembalikan.');
        }

        $baris = $this->db->get_where('tblrak_history', array('batch_id' => (int) $batch_id))->result_array();
        if (!$baris) {
            throw new Exception('Batch tidak punya riwayat perubahan.');
        }

        $now = date('Y-m-d H:i:s');
        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->trans_start();

        $this->db->insert('tblrak_batch', array(
            'jenis'        => 'ROLLBACK',
            'keterangan'   => 'Kembalikan batch #' . (int) $batch_id,
            'ref_batch_id' => (int) $batch_id,
            'id_pegawai'   => $id_pegawai,
            'created_at'   => $now,
        ));
        $rb = (int) $this->db->insert_id();

        $ok = 0;
        $lewati = 0;
        $daftar_lewati = array();
        foreach ($baris as $h) {
            $lama = (string) $h['rak_lama'];
            $baru = (string) $h['rak_baru'];
            $this->db->query(
                'UPDATE tblsku SET no_rak = ? WHERE id_sku = ? AND TRIM(IFNULL(no_rak, \'\')) = ?',
                array($lama === '' ? null : $lama, $h['id_sku'], $baru)
            );
            if ($this->db->affected_rows() < 1) {
                $lewati++;
                $daftar_lewati[] = $h['id_sku'];
                continue;
            }
            $this->catat($h['id_sku'], $baru, $lama, self::SUMBER_ROLLBACK, $id_pegawai, $rb, $now);
            $ok++;
        }

        $this->db->where('id', $rb)->update('tblrak_batch', array('jumlah_sku' => $ok, 'jumlah_lewati' => $lewati));
        $this->db->where('id', (int) $batch_id)->update('tblrak_batch', array('status' => 'DIKEMBALIKAN'));
        $this->db->trans_complete();
        $sukses = $this->db->trans_status();
        $this->db->db_debug = $db_debug;
        if (!$sukses) {
            throw new Exception('Pengembalian gagal dan dibatalkan (transaksi di-rollback). Data tidak berubah.');
        }

        return array('batch_id' => $rb, 'dikembalikan' => $ok, 'dilewati' => $lewati, 'contoh_lewati' => array_slice($daftar_lewati, 0, 20));
    }

    public function daftar_batch($limit = 50)
    {
        return $this->db->select('b.*, u.name AS nama_pegawai')
            ->from('tblrak_batch b')
            ->join('tbluser u', 'u.id_user = b.id_pegawai', 'left')
            ->order_by('b.id', 'DESC')
            ->limit((int) $limit)
            ->get()->result_array();
    }

    private function catat($sku, $lama, $baru, $sumber, $id_pegawai, $batch_id, $now)
    {
        $this->db->insert('tblrak_history', array(
            'id_sku'     => $sku,
            'jenis'      => 'DISPLAY',
            'rak_lama'   => ($lama === '' ? null : $lama),
            'rak_baru'   => ($baru === '' ? null : $baru),
            'sumber'     => $sumber,
            'id_pegawai' => $id_pegawai,
            'created_at' => $now,
            'batch_id'   => $batch_id,
        ));
    }
}
