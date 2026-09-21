<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Arsip_fcd — menyalin data prod ke database arsip (superset) tiap malam.
 *
 * Dipanggil Cron::arsip_harian. Semua pekerjaan berat dijalankan di server
 * MariaDB lewat `REPLACE INTO arsip.t SELECT * FROM prod.t` — PHP hanya
 * mengatur batch, watermark, dan pencatatan. Tidak ada DELETE di sini:
 * tahap purna (hapus keluarga resi > retensi dari prod) dibuat terpisah
 * setelah sinkron terbukti beberapa malam. Lihat docs/ARSIP_DATA.md.
 */
class Arsip_fcd extends CI_Model
{
    /** @var array setelan dari application/config/arsip.php */
    private $cfg;

    /** @var string nama DB prod (dari koneksi aktif) dan arsip */
    private $prod;
    private $arsip;

    /** @var float waktu mulai putaran (microtime) */
    private $mulai;

    /** @var int watermark PK setelah salin_bertahap terakhir (dibaca sinkron()) */
    private $watermark_sekarang = 0;

    /** @var array ringkasan putaran: ['tabel' => ['mode','baris','detik']] */
    private $ringkas = array();

    /** @var array pesan log berurutan (dicetak Cron ke stdout/berkas log) */
    private $log = array();

    public function __construct()
    {
        parent::__construct();
        $this->config->load('arsip', TRUE);
        $this->cfg   = $this->config->item('arsip');
        // Nama prod dari secrets, bukan dari koneksi: di Mode Arsip koneksi sudah
        // di-db_select ke arsip, dan tarik_balik() tidak boleh salah arah.
        $this->prod  = function_exists('iresis_secret') ? iresis_secret('db_database', $this->db->database) : $this->db->database;
        $this->arsip = $this->cfg['db_arsip'];
    }

    // ------------------------------------------------------------------
    //  Tarik balik: keluarga resi lama dari arsip ke prod (dipanggil helper
    //  pastikan_resi_live() di pintu masuk retur / CS / cek resi)
    // ------------------------------------------------------------------

