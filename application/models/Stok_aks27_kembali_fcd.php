<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model menu TIM PACKER -> "Konfirmasi Pinset Kembali" dan TIM PURCHASING -> "Persetujuan Stok AKS27".
 *
 * Pinset bonus AKS27-RDM sudah keluar (PENJUALAN -1) saat resi discan packer. Kalau resinya lalu
 * CANCELED atau diretur, stok TIDAK naik otomatis: packer yang memegang stok harus mengonfirmasi
 * fisiknya dulu (scan resi). Hasil konfirmasi:
 *  - ADA                : stok +1 langsung (ref CANCEL atau RETURAN).
 *  - TIDAK_ADA / pembeli tidak kirim balik : stok tetap (TIDAK_KEMBALI).
 *  - TIDAK_ADA / lupa dimasukkan saat packing : pinset ternyata masih di rak, jadi diajukan koreksi +1
 *                         yang baru berlaku setelah disetujui purchasing (TIDAK_PERNAH_MASUK).
 *  - RUSAK              : stok tetap, hanya dicatat.
 * Opname (hitung fisik) juga diajukan packer dan berlaku setelah disetujui purchasing. Hitungnya buta:
 * packer tidak melihat saldo sistem, selisih dihitung server saat pengajuan.
 * Satu resi satu keputusan (noresi UNIQUE + kunci buku CANCEL:/RETUR:). Tidak ada UPDATE/DELETE
 * pada buku stok; kebenaran diperbaiki dengan baris koreksi baru.
 */
class Stok_aks27_kembali_fcd extends CI_Model
{
    /** Resi menunggu lebih lama dari ini (hari) ditandai merah. */
    const HARI_TERLAMBAT = 14;

    const PILIHAN = ['ADA', 'TIDAK_ADA', 'RUSAK'];

    /** Alasan "tidak ada" -> status keputusan. */
    const ALASAN_TIDAK_ADA = [
        'PEMBELI_TIDAK_KIRIM' => 'TIDAK_KEMBALI',
        'LUPA_DIMASUKKAN'     => 'TIDAK_PERNAH_MASUK',
    ];

    const ALASAN_OPNAME = ['HITUNG_ULANG', 'BARANG_HILANG', 'SALAH_CATAT', 'LAINNYA'];

    private function hasil($ok, $pesan, $data = [])
    {
        return ['ok' => $ok, 'pesan' => $pesan, 'data' => $data];
    }

    /** Resi yang sudah PENJUALAN, lalu CANCELED / diretur, dan belum ada keputusan. */
    private function sql_menunggu()
    {
        return "SELECT j.no_ref AS noresi, j.tanggal AS tgl_jual,"
            . " IF(pr.status_pesanan = 'CANCELED', 'CANCEL', 'RETUR') AS jenis,"
            . " DATEDIFF(CURDATE(), j.tanggal) AS umur"
            . " FROM tblstok_aks27 j JOIN tblprintresi pr ON pr.noresi = j.no_ref"
            . " WHERE j.ref = 'PENJUALAN'"
            . "  AND (pr.status_pesanan = 'CANCELED'"
            . "       OR EXISTS (SELECT 1 FROM tblresiretur rr WHERE rr.id_resi = pr.id_printresi))"
            . "  AND NOT EXISTS (SELECT 1 FROM tblstok_aks27_kembali k WHERE k.noresi = j.no_ref)"
            . "  AND NOT EXISTS (SELECT 1 FROM tblstok_aks27 c WHERE c.kunci IN (CONCAT('CANCEL:', j.no_ref), CONCAT('RETUR:', j.no_ref)))"
            . " GROUP BY j.id";
    }

    public function menunggu($batas = 300)
    {
        $rows = $this->db->query($this->sql_menunggu() . ' ORDER BY j.tanggal ASC, j.id ASC LIMIT ' . (int) $batas)->result_array();
        foreach ($rows as &$r) {
            $r['umur']      = (int) $r['umur'];
            $r['terlambat'] = $r['umur'] > self::HARI_TERLAMBAT;
        }
        unset($r);
        return $rows;
    }