    /**
     * Kalau $noresi tidak ada di prod tetapi ada di arsip, salin seluruh
     * keluarganya (config `keluarga_resi`) ke prod dengan INSERT IGNORE dalam
     * satu transaksi, lalu alur pemanggil berjalan seperti biasa. Putaran malam
     * berikutnya menyalin perubahan barunya kembali ke arsip; tahap purna nanti
     * mengarsipkannya lagi setelah 60 hari sejak aktivitas terakhir.
     *
     * Sengaja tidak pernah melempar: pemanggil adalah alur transaksi live.
     * Kembalian: 'live' (sudah ada di prod), 'ditarik', 'tidak_ada',
     * 'lewati' (Mode Arsip / noresi kosong), 'gagal' (lihat _arsip_log).
     */
    public function tarik_balik($noresi)
    {
        $noresi = trim((string) $noresi);
        if ($noresi === '' || $this->arsip === '' || $this->arsip === $this->prod
            || $this->session->userdata('mode_arsip')) {
            return 'lewati';
        }

        $debug_lama = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        try {
            $ada = $this->ambil_row("SELECT 1 n FROM `{$this->prod}`.`tblprintresi` WHERE noresi = ? LIMIT 1", array($noresi));
            if ($ada) {
                return 'live';
            }
            $r = $this->ambil_row("SELECT id_printresi FROM `{$this->arsip}`.`tblprintresi` WHERE noresi = ? LIMIT 1", array($noresi));
            if (!$r) {
                return 'tidak_ada';
            }
            $id = (int) $r->id_printresi;

            // sql_mode kosong sementara supaya tanggal 0000-00-00 dari arsip ikut masuk.
            $mode_lama = $this->ambil_row("SELECT @@SESSION.sql_mode m");
            $this->q("SET SESSION sql_mode = ''");

            $this->db->trans_begin();
            $disalin = array();
            foreach ($this->cfg['keluarga_resi'] as $t => $aturan) {
                $pilih = $this->pilih_sql($aturan, array($id), array($noresi), $this->arsip);
                $ok = $this->kueri("INSERT IGNORE INTO `{$this->prod}`.`$t` SELECT * FROM `{$this->arsip}`.`$t`
                    WHERE $pilih", array(), FALSE);
                if (!$ok) {
                    $this->db->trans_rollback();
                    $this->catat('tarik_balik', $t, 'FAIL', "$noresi: " . $this->pesan_error());
                    return 'gagal';
                }
                $n = $this->db->affected_rows();
                if ($n > 0) {
                    $disalin[] = "$t=$n";
                }
            }
            $this->db->trans_commit();
            $this->catat('tarik_balik', 'tblprintresi', 'OK', "$noresi (id $id): " . implode(', ', $disalin));
            return 'ditarik';
        } catch (Throwable $e) {
            if ($this->db->trans_status() !== FALSE) {
                $this->db->trans_rollback();
            }
            $this->catat('tarik_balik', NULL, 'FAIL', "$noresi: " . $e->getMessage());
            return 'gagal';
        } finally {
            if (!empty($mode_lama) && isset($mode_lama->m)) {
                $this->q("SET SESSION sql_mode = ?", array($mode_lama->m));
            }
            $this->db->db_debug = $debug_lama;
        }
    }

    // ------------------------------------------------------------------
    //  Orkestrasi
    // ------------------------------------------------------------------

    /**
     * Jalankan satu putaran. $tahap: 'semua' | 'cek_skema' | 'sinkron' | 'laporan'.
     * Mengembalikan array ringkasan; baris log ada di ['log'].
     */
    public function jalankan($tahap = 'semua', $maks_detik = NULL)
    {
        $this->mulai = microtime(TRUE);
        $maks_detik  = (int) ($maks_detik ?: $this->cfg['maks_detik']);
        $hasil       = array('success' => FALSE, 'tahap' => $tahap, 'prod' => $this->prod, 'arsip' => $this->arsip);

        if ($this->arsip === '' || $this->prod === $this->arsip) {
            $hasil['pesan'] = "Nama DB arsip '{$this->arsip}' kosong atau sama dengan prod";
            return $this->selesai($hasil);
        }

        $kunci = $this->ambil_kunci();
        if ($kunci !== TRUE) {
            $hasil['pesan'] = $kunci;
            return $this->selesai($hasil);
        }

        // Error DB ditangani manual (cek hasil query) supaya tidak mencetak halaman
        // HTML CI di tengah log. sql_mode dikosongkan agar tanggal nol dan nilai lama
        // tersalin apa adanya. READ COMMITTED: SELECT sumber jadi consistent read,
        // tidak memasang shared lock pada baris prod selama batch berjalan.
        $debug_lama = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->q("SET SESSION sql_mode = ''");
        $this->q("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
        $this->q("SET SESSION wait_timeout = 28800");

        try {
            $this->siapkan_db();

            $skema = $this->cek_skema();
            $hasil['skema'] = array(
                'siap' => count($skema['siap']), 'dibuat' => $skema['dibuat'], 'view' => $skema['view'],
                'beda' => $skema['beda'], 'dilewati' => $skema['dilewati'],
            );

            if ($tahap === 'semua' || $tahap === 'sinkron') {
                $hasil['sinkron'] = $this->sinkron($skema['siap'], $maks_detik);
            }
            if ($tahap === 'semua' || $tahap === 'laporan') {
                $hasil['purna_perkiraan'] = $this->laporan_purna();
            }

            // Purna ikut putaran malam hanya bila dinyalakan di config, sinkron
            // bersih, dan masih ada sisa batas waktu (min. 10 menit).
            if ($tahap === 'semua' && !empty($this->cfg['purna_aktif']) && empty($skema['beda'])
                && empty($hasil['sinkron']['gagal']) && empty($hasil['sinkron']['terpotong'])) {
                $sisa = $maks_detik - (int) (microtime(TRUE) - $this->mulai);
                if ($sisa >= 600) {
                    $hasil['purna'] = $this->purna($sisa, 0, FALSE, FALSE);
                } else {
                    $this->log[] = date('H:i:s') . "  purna dilewati: sisa waktu $sisa dtk < 600";
                }
            }

            $hasil['success'] = empty($skema['beda']) && empty($hasil['sinkron']['gagal'])
                && (!isset($hasil['purna']) || $hasil['purna']['success']);
        } catch (Throwable $e) {
            $hasil['pesan'] = $e->getMessage();
            $this->log[] = date("H:i:s") . "  " . 'PUTARAN GAGAL: ' . $e->getMessage();
            $this->catat('putaran', NULL, 'FAIL', $e->getMessage());
        }

        $this->db->db_debug = $debug_lama;
        $this->lepas_kunci();
        return $this->selesai($hasil);
    }

    private function selesai(array $hasil)
    {
        $hasil['durasi_detik'] = round(microtime(TRUE) - $this->mulai, 1);
        $hasil['log']          = $this->log;
        $hasil['ringkas']      = $this->ringkas;
        return $hasil;
    }

    // ------------------------------------------------------------------
    //  Persiapan: DB arsip + tabel status/log
    // ------------------------------------------------------------------

    private function siapkan_db()
    {
        $cs = $this->ambil_row("SELECT default_character_set_name cs, default_collation_name col
            FROM information_schema.schemata WHERE schema_name = ?", array($this->prod));
        $cs_sql = $cs ? "CHARACTER SET {$cs->cs} COLLATE {$cs->col}" : '';
        $this->kueri("CREATE DATABASE IF NOT EXISTS `{$this->arsip}` $cs_sql");

        $this->kueri("CREATE TABLE IF NOT EXISTS `{$this->arsip}`.`_arsip_status` (
            `tabel`          VARCHAR(64) NOT NULL PRIMARY KEY,
            `kolom_pk`       VARCHAR(64) NULL,
            `mode`           ENUM('penuh','bertahap','lewati') NOT NULL DEFAULT 'lewati',
            `pk_terakhir`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
            `baris_terakhir` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'baris disalin pada putaran terakhir',
            `baris_arsip`    BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'COUNT(*) di arsip setelah putaran',
            `terakhir_jalan` DATETIME NULL,
            `durasi_detik`   DECIMAL(9,2) NOT NULL DEFAULT 0,
            `keterangan`     VARCHAR(255) NULL
        ) ENGINE=InnoDB COMMENT='Watermark sinkron per tabel — dipakai cron/arsip_harian'");

        $this->kueri("CREATE TABLE IF NOT EXISTS `{$this->arsip}`.`_arsip_log` (
            `id`     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `waktu`  DATETIME NOT NULL,
            `tahap`  VARCHAR(32) NOT NULL,
            `tabel`  VARCHAR(64) NULL,
            `status` VARCHAR(8) NOT NULL,
            `pesan`  TEXT NULL,
            KEY `idx_waktu` (`waktu`)
        ) ENGINE=InnoDB COMMENT='Jejak putaran cron/arsip_harian'");
    }

    // ------------------------------------------------------------------
    //  Cek skema: buat tabel/view yang belum ada, tandai yang kolomnya beda
    // ------------------------------------------------------------------

    /**
     * Membandingkan setiap tabel prod dengan arsip.
     *  - belum ada di arsip → CREATE TABLE ... LIKE; view dibuat ulang tiap putaran
     *    (definisinya menyebut nama DB prod, jadi diganti ke arsip)
     *  - kolom beda        → dicatat di 'beda', tabel DILEWATI saat sinkron (tidak ditebak)
     * Mengembalikan ['siap' => [tabel => info], 'dibuat' => [], 'beda' => [], 'view' => [], 'dilewati' => []].
     */
    public function cek_skema()
    {
        $out = array('siap' => array(), 'dibuat' => array(), 'beda' => array(), 'view' => array(), 'dilewati' => array());

        $rows = $this->ambil_semua("SELECT table_name, table_type FROM information_schema.tables
            WHERE table_schema = ? ORDER BY table_type, table_name", array($this->prod));

        $views = array();
        foreach ($rows as $r) {
            $t = $r->table_name;
            if ($this->dikecualikan($t)) {
                $out['dilewati'][] = $t;
                continue;
            }
            if ($r->table_type === 'VIEW') {
                $views[] = $t;
                continue;
            }

            $ada = $this->ambil_row("SELECT COUNT(*) n FROM information_schema.tables
                WHERE table_schema = ? AND table_name = ?", array($this->arsip, $t));
            if (!$ada || !$ada->n) {
                $this->kueri("CREATE TABLE `{$this->arsip}`.`$t` LIKE `{$this->prod}`.`$t`");
                $out['dibuat'][] = $t;
                $this->catat('cek_skema', $t, 'OK', 'tabel dibuat (LIKE prod)');
            }

            $selisih = $this->bandingkan_kolom($t);
            if ($selisih !== '') {
                $out['beda'][$t] = $selisih;
                $this->catat('cek_skema', $t, 'FAIL', 'kolom beda: ' . $selisih);
                continue;
            }

            $out['siap'][$t] = $this->info_tabel($t);
        }

        // View dibuat setelah semua tabel ada; yang gagal (mungkin merujuk view lain)
        // dicoba sekali lagi setelah putaran pertama.
        $gagal = array();
        foreach ($views as $v) {
            $err = $this->buat_view($v);
            if ($err === '') {
                $out['view'][] = $v;
            } else {
                $gagal[$v] = $err;
            }
        }
        foreach ($gagal as $v => $err) {
            $err = $this->buat_view($v);
            if ($err === '') {
                $out['view'][] = $v;
            } else {
                $out['beda'][$v] = 'view gagal dibuat: ' . $err;
                $this->catat('cek_skema', $v, 'FAIL', 'view gagal dibuat: ' . $err);
            }
        }

        $this->log[] = date("H:i:s") . "  " . sprintf('cek_skema: %d tabel siap, %d dibuat, %d view, %d beda, %d dilewati',
            count($out['siap']), count($out['dibuat']), count($out['view']), count($out['beda']), count($out['dilewati']));
        return $out;
    }

    /** Buat/ganti view $v di arsip dari definisi prod. '' kalau berhasil, selain itu pesan error. */
    private function buat_view($v)
    {
        $def = $this->q("SHOW CREATE VIEW `{$this->prod}`.`$v`");
        $def = is_object($def) ? $def->row_array() : array();
        $sql = isset($def['Create View']) ? $def['Create View'] : '';
        if ($sql === '') {
            return 'SHOW CREATE VIEW kosong';
        }
        $sql = str_replace("`{$this->prod}`.", "`{$this->arsip}`.", $sql);
        $sql = preg_replace('/^CREATE\s+/i', 'CREATE OR REPLACE ', $sql, 1);
        // DEFINER=root@localhost butuh hak SUPER; tanpa klausa itu definer = user aplikasi.
        $sql = preg_replace('/\sDEFINER=`[^`]*`@`[^`]*`/', '', $sql, 1);
        return $this->kueri($sql, array(), FALSE) ? '' : $this->pesan_error();
    }

    /** '' kalau nama+tipe kolom urut sama; kalau beda, teks penjelasannya. */
    private function bandingkan_kolom($t)
    {
        $ambil = function ($db) use ($t) {
            $r = $this->ambil_semua("SELECT column_name, column_type FROM information_schema.columns
                WHERE table_schema = ? AND table_name = ? ORDER BY ordinal_position", array($db, $t));
            $k = array();
            foreach ($r as $c) {
                $k[] = $c->column_name . ' ' . $c->column_type;
            }
            return $k;
        };
        $p = $ambil($this->prod);
        $a = $ambil($this->arsip);
        if ($p === $a) {
            return '';
        }
        $hanya_prod  = array_diff($p, $a);
        $hanya_arsip = array_diff($a, $p);
        $ket = array();
        if ($hanya_prod) {
            $ket[] = 'hanya di prod: ' . implode(', ', $hanya_prod);
        }
        if ($hanya_arsip) {
            $ket[] = 'hanya di arsip: ' . implode(', ', $hanya_arsip);
        }
        if (!$ket) {
            $ket[] = 'urutan kolom beda';
        }
        return implode('; ', $ket);
    }

    /** Info yang dibutuhkan sinkron: PK, apakah integer, kolom ubah, perkiraan baris. */
    private function info_tabel($t)
    {
        $pk = $this->ambil_semua("SELECT column_name, data_type FROM information_schema.columns
            WHERE table_schema = ? AND table_name = ? AND column_key = 'PRI'", array($this->prod, $t));

        $info = array('pk' => NULL, 'pk_int' => FALSE, 'kolom_ubah' => NULL, 'baris' => 0);
        if (count($pk) === 1) {
            $info['pk']     = $pk[0]->column_name;
            $info['pk_int'] = in_array($pk[0]->data_type, array('tinyint', 'smallint', 'mediumint', 'int', 'bigint'));
        }

        $kolom = $this->ambil_semua("SELECT column_name FROM information_schema.columns
            WHERE table_schema = ? AND table_name = ? AND data_type IN ('datetime','timestamp')", array($this->prod, $t));
        $ada = array();
        foreach ($kolom as $c) {
            $ada[] = $c->column_name;
        }
        foreach ($this->cfg['kolom_ubah'] as $nama) {
            if (in_array($nama, $ada)) {
                $info['kolom_ubah'] = $nama;
                break;
            }
        }

        // table_rows di information_schema hanya perkiraan; cukup untuk memilih mode.
        $r = $this->ambil_row("SELECT table_rows FROM information_schema.tables
            WHERE table_schema = ? AND table_name = ?", array($this->prod, $t));
        $info['baris'] = $r ? (int) $r->table_rows : 0;

        return $info;
    }

    private function dikecualikan($t)
    {
        if (in_array($t, $this->cfg['kecuali_tabel'])) {
            return TRUE;
        }
        foreach ($this->cfg['kecuali_pola'] as $pola) {
            if ($pola !== '' && strpos($t, $pola) !== FALSE) {
                return TRUE;
            }
        }
        return FALSE;
    }

    // ------------------------------------------------------------------
    //  Sinkron: salin baris baru/berubah dari prod ke arsip
    // ------------------------------------------------------------------

    /**
     * $siap: hasil cek_skema()['siap']. Tabel kecil disalin penuh; tabel besar
     * bertahap per batch PK, lalu baris yang berubah N hari terakhir.
     * Berhenti rapi kalau $maks_detik terlampaui; putaran berikutnya melanjutkan.
     */
    public function sinkron(array $siap, $maks_detik)
    {
        $out = array('selesai' => array(), 'gagal' => array(), 'lewati' => array(), 'terpotong' => FALSE);

        // Kecil dulu (master: user, menu, sku, ...) supaya Mode Arsip selalu punya
        // tabel pendukung walau putaran terpotong di tabel besar.
        uasort($siap, function ($a, $b) {
            return $a['baris'] - $b['baris'];
        });

        foreach ($siap as $t => $info) {
            if ($this->habis_waktu($maks_detik)) {
                $out['terpotong'] = TRUE;
                $this->log[] = date("H:i:s") . "  " . "sinkron: batas waktu $maks_detik dtk tercapai, sisa tabel dilanjutkan putaran berikutnya";
                break;
            }

            if ($info['pk'] === NULL) {
                $out['lewati'][$t] = 'tanpa PK tunggal';
                $this->simpan_status($t, NULL, 'lewati', 0, 0, 0, 0, 'tanpa PK tunggal — tidak bisa REPLACE');
                continue;
            }

            $mode = ($info['baris'] <= $this->cfg['batas_penuh'] || !$info['pk_int']) ? 'penuh' : 'bertahap';
            $t0   = microtime(TRUE);

            try {
                $baris = ($mode === 'penuh')
                    ? $this->salin_penuh($t)
                    : $this->salin_bertahap($t, $info, $maks_detik);

                $total = $this->hitung("SELECT COUNT(*) n FROM `{$this->arsip}`.`$t`");
                $detik = round(microtime(TRUE) - $t0, 2);
                $wm    = ($mode === 'bertahap') ? $this->watermark_sekarang : 0;

                $this->simpan_status($t, $info['pk'], $mode, $wm, $baris, $total, $detik);
                $out['selesai'][$t] = array('mode' => $mode, 'baris' => $baris, 'detik' => $detik);
                $this->ringkas[$t]  = $out['selesai'][$t];
                $this->log[] = date("H:i:s") . "  " . sprintf('%-32s %-8s %10s baris %8.2f dtk  (arsip: %s)',
                    $t, $mode, number_format($baris), $detik, number_format($total));
            } catch (Throwable $e) {
                $out['gagal'][$t] = $e->getMessage();
                $this->catat('sinkron', $t, 'FAIL', $e->getMessage());
                $this->log[] = date("H:i:s") . "  " . "$t GAGAL: " . $e->getMessage();
            }
        }

        $this->catat('sinkron', NULL, empty($out['gagal']) ? 'OK' : 'FAIL', sprintf(
            '%d tabel selesai, %d gagal, %d dilewati%s',
            count($out['selesai']), count($out['gagal']), count($out['lewati']),
            $out['terpotong'] ? ', TERPOTONG batas waktu' : ''
        ));
        return $out;
    }

    /** Salin seluruh isi tabel (master/kecil). Mengembalikan jumlah baris prod. */
    private function salin_penuh($t)
    {
        $this->kueri("REPLACE INTO `{$this->arsip}`.`$t` SELECT * FROM `{$this->prod}`.`$t`");
        return $this->hitung("SELECT COUNT(*) n FROM `{$this->prod}`.`$t`");
    }

    /**
     * Salin baris pk > watermark per batch, lalu baris yang kolom-ubahnya
     * masih dalam jendela N hari (UPDATE pada baris lama).
     */
    private function salin_bertahap($t, array $info, $maks_detik)
    {
        $pk    = $info['pk'];
        $batch = (int) $this->cfg['batch'];
        $jeda  = (int) $this->cfg['jeda_batch_ms'] * 1000;

        $st      = $this->ambil_row("SELECT pk_terakhir FROM `{$this->arsip}`.`_arsip_status` WHERE tabel = ?", array($t));
        $wm_awal = $st ? (int) $st->pk_terakhir : 0;
        $wm      = $wm_awal;
        $disalin = 0;

        while (TRUE) {
            $r = $this->ambil_row("SELECT MAX(`$pk`) m FROM (SELECT `$pk` FROM `{$this->prod}`.`$t`
                WHERE `$pk` > ? ORDER BY `$pk` LIMIT $batch) x", array($wm));
            if (!$r || $r->m === NULL) {
                break;
            }
            $maks = (int) $r->m;
            $this->kueri("REPLACE INTO `{$this->arsip}`.`$t` SELECT * FROM `{$this->prod}`.`$t`
                WHERE `$pk` > ? AND `$pk` <= ?", array($wm, $maks));
            $disalin += $this->hitung("SELECT COUNT(*) n FROM `{$this->prod}`.`$t`
                WHERE `$pk` > ? AND `$pk` <= ?", array($wm, $maks));
            $wm = $maks;

            // Simpan watermark tiap batch supaya putaran yang terpotong tidak mengulang.
            $this->simpan_status($t, $pk, 'bertahap', $wm, $disalin, 0, 0, 'sedang berjalan');

            if ($this->habis_waktu($maks_detik)) {
                $this->log[] = date("H:i:s") . "  " . "$t: terpotong di $pk=$wm, dilanjutkan putaran berikutnya";
                break;
            }
            if ($jeda > 0) {
                usleep($jeda);
            }
        }

        // Baris lama yang diubah dalam jendela: hanya kalau tabel punya kolom-ubah
        // dan ini bukan muat awal (saat muat awal semua baris toh baru disalin).
        if ($info['kolom_ubah'] !== NULL && $wm_awal > 0) {
            $hari = (int) $this->cfg['jendela_ubah_hari'];
            $this->kueri("REPLACE INTO `{$this->arsip}`.`$t` SELECT * FROM `{$this->prod}`.`$t`
                WHERE `{$info['kolom_ubah']}` >= NOW() - INTERVAL $hari DAY AND `$pk` <= ?", array($wm_awal));
            $disalin += $this->hitung("SELECT COUNT(*) n FROM `{$this->prod}`.`$t`
                WHERE `{$info['kolom_ubah']}` >= NOW() - INTERVAL $hari DAY AND `$pk` <= ?", array($wm_awal));
        }

        $this->watermark_sekarang = $wm;
        return $disalin;
    }

    // ------------------------------------------------------------------
    //  Laporan purna (perkiraan saja — belum ada penghapusan)
    // ------------------------------------------------------------------

    /**
     * Berapa resi yang aktivitas terakhirnya sudah lewat retensi. Angka ini
     * perkiraan kasar untuk memantau; aturan "status terbuka" (retur belum
     * tuntas, komplain, lost scan) baru diterapkan di tahap purna.
     */
    public function laporan_purna()
    {
        $hari = (int) $this->cfg['retensi_hari'];
        $r = $this->ambil_row("SELECT COUNT(*) jml, MIN(akhir) tertua, MAX(akhir) termuda FROM (
                SELECT GREATEST(
                    COALESCE(NULLIF(tanggal_printresi, '0000-00-00 00:00:00'), '1970-01-01'),
                    COALESCE(created_at,      '1970-01-01'),
                    COALESCE(modified_at,     '1970-01-01'),
                    COALESCE(tanggal_selesai, '1970-01-01'),
                    COALESCE(tanggal_retur,   '1970-01-01')
                ) akhir FROM `{$this->prod}`.`tblprintresi`
            ) x WHERE akhir < NOW() - INTERVAL $hari DAY");
        if (!$r) {
            $this->log[] = date("H:i:s") . "  " . 'laporan: gagal menghitung — ' . $this->pesan_error();
            return array('error' => $this->pesan_error());
        }

        $out = array('retensi_hari' => $hari, 'resi_lewat_retensi' => (int) $r->jml,
            'aktivitas_tertua' => $r->tertua, 'aktivitas_termuda_lewat' => $r->termuda);
        $this->log[] = date("H:i:s") . "  " . sprintf('laporan: %s resi sudah lewat retensi %d hari (aktivitas tertua %s) — belum dihapus, tahap purna belum aktif',
            number_format($out['resi_lewat_retensi']), $hari, $r->tertua ?: '-');
        return $out;
    }

    // ------------------------------------------------------------------
    //  Purna: pindahkan keluarga resi > retensi dari prod ke arsip (tahap 7)
    // ------------------------------------------------------------------

    /**
     * Klausa WHERE pemilih baris satu tabel keluarga untuk sekumpulan resi.
     *   pakai 'id'       → kolom IN (id...)
     *   pakai 'noresi'   → kolom IN ('noresi'...)
     *   pakai 'komplain' → kolom IN (SELECT id_complain FROM <sumber>.tblcs_complain WHERE no_resi IN (...))
     * $db_sumber = DB tempat baris dibaca (prod saat purna, arsip saat tarik balik).
     */
    private function pilih_sql(array $aturan, array $ids, array $noresis, $db_sumber, $alias = '')
    {
        $kolom = ($alias !== '' ? "`$alias`." : '') . "`{$aturan['kolom']}`";
        $ids   = array_map('intval', $ids);
        $esc   = array();
        foreach ($noresis as $n) {
            $esc[] = $this->db->escape($n);
        }
        $id_list = $ids ? implode(',', $ids) : 'NULL';
        $no_list = $esc ? implode(',', $esc) : "''";

        switch ($aturan['pakai']) {
            case 'id':
                return "$kolom IN ($id_list)";
            case 'noresi':
                return "$kolom IN ($no_list)";
            case 'komplain':
                return "$kolom IN (SELECT id_complain FROM `$db_sumber`.`tblcs_complain` WHERE no_resi IN ($no_list))";
        }
        throw new Exception("keluarga_resi: 'pakai' tidak dikenal untuk kolom {$aturan['kolom']}");
    }

    /** Nama kolom PK tunggal tabel prod, atau NULL. */
    private function pk_tabel($t)
    {
        $r = $this->ambil_semua("SELECT column_name FROM information_schema.columns
            WHERE table_schema = ? AND table_name = ? AND column_key = 'PRI'", array($this->prod, $t));
        return count($r) === 1 ? $r[0]->column_name : NULL;
    }

    /**
     * Gerbang keselamatan sebelum purna. Mengembalikan '' kalau boleh jalan,
     * selain itu alasan penolakan.
     */
    private function gerbang_purna()
    {
        $skema = $this->cek_skema();
        if (!empty($skema['beda'])) {
            return 'skema prod dan arsip beda: ' . implode(', ', array_keys($skema['beda']));
        }
        foreach ($this->cfg['keluarga_resi'] as $t => $a) {
            if (!isset($skema['siap'][$t])) {
                return "tabel keluarga '$t' tidak siap di arsip";
            }
        }

        $maks = (int) $this->cfg['purna_sinkron_maks_jam'];
        $r = $this->ambil_row("SELECT MAX(terakhir_jalan) t FROM `{$this->arsip}`.`_arsip_status` WHERE mode <> 'lewati'");
        if (!$r || !$r->t) {
            return 'arsip belum pernah disinkron';
        }
        $umur = (time() - strtotime($r->t)) / 3600;
        if ($umur > $maks) {
            return sprintf('sinkron terakhir %s (%.0f jam lalu) lebih tua dari %d jam', $r->t, $umur, $maks);
        }

        $pola  = $this->cfg['purna_backup_pola'];
        $files = $pola ? glob($pola) : array();
        if (!$files) {
            return "tidak ada backup arsip yang cocok dengan $pola";
        }
        $terbaru = 0;
        foreach ($files as $f) {
            $terbaru = max($terbaru, filemtime($f));
        }
        $umur = (time() - $terbaru) / 3600;
        if ($umur > (int) $this->cfg['purna_backup_maks_jam']) {
            return sprintf('backup arsip terbaru %s (%.0f jam lalu) lebih tua dari %d jam',
                date('Y-m-d H:i', $terbaru), $umur, $this->cfg['purna_backup_maks_jam']);
        }
        return '';
    }

    /**
     * Ambil satu batch resi calon purna: aktivitas terakhir di tblprintresi
     * < cutoff, dan TIDAK punya tanda "masih terbuka" di tabel anak.
     * Aturannya (lihat docs/ARSIP_DATA.md §4c):
     *   - packing / ambil barang / keluar / retur / verifikasi / cancel / komplain
     *     yang bertanggal >= cutoff → masih aktif, tunda;
     *   - retur masih "Terima Retur" (belum dibuka), buka retur status_acc = 0
     *     (belum di-ACC finance), komplain belum "Selesai" → tunda;
     *   - belum pernah scan keluar DAN status bukan CANCELED/COMPLETED/SHIPPED/
     *     RETURNED → tunda (201 resi per 21 Sep 2026); status PROCESSING yang
     *     sudah punya scan keluar dianggap basi dan boleh diarsip.
     */
    private function calon_purna($id_awal, $batas, $cutoff)
    {
        $p = "`{$this->prod}`";
        $sql = "SELECT p.id_printresi, p.noresi FROM $p.`tblprintresi` p
            WHERE p.id_printresi > ?
              AND GREATEST(
                    COALESCE(NULLIF(p.tanggal_printresi, '0000-00-00 00:00:00'), '1970-01-01'),
                    COALESCE(p.created_at, '1970-01-01'), COALESCE(p.modified_at, '1970-01-01'),
                    COALESCE(p.tanggal_selesai, '1970-01-01'), COALESCE(p.tanggal_retur, '1970-01-01'),
                    COALESCE(p.tanggal_pengiriman, '1970-01-01')) < ?
              AND (p.status_pesanan IN ('CANCELED','COMPLETED','SHIPPED','RETURNED')
                   OR EXISTS (SELECT 1 FROM $p.`tblresikeluar` k WHERE k.id_resi = p.id_printresi))
              AND NOT EXISTS (SELECT 1 FROM $p.`tblpacking` x WHERE x.id_resi = p.id_printresi AND x.tanggal_packing >= ?)
              AND NOT EXISTS (SELECT 1 FROM $p.`tblresiambilbarang` x WHERE x.id_resi = p.id_printresi AND x.tanggal_resiambilbarang >= ?)
              AND NOT EXISTS (SELECT 1 FROM $p.`tblresikeluar` x WHERE x.id_resi = p.id_printresi AND x.tanggal_resikeluar >= ?)
              AND NOT EXISTS (SELECT 1 FROM $p.`tblresiretur` x WHERE x.id_resi = p.id_printresi AND (x.tanggal_resiretur >= ? OR x.status_retur = 'Terima Retur'))
              AND NOT EXISTS (SELECT 1 FROM $p.`tblbukaretur` x WHERE x.resi_buka = p.noresi AND (x.tanggal_buka_retur >= ? OR x.status_acc = 0))
              AND NOT EXISTS (SELECT 1 FROM $p.`tblreturverifikasi` x WHERE x.no_resi = p.noresi AND x.verified_at >= ?)
              AND NOT EXISTS (SELECT 1 FROM $p.`tblcancelorder` x WHERE x.noresi = p.noresi AND COALESCE(x.updated_at, x.created_at) >= ?)
              AND NOT EXISTS (SELECT 1 FROM $p.`tblcs_complain` x WHERE x.no_resi = p.noresi AND (COALESCE(x.status_penanganan, '') <> 'Selesai' OR COALESCE(x.updated_at, x.created_at) >= ?))
            ORDER BY p.id_printresi LIMIT " . (int) $batas;
        $binds = array((int) $id_awal, $cutoff, $cutoff, $cutoff, $cutoff, $cutoff, $cutoff, $cutoff, $cutoff, $cutoff);
        return $this->ambil_semua($sql, $binds);
    }

    /**
     * Jalankan purna. $uji = TRUE → semua langkah kecuali DELETE (REPLACE ke
     * arsip + verifikasi tetap dilakukan, hasilnya dilaporkan). $maks_resi = 0
     * berarti tanpa batas selain waktu. Mengembalikan ringkasan.
     *
     * Satu batch: calon_purna → REPLACE tiap tabel keluarga ke arsip → verifikasi
     * tiap PK prod ada di arsip (kalau tidak: berhenti, tidak ada yang dihapus)
     * → DELETE prod dalam satu transaksi, urutan keluarga dibalik (anak dulu).
     */
    public function purna($maks_detik = NULL, $maks_resi = 0, $uji = TRUE, $pakai_kunci = TRUE)
    {
        // $pakai_kunci = FALSE saat dipanggil dari jalankan(): kunci, sesi, dan
        // jam mulai sudah diatur pemanggil (batas waktu dibagi dengan sinkron).
        if ($pakai_kunci) {
            $this->mulai = microtime(TRUE);
        }
        $maks_detik = (int) ($maks_detik ?: $this->cfg['maks_detik']);
        $out = array('success' => FALSE, 'uji' => (bool) $uji, 'resi' => 0, 'batch' => 0,
            'baris' => array(), 'khusus' => array(), 'pesan' => '');

        if ($pakai_kunci) {
            $kunci = $this->ambil_kunci();
            if ($kunci !== TRUE) {
                $out['pesan'] = $kunci;
                return $this->selesai($out);
            }
        }
        $debug_lama = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->q("SET SESSION sql_mode = ''");
        $this->q("SET SESSION wait_timeout = 28800");
        // Tanpa ini MariaDB 10.4 mematerialisasi tiap NOT EXISTS di calon_purna()
        // (memindai seluruh indeks tblpacking 2,7 jt baris per batch, ±10 dtk);
        // sebagai sub-kueri berkorelasi jadi lookup indeks per resi (±90 ms/batch).
        $this->q("SET SESSION optimizer_switch = 'materialization=off'");

        try {
            $this->siapkan_db();
            $tolak = $this->gerbang_purna();
            if ($tolak !== '') {
                $out['pesan'] = 'DITOLAK gerbang keselamatan: ' . $tolak;
                $this->log[] = date('H:i:s') . '  purna ' . $out['pesan'];
                $this->catat('purna', NULL, 'FAIL', $out['pesan']);
                throw new Exception($out['pesan']);
            }

            $hari   = (int) $this->cfg['retensi_hari'];
            $cutoff = date('Y-m-d H:i:s', strtotime("-$hari days"));
            $batas  = max(100, (int) $this->cfg['purna_batch_resi']);
            $this->log[] = date('H:i:s') . sprintf('  purna %s: retensi %d hari, cutoff %s, batch %d resi',
                $uji ? 'UJI (tanpa DELETE)' : 'JALANKAN', $hari, $cutoff, $batas);

            $keluarga = $this->cfg['keluarga_resi'];
            $pk = array();
            foreach ($keluarga as $t => $a) {
                $pk[$t] = $this->pk_tabel($t);
                if ($pk[$t] === NULL) {
                    throw new Exception("tabel keluarga '$t' tidak punya PK tunggal — verifikasi tidak mungkin");
                }
                $out['baris'][$t] = 0;
            }

            $id_awal = 0;
            while (TRUE) {
                if ($this->habis_waktu($maks_detik)) {
                    $this->log[] = date('H:i:s') . "  purna: batas waktu $maks_detik dtk tercapai";
                    break;
                }
                $sisa = $maks_resi > 0 ? min($batas, $maks_resi - $out['resi']) : $batas;
                if ($sisa <= 0) {
                    break;
                }
                $calon = $this->calon_purna($id_awal, $sisa, $cutoff);
                if (!$calon) {
                    break;
                }
                $ids = $nos = array();
                foreach ($calon as $c) {
                    $ids[] = (int) $c->id_printresi;
                    $nos[] = $c->noresi;
                }
                $id_awal = max($ids);
                $t0 = microtime(TRUE);

                // 1. Salin keluarga ke arsip (REPLACE = idempoten, menimpa versi lama di arsip).
                foreach ($keluarga as $t => $a) {
                    $pilih = $this->pilih_sql($a, $ids, $nos, $this->prod);
                    $this->kueri("REPLACE INTO `{$this->arsip}`.`$t` SELECT * FROM `{$this->prod}`.`$t` WHERE $pilih");
                }

                // 2. Verifikasi: setiap PK prod dalam batch harus ada di arsip.
                $hilang = array();
                $jml    = array();
                foreach ($keluarga as $t => $a) {
                    $pilih = $this->pilih_sql($a, $ids, $nos, $this->prod, 'p');
                    $jml[$t] = $this->hitung("SELECT COUNT(*) n FROM `{$this->prod}`.`$t` p WHERE $pilih");
                    $n = $this->hitung("SELECT COUNT(*) n FROM `{$this->prod}`.`$t` p WHERE $pilih
                        AND NOT EXISTS (SELECT 1 FROM `{$this->arsip}`.`$t` a WHERE a.`{$pk[$t]}` = p.`{$pk[$t]}`)");
                    if ($n > 0) {
                        $hilang[] = "$t: $n baris";
                    }
                }
                if ($hilang) {
                    $pesan = 'verifikasi gagal, batch TIDAK dihapus — ' . implode('; ', $hilang);
                    $this->catat('purna', NULL, 'FAIL', $pesan);
                    throw new Exception($pesan);
                }

                // 3. Hapus di prod (anak dulu, tblprintresi terakhir) — dilewati saat uji.
                if (!$uji) {
                    $this->db->trans_begin();
                    foreach (array_reverse($keluarga, TRUE) as $t => $a) {
                        $pilih = $this->pilih_sql($a, $ids, $nos, $this->prod);
                        if (!$this->kueri("DELETE FROM `{$this->prod}`.`$t` WHERE $pilih", array(), FALSE)) {
                            $this->db->trans_rollback();
                            $pesan = "DELETE $t gagal, batch dibatalkan: " . $this->pesan_error();
                            $this->catat('purna', $t, 'FAIL', $pesan);
                            throw new Exception($pesan);
                        }
                    }
                    $this->db->trans_commit();
                }

                foreach ($jml as $t => $n) {
                    $out['baris'][$t] += $n;
                }
                $out['resi']  += count($ids);
                $out['batch'] += 1;
                $this->catat('purna', 'batch', 'OK', sprintf('%s%d resi (id %d..%d), %s, %.1f dtk',
                    $uji ? 'UJI ' : '', count($ids), min($ids), $id_awal, $this->ringkas_jml($jml), microtime(TRUE) - $t0));
                if ($out['batch'] % 10 === 0 || count($ids) < $sisa) {
                    $this->log[] = date('H:i:s') . sprintf('  purna: %s resi, %d batch, terakhir id %d',
                        number_format($out['resi']), $out['batch'], $id_awal);
                }
                usleep((int) $this->cfg['jeda_batch_ms'] * 1000);
            }

            // 4. Tabel dengan retensi sendiri (tblkpi, notifications).
            foreach ($this->cfg['retensi_khusus'] as $t => $aturan) {
                if ($this->habis_waktu($maks_detik)) {
                    break;
                }
                $out['khusus'][$t] = $this->purna_khusus($t, $aturan, $uji);
            }

            $out['success'] = TRUE;
            $this->log[] = date('H:i:s') . sprintf('  purna selesai: %s resi dalam %d batch%s; %s',
                number_format($out['resi']), $out['batch'], $uji ? ' (UJI, tidak ada yang dihapus)' : ' DIHAPUS dari prod',
                $this->ringkas_jml($out['baris']));
            $this->catat('purna', NULL, 'OK', ($uji ? 'UJI ' : '') . number_format($out['resi']) . ' resi, ' . $this->ringkas_jml($out['baris']));
        } catch (Throwable $e) {
            $out['pesan'] = $e->getMessage();
            $this->log[]  = date('H:i:s') . '  PURNA BERHENTI: ' . $e->getMessage();
        }

        $this->db->db_debug = $debug_lama;
        if ($pakai_kunci) {
            $this->lepas_kunci();
            return $this->selesai($out);
        }
        return $out;
    }

    /** Purna satu tabel ber-retensi sendiri (by kolom tanggal, batch by PK). Mengembalikan jumlah baris. */
    private function purna_khusus($t, array $aturan, $uji)
    {
        $pk = $this->pk_tabel($t);
        if ($pk === NULL) {
            $this->log[] = date('H:i:s') . "  purna $t dilewati: tanpa PK tunggal";
            return 0;
        }
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$aturan['hari']} days"));
        $batas  = (int) $this->cfg['batch'];
        $total  = 0;
        while (TRUE) {
            $r = $this->ambil_row("SELECT MIN(`$pk`) a, MAX(`$pk`) b, COUNT(*) n FROM (SELECT `$pk` FROM `{$this->prod}`.`$t`
                WHERE `{$aturan['kolom']}` < ? ORDER BY `$pk` LIMIT $batas) x", array($cutoff));
            if (!$r || !$r->n) {
                break;
            }
            $where = "`{$aturan['kolom']}` < " . $this->db->escape($cutoff) . " AND `$pk` BETWEEN {$r->a} AND {$r->b}";
            $this->kueri("REPLACE INTO `{$this->arsip}`.`$t` SELECT * FROM `{$this->prod}`.`$t` WHERE $where");
            $hilang = $this->hitung("SELECT COUNT(*) n FROM `{$this->prod}`.`$t` p WHERE $where
                AND NOT EXISTS (SELECT 1 FROM `{$this->arsip}`.`$t` a WHERE a.`$pk` = p.`$pk`)");
            if ($hilang > 0) {
                throw new Exception("purna $t: verifikasi gagal ($hilang baris belum ada di arsip), tidak dihapus");
            }
            if ($uji) {
                // Saat uji tidak ada yang dihapus, jadi loop akan mengulang batch yang
                // sama — cukup hitung totalnya sekali lalu keluar.
                $total = $this->hitung("SELECT COUNT(*) n FROM `{$this->prod}`.`$t` WHERE `{$aturan['kolom']}` < ?", array($cutoff));
                break;
            }
            $this->kueri("DELETE FROM `{$this->prod}`.`$t` WHERE $where");
            $total += (int) $r->n;
            usleep((int) $this->cfg['jeda_batch_ms'] * 1000);
        }
        $this->log[] = date('H:i:s') . sprintf('  purna %s: %s baris < %s (%d hari)%s',
            $t, number_format($total), $cutoff, $aturan['hari'], $uji ? ' — UJI' : ' dihapus');
        $this->catat('purna', $t, 'OK', ($uji ? 'UJI ' : '') . number_format($total) . " baris < $cutoff");
        return $total;
    }

    private function ringkas_jml(array $jml)
    {
        $s = array();
        foreach ($jml as $t => $n) {
            if ($n > 0) {
                $s[] = str_replace('tbl', '', $t) . '=' . number_format($n);
            }
        }
        return $s ? implode(', ', $s) : '0 baris';
    }

    // ------------------------------------------------------------------
    //  Alat bantu
    // ------------------------------------------------------------------

    /** @var string pesan error kueri terakhir yang gagal */
    private $error_terakhir = '';

    /**
     * Semua akses DB lewat sini. mysqli di PHP 8 melempar mysqli_sql_exception
     * walau db_debug mati, jadi ditangkap di satu tempat; error terakhir disimpan
     * untuk pesan_error(). Mengembalikan objek hasil / TRUE, atau FALSE bila gagal.
     */
    private function q($sql, array $binds = array())
    {
        $this->error_terakhir = '';
        try {
            $hasil = $this->db->query($sql, $binds ?: FALSE);
        } catch (Throwable $e) {
            $this->error_terakhir = $e->getMessage();
            return FALSE;
        }
        if ($hasil === FALSE) {
            $err = $this->db->error();
            $this->error_terakhir = (isset($err['code']) ? $err['code'] : '?') . ' ' . (isset($err['message']) ? $err['message'] : '');
        }
        return $hasil;
    }

    /** Jalankan kueri tulis. Error → Exception, atau FALSE kalau $lempar = FALSE. */
    private function kueri($sql, array $binds = array(), $lempar = TRUE)
    {
        if ($this->q($sql, $binds) === FALSE) {
            $pesan = $this->pesan_error() . ' — ' . substr(preg_replace('/\s+/', ' ', $sql), 0, 200);
            if ($lempar) {
                throw new Exception($pesan);
            }
            return FALSE;
        }
        return TRUE;
    }

    /** Satu baris hasil SELECT, atau NULL kalau kueri gagal / kosong. */
    private function ambil_row($sql, array $binds = array())
    {
        $q = $this->q($sql, $binds);
        return (is_object($q) && method_exists($q, 'row')) ? $q->row() : NULL;
    }

    /** Semua baris hasil SELECT (array objek); array kosong kalau gagal. */
    private function ambil_semua($sql, array $binds = array())
    {
        $q = $this->q($sql, $binds);
        return (is_object($q) && method_exists($q, 'result')) ? $q->result() : array();
    }

    /** Nilai kolom `n` dari SELECT COUNT(*) n ...; Exception kalau kueri gagal. */
    private function hitung($sql, array $binds = array())
    {
        $r = $this->ambil_row($sql, $binds);
        if ($r === NULL) {
            throw new Exception($this->pesan_error() . ' — ' . substr(preg_replace('/\s+/', ' ', $sql), 0, 200));
        }
        return (int) $r->n;
    }

    private function pesan_error()
    {
        return $this->error_terakhir !== '' ? $this->error_terakhir : 'error tidak diketahui';
    }

    private function habis_waktu($maks_detik)
    {
        return (microtime(TRUE) - $this->mulai) >= $maks_detik;
    }

    private function simpan_status($t, $pk, $mode, $wm, $baris, $total, $detik, $ket = NULL)
    {
        $this->q("REPLACE INTO `{$this->arsip}`.`_arsip_status`
            (tabel, kolom_pk, mode, pk_terakhir, baris_terakhir, baris_arsip, terakhir_jalan, durasi_detik, keterangan)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)", array($t, $pk, $mode, $wm, $baris, $total, $detik, $ket));
    }

    private function catat($tahap, $tabel, $status, $pesan)
    {
        $this->q("INSERT INTO `{$this->arsip}`.`_arsip_log` (waktu, tahap, tabel, status, pesan)
            VALUES (NOW(), ?, ?, ?, ?)", array($tahap, $tabel, $status, $pesan));
    }

    private function berkas_kunci()
    {
        return APPPATH . 'cache/arsip_harian.lock';
    }

    /** TRUE kalau kunci didapat; kalau tidak, teks alasannya. */
    private function ambil_kunci()
    {
        $f = $this->berkas_kunci();
        if (is_file($f)) {
            $umur = time() - filemtime($f);
            if ($umur < (int) $this->cfg['kunci_basi_detik']) {
                return "Putaran lain masih berjalan (kunci berumur $umur dtk: $f)";
            }
            $this->log[] = date("H:i:s") . "  " . "kunci basi ($umur dtk) diambil alih";
        }
        return @file_put_contents($f, getmypid() . ' ' . date('Y-m-d H:i:s')) !== FALSE
            ? TRUE : "Tidak bisa menulis kunci $f";
    }

    private function lepas_kunci()
    {
        @unlink($this->berkas_kunci());
    }
}