    /** Jumlah resi menunggu dan yang terlambat (kartu di menu Stok AKS27). */
    public function hitung_menunggu()
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS n, COALESCE(SUM(m.umur > ' . (int) self::HARI_TERLAMBAT . '), 0) AS lambat FROM (' . $this->sql_menunggu() . ') m'
        )->row_array();
        return ['menunggu' => (int) $row['n'], 'terlambat' => (int) $row['lambat']];
    }

    public function hitung_ajuan()
    {
        return (int) $this->db->query("SELECT COUNT(*) AS n FROM tblstok_aks27_ajuan WHERE status = 'MENUNGGU'")->row()->n;
    }

    /**
     * Periksa resi hasil scan: harus sudah PENJUALAN, berstatus CANCELED / diretur, belum diputuskan.
     * @return array ok, pesan, data{noresi, jenis, tgl_jual}
     */
    public function cek($noresi)
    {
        $noresi = trim((string) $noresi);
        if ($noresi === '') {
            return $this->hasil(FALSE, 'Scan atau ketik nomor resi dulu.');
        }
        $ada = $this->db->query(
            "SELECT k.status FROM tblstok_aks27_kembali k WHERE k.noresi = ? LIMIT 1", [$noresi]
        )->row_array();
        if ($ada) {
            return $this->hasil(FALSE, 'Resi ' . $noresi . ' sudah dikonfirmasi sebelumnya (' . str_replace('_', ' ', $ada['status']) . ').');
        }
        $lama = $this->db->query(
            "SELECT 1 FROM tblstok_aks27 WHERE kunci IN (CONCAT('CANCEL:', ?), CONCAT('RETUR:', ?)) LIMIT 1", [$noresi, $noresi]
        )->row_array();
        if ($lama) {
            return $this->hasil(FALSE, 'Pinset resi ' . $noresi . ' sudah tercatat kembali di buku stok.');
        }
        $jual = $this->db->query("SELECT 1 FROM tblstok_aks27 WHERE kunci = CONCAT('JUAL:', ?) LIMIT 1", [$noresi])->row_array();
        if (!$jual) {
            return $this->hasil(FALSE, 'Resi ' . $noresi . ' tidak pernah memotong stok pinset bonus, jadi tidak ada yang dikembalikan.');
        }
        $m = $this->db->query('SELECT x.* FROM (' . $this->sql_menunggu() . ') x WHERE x.noresi = ? LIMIT 1', [$noresi])->row_array();
        if (!$m) {
            return $this->hasil(FALSE, 'Resi ' . $noresi . ' belum berstatus CANCELED atau retur. Konfirmasi baru bisa dilakukan setelah itu.');
        }
        return $this->hasil(TRUE, 'OK', ['noresi' => $m['noresi'], 'jenis' => $m['jenis'], 'tgl_jual' => $m['tgl_jual']]);
    }

    /**
     * Simpan keputusan fisik untuk satu resi.
     * @param string $pilihan ADA | TIDAK_ADA | RUSAK
     * @param string $alasan  wajib untuk TIDAK_ADA (kunci ALASAN_TIDAK_ADA)
     */
    public function konfirmasi($noresi, $pilihan, $alasan, $id_user)
    {
        $pilihan = strtoupper(trim((string) $pilihan));
        $alasan  = strtoupper(trim((string) $alasan));
        if (!in_array($pilihan, self::PILIHAN, TRUE)) {
            return $this->hasil(FALSE, 'Pilihan tidak dikenal.');
        }
        if ($pilihan === 'TIDAK_ADA' && !isset(self::ALASAN_TIDAK_ADA[$alasan])) {
            return $this->hasil(FALSE, 'Alasan wajib dipilih untuk pinset yang tidak ada.');
        }

        $c = $this->cek($noresi);
        if (!$c['ok']) {
            return $c;
        }
        $noresi = $c['data']['noresi'];
        $jenis  = $c['data']['jenis'];

        $status = 'KEMBALI';
        $catat  = '';
        if ($pilihan === 'TIDAK_ADA') {
            $status = self::ALASAN_TIDAK_ADA[$alasan];
            $catat  = $alasan === 'LUPA_DIMASUKKAN' ? 'lupa dimasukkan saat packing' : 'pembeli tidak mengirim balik';
        } elseif ($pilihan === 'RUSAK') {
            $status = 'RUSAK';
            $catat  = 'pinset kembali tapi rusak';
        }

        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->trans_start();
        $now = date('Y-m-d H:i:s');

        $this->db->query(
            'INSERT IGNORE INTO tblstok_aks27_kembali (noresi, jenis, status, catatan, created, createdby) VALUES (?, ?, ?, ?, ?, ?)',
            [$noresi, $jenis, $status, $catat, $now, (int) $id_user]
        );
        $baru = $this->db->affected_rows() === 1;
        $id   = $baru ? (int) $this->db->insert_id() : 0;

        if ($baru && $status === 'KEMBALI') {
            $this->db->query(
                "INSERT IGNORE INTO tblstok_aks27 (ref, tanggal, jam, masuk, keluar, qty, no_ref, keterangan, flag, kunci, created, createdby)"
                . " VALUES (?, CURDATE(), CURTIME(), 1, 0, NULL, ?, 'pinset kembali, dikonfirmasi packer', NULL, ?, ?, ?)",
                [$jenis === 'CANCEL' ? 'CANCEL' : 'RETURAN', $noresi, ($jenis === 'CANCEL' ? 'CANCEL:' : 'RETUR:') . $noresi, $now, (int) $id_user]
            );
        } elseif ($baru && $status === 'TIDAK_PERNAH_MASUK') {
            $this->db->query(
                "INSERT INTO tblstok_aks27_ajuan (jenis, noresi, selisih, alasan, status, diajukan, diajukanby)"
                . " VALUES ('LUPA_MASUK', ?, 1, 'lupa dimasukkan saat packing, pinset masih di rak', 'MENUNGGU', ?, ?)",
                [$noresi, $now, (int) $id_user]
            );
            $this->db->query('UPDATE tblstok_aks27_kembali SET ajuan_id = ? WHERE id = ?', [(int) $this->db->insert_id(), $id]);
        }
        $this->db->trans_complete();
        $ok = $this->db->trans_status() !== FALSE && $baru;
        $this->db->db_debug = $debug;

        if (!$ok) {
            return $this->hasil(FALSE, 'Gagal menyimpan konfirmasi. Coba lagi.');
        }
        $pesan = [
            'KEMBALI'            => 'Tersimpan: pinset kembali, stok bertambah 1.',
            'TIDAK_KEMBALI'      => 'Tersimpan: pinset tidak kembali, stok tidak berubah.',
            'TIDAK_PERNAH_MASUK' => 'Tersimpan: koreksi +1 diajukan ke Purchasing, stok berubah setelah disetujui.',
            'RUSAK'              => 'Tersimpan: pinset rusak, stok tidak berubah.',
        ];
        return $this->hasil(TRUE, $pesan[$status], ['status' => $status]);
    }

    /** Ajukan opname: packer memasukkan hitung fisik; selisih dihitung di sini terhadap saldo sistem. */
    public function ajukan_opname($fisik, $alasan, $catatan, $id_user)
    {
        if (!preg_match('/^\d{1,7}$/', trim((string) $fisik))) {
            return $this->hasil(FALSE, 'Jumlah fisik harus bilangan bulat 0 atau lebih.');
        }
        $alasan = strtoupper(trim((string) $alasan));
        if (!in_array($alasan, self::ALASAN_OPNAME, TRUE)) {
            return $this->hasil(FALSE, 'Alasan wajib dipilih.');
        }
        $catatan = trim((string) $catatan);
        if ($alasan === 'LAINNYA' && $catatan === '') {
            return $this->hasil(FALSE, 'Untuk alasan Lainnya, catatan wajib diisi.');
        }
        if ($this->db->query("SELECT 1 FROM tblstok_aks27_ajuan WHERE jenis = 'OPNAME' AND status = 'MENUNGGU' LIMIT 1")->row_array()) {
            return $this->hasil(FALSE, 'Masih ada opname yang menunggu persetujuan Purchasing. Tunggu diputuskan dulu.');
        }

        $this->load->model('stok_aks27_fcd');
        $fisik   = (int) $fisik;
        $sistem  = (int) $this->stok_aks27_fcd->saldo();
        $selisih = $fisik - $sistem;
        if ($selisih === 0) {
            return $this->hasil(TRUE, 'Hitungan fisik sama dengan stok sistem. Tidak ada yang perlu diajukan.', ['selisih' => 0]);
        }

        $teks = str_replace('_', ' ', $alasan) . ($catatan !== '' ? ': ' . $catatan : '');
        $this->db->query(
            "INSERT INTO tblstok_aks27_ajuan (jenis, noresi, selisih, qty_fisik, qty_sistem, alasan, status, diajukan, diajukanby)"
            . " VALUES ('OPNAME', NULL, ?, ?, ?, ?, 'MENUNGGU', ?, ?)",
            [$selisih, $fisik, $sistem, mb_substr($teks, 0, 255), date('Y-m-d H:i:s'), (int) $id_user]
        );
        return $this->hasil(TRUE, 'Opname diajukan ke Purchasing. Stok berubah setelah disetujui.', ['selisih' => $selisih]);
    }

    /** Riwayat konfirmasi dan pengajuan terbaru (layar packer). Saldo sistem sengaja tidak ikut. */
    public function riwayat_packer()
    {
        $kembali = $this->db->query(
            "SELECT k.noresi, k.jenis, k.status, k.catatan, k.created, COALESCE(NULLIF(u.name, ''), u.username, '') AS oleh"
            . " FROM tblstok_aks27_kembali k LEFT JOIN tbluser u ON u.id_user = k.createdby ORDER BY k.id DESC LIMIT 30"
        )->result_array();
        $ajuan = $this->db->query(
            "SELECT a.id, a.jenis, a.noresi, a.alasan, a.status, a.diajukan, a.catatan_putus, COALESCE(NULLIF(u.name, ''), u.username, '') AS oleh"
            . " FROM tblstok_aks27_ajuan a LEFT JOIN tbluser u ON u.id_user = a.diajukanby ORDER BY a.id DESC LIMIT 15"
        )->result_array();
        return ['kembali' => $kembali, 'ajuan' => $ajuan];
    }

    /** Antrean persetujuan + keputusan terakhir (layar purchasing). */
    public function antrean()
    {
        $sql = "SELECT a.id, a.jenis, a.noresi, a.selisih, a.qty_fisik, a.qty_sistem, a.alasan, a.status, a.diajukan, a.diputus, a.catatan_putus,"
            . " COALESCE(NULLIF(u.name, ''), u.username, '') AS pengaju, COALESCE(NULLIF(p.name, ''), p.username, '') AS pemutus"
            . " FROM tblstok_aks27_ajuan a LEFT JOIN tbluser u ON u.id_user = a.diajukanby LEFT JOIN tbluser p ON p.id_user = a.diputusby";
        $menunggu = $this->db->query($sql . " WHERE a.status = 'MENUNGGU' ORDER BY a.id ASC")->result_array();
        $putus    = $this->db->query($sql . " WHERE a.status <> 'MENUNGGU' ORDER BY a.diputus DESC, a.id DESC LIMIT 30")->result_array();
        return ['menunggu' => $menunggu, 'putus' => $putus];
    }

    /** Setujui (menulis baris PENYESUAIAN di buku stok) atau tolak satu pengajuan. */
    public function putuskan($id, $setuju, $catatan, $id_user)
    {
        $id = (int) $id;
        $catatan = mb_substr(trim((string) $catatan), 0, 255);
        if (!$setuju && $catatan === '') {
            return $this->hasil(FALSE, 'Alasan penolakan wajib diisi.');
        }

        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->trans_start();
        $a = $this->db->query('SELECT * FROM tblstok_aks27_ajuan WHERE id = ? FOR UPDATE', [$id])->row_array();
        $pesan = NULL;
        if (!$a) {
            $pesan = 'Pengajuan tidak ditemukan.';
        } elseif ($a['status'] !== 'MENUNGGU') {
            $pesan = 'Pengajuan ini sudah diputuskan.';
        } else {
            $now = date('Y-m-d H:i:s');
            $stok_id = NULL;
            if ($setuju) {
                $this->load->model('stok_aks27_fcd');
                $selisih = (int) $a['selisih'];
                if ($selisih < 0 && $this->stok_aks27_fcd->saldo() + $selisih < 0) {
                    $pesan = 'Disetujui akan membuat stok negatif. Periksa dulu saldo di menu Stok AKS27-RDM.';
                } else {
                    $ket = $a['jenis'] === 'OPNAME'
                        ? 'opname: fisik ' . $a['qty_fisik'] . ', sistem ' . $a['qty_sistem'] . ' (' . $a['alasan'] . ')'
                        : 'koreksi: ' . $a['alasan'];
                    $this->db->query(
                        "INSERT INTO tblstok_aks27 (ref, tanggal, jam, masuk, keluar, qty, no_ref, keterangan, flag, kunci, created, createdby)"
                        . " VALUES ('PENYESUAIAN', CURDATE(), CURTIME(), ?, ?, NULL, ?, ?, ?, ?, ?, ?)",
                        [max($selisih, 0), max(-$selisih, 0), (string) $a['noresi'], mb_substr($ket, 0, 255),
                         $a['jenis'] === 'OPNAME' ? 'opname' : 'koreksi', 'AJUAN:' . $id, $now, (int) $id_user]
                    );
                    $stok_id = (int) $this->db->insert_id();
                }
            }
            if ($pesan === NULL) {
                $this->db->query(
                    'UPDATE tblstok_aks27_ajuan SET status = ?, diputus = ?, diputusby = ?, catatan_putus = ?, stok_id = ? WHERE id = ? AND status = \'MENUNGGU\'',
                    [$setuju ? 'DISETUJUI' : 'DITOLAK', $now, (int) $id_user, $catatan, $stok_id, $id]
                );
            }
        }
        $this->db->trans_complete();
        $ok = $pesan === NULL && $this->db->trans_status() !== FALSE;
        $this->db->db_debug = $debug;

        if (!$ok) {
            return $this->hasil(FALSE, $pesan !== NULL ? $pesan : 'Gagal menyimpan keputusan. Coba lagi.');
        }
        return $this->hasil(TRUE, $setuju ? 'Disetujui. Stok sudah disesuaikan.' : 'Ditolak. Stok tidak berubah.');
    }
}
