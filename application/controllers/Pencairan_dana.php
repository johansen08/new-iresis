<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu "Pencairan Dana" (TIM FINANCE) — pantau pesanan Completed: dananya sudah cair
 * atau belum, dengan tab Semua / Belum Cair / Sudah Cair. Definisi, sumber data, dan
 * patokan warna ada di Pencairan_dana_fcd.
 *
 * Tanggal pencairan diisi dari upload Excel (kolom A nomor pesanan marketplace, kolom B
 * tanggal pencairan). Upload hanya mengisi yang masih kosong -- tidak menimpa, tidak menghapus.
 */
class Pencairan_dana extends MY_Controller
{
    const URI = 'finance/pencairan-dana';

    /** Batas baris Excel per unggahan. */
    const MAKS_BARIS = 20000;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pencairan_dana_fcd');
    }

    /** Halaman menu. */
    public function index()
    {
        $this->show(array(
            'batas_kuning' => Pencairan_dana_fcd::BATAS_KUNING,
            'mp'           => array_values(Pencairan_dana_fcd::peta_mp()),
            'hari_ini'     => date('Y-m-d'),
        ));
    }

    /**
     * Satu halaman daftar (JSON) dan, bila diminta (ring=1), ringkasan per marketplace.
     * Ringkasan hanya bergantung pada rentang tanggal dan kotak cari; pindah tab, halaman, ukuran
     * halaman, marketplace, atau centang tidak mengubahnya, jadi view menyimpannya dan mengirim
     * ring=0 agar server cukup mengambil barisnya. Jumlah per tab/halaman dihitung di view.
     */
    public function get_data()
    {
        if (!$this->_boleh()) {
            $this->make_ajax_response(403, 'Anda tidak punya akses ke menu ini.');
        }

        list($start, $end) = $this->_rentang();
        $mps   = $this->_pilihan_mp();
        $tab   = $this->_tab();
        $merah = $this->input->post('hanya_merah') === '1';
        $cari  = (string) $this->input->post('q');

        $size = (int) $this->input->post('size');
        if (!in_array($size, array(10, 50, 100), true)) $size = 10;
        $halaman = max(1, (int) $this->input->post('page'));

        $data = array('page' => $halaman);

        if ($this->input->post('ring') !== '0') {
            $ring = $this->pencairan_dana_fcd->ringkasan($start, $end, $cari);
            $data['ring'] = array();
            foreach (Pencairan_dana_fcd::peta_mp() as $kode => $nama) {
                $data['ring'][$nama] = $ring[$kode];
            }
        }

        $rows = $this->pencairan_dana_fcd->daftar($start, $end, $mps, $tab, $merah, ($halaman - 1) * $size, $size, $cari);
        $data['rows'] = $this->_baris($rows);

        $this->make_ajax_response(200, 'OK', $data);
    }

    /** Ekspor Excel sesuai filter yang sedang tampil (tanggal, marketplace, tab). Parameter lewat GET (unduhan). */
    public function export()
    {
        if (!$this->_boleh()) {
            show_error('Anda tidak punya akses ke menu ini.', 403);
        }
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        list($start, $end) = $this->_rentang();
        $rows = $this->pencairan_dana_fcd->daftar(
            $start, $end, $this->_pilihan_mp(), $this->_tab(),
            $this->input->post_get('hanya_merah') === '1', 0, 0, (string) $this->input->post_get('q')
        );

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sh = $ss->getActiveSheet();
        $sh->setTitle('Pencairan Dana');
        $sh->fromArray(array('ID Pesanan', 'Tgl Shipped', 'Tgl Delivered', 'Tgl Completed', 'Marketplace', 'Toko', 'Tanggal Cair', 'Cair vs Completed (hari)', 'Umur (hari)', 'Status', 'Harga Final Jubelio', 'Harga Final Laporan MP', 'Selisih (Jubelio - Laporan)', 'Cek Nominal'), null, 'A1');
        $sh->getStyle('A1:N1')->getFont()->setBold(true);
        $cek_label = array('selisih' => 'Ada selisih', 'penuh' => 'Full');

        $baris = 2;
        $peta = Pencairan_dana_fcd::peta_mp();
        $label = array('sudah' => 'Sudah cair', 'kuning' => 'Belum cair (kuning)', 'merah' => 'Belum cair (merah)');
        foreach ($rows as $r) {
            $st = $r->sudah ? 'sudah' : ($r->umur > Pencairan_dana_fcd::BATAS_KUNING ? 'merah' : 'kuning');
            // Nomor pesanan ditulis sebagai teks: 18 digit angka kehilangan presisi di Excel.
            $sh->setCellValueExplicit('A' . $baris, Pencairan_dana_fcd::nomor_pesanan($r->no_pesanan), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sh->setCellValue('B' . $baris, $r->tgl_kirim ? substr((string) $r->tgl_kirim, 0, 10) : '');
            $sh->setCellValue('C' . $baris, '');   // Tgl Delivered: belum ada kolomnya di database
            $sh->setCellValue('D' . $baris, substr((string) $r->tgl_selesai, 0, 10));
            $sh->setCellValue('E' . $baris, isset($peta[$r->mp]) ? $peta[$r->mp] : $r->mp);
            $sh->setCellValue('F' . $baris, (string) $r->toko);
            $sh->setCellValue('G' . $baris, $r->tgl_cair ? substr((string) $r->tgl_cair, 0, 10) : '');
            // negatif = cair sebelum Completed, positif = sesudah Completed
            $sh->setCellValue('H' . $baris, $r->tgl_cair ? $this->_selisih_hari($r->tgl_selesai, $r->tgl_cair) : '');
            $sh->setCellValue('I' . $baris, (int) $r->umur);
            $sh->setCellValue('J' . $baris, $label[$st]);
            $sh->setCellValue('K' . $baris, $r->dana_mp !== null ? (float) $r->dana_mp : '');
            $sh->setCellValue('L' . $baris, $r->dana_file !== null ? (float) $r->dana_file : '');
            $sel = $this->_selisih_rp($r);
            $sh->setCellValue('M' . $baris, $sel !== null ? $sel : '');
            $cek = $this->_cek_nominal($r);
            $sh->setCellValue('N' . $baris, $cek ? $cek_label[$cek] : ($r->sudah ? 'Nominal belum lengkap' : ''));
            $baris++;
        }
        foreach (array('A' => 26, 'B' => 14, 'C' => 14, 'D' => 16, 'E' => 14, 'F' => 22, 'G' => 14, 'H' => 24, 'I' => 12, 'J' => 22, 'K' => 16, 'L' => 18, 'M' => 26, 'N' => 22) as $kol => $lebar) {
            $sh->getColumnDimension($kol)->setWidth($lebar);
        }

        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Pencairan_Dana_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save('php://output');
        exit;
    }

    /**
     * Unggah tanggal pencairan. dry=1: hanya memeriksa dan melaporkan; dry=0: menyimpan.
     * Kolom A = nomor pesanan marketplace (teks), kolom B = tanggal pencairan.
     * Hanya mengisi tanggal pencairan yang masih kosong.
     */
    public function upload()
    {
        if (!$this->_boleh()) {
            $this->make_ajax_response(403, 'Anda tidak punya akses ke menu ini.');
        }
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        try {
            if (!isset($_FILES['berkas']) || $_FILES['berkas']['error'] != 0) {
                throw new \Exception('File Excel tidak ditemukan atau gagal diunggah.');
            }
            $ext = strtolower(pathinfo($_FILES['berkas']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, array('xls', 'xlsx', 'csv'), true)) {
                throw new \Exception('Format file harus .xls, .xlsx, atau .csv.');
            }
            $dry = $this->input->post('dry') !== '0';

            $path = $_FILES['berkas']['tmp_name'];
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(\PhpOffice\PhpSpreadsheet\IOFactory::identify($path));
            $reader->setReadDataOnly(true);
            $mentah = $reader->load($path)->getActiveSheet()->toArray(null, true, false, false);

            if (empty($mentah)) {
                throw new \Exception('File Excel kosong.');
            }
            if (count($mentah) > self::MAKS_BARIS + 1) {
                throw new \Exception('Maksimal ' . self::MAKS_BARIS . ' baris per unggahan.');
            }

            // Peta kolom. Bila baris pertama berisi judul "ID Pesanan" + "Tanggal pencairan" dan/atau
            // "Harga final (setelah diskon)" (format laporan MP), kolom dicari lewat judul; bila tidak, format lama:
            // A = nomor pesanan, B = tanggal pencairan, C (opsional) = harga final.
            $kol = $this->_peta_kolom(isset($mentah[0]) ? $mentah[0] : array());
            if ($kol) {
                $mulai = 1;
            } else {
                $kol = array('id' => 0, 'tgl' => 1, 'dana' => 2);
                // Baris pertama = judul kolom bila kolom B-nya bukan tanggal.
                $mulai = ($this->_tanggal(isset($mentah[0][1]) ? $mentah[0][1] : '') === null) ? 1 : 0;
            }

            $lap = array(
                'dry' => $dry, 'total' => 0, 'diisi' => 0, 'nominal_diisi' => 0, 'sudah_terisi' => 0, 'tidak_ditemukan' => 0,
                'tanggal_salah' => 0, 'nomor_salah' => 0, 'duplikat' => 0, 'belum_completed' => 0,
                'contoh_tidak_ditemukan' => array(), 'contoh_salah' => array(),
            );
            $lihat = array();

            if (!$dry) $this->db->trans_start();

            for ($i = $mulai; $i < count($mentah); $i++) {
                $a = isset($mentah[$i][$kol['id']]) ? $mentah[$i][$kol['id']] : '';
                $b = ($kol['tgl'] !== null && isset($mentah[$i][$kol['tgl']])) ? $mentah[$i][$kol['tgl']] : '';
                $c = ($kol['dana'] !== null && isset($mentah[$i][$kol['dana']])) ? $mentah[$i][$kol['dana']] : '';
                if (trim((string) $a) === '' && trim((string) $b) === '' && trim((string) $c) === '') continue;
                $lap['total']++;
                $baris_ke = $i + 1;

                // Angka 18 digit yang tersimpan sebagai angka di Excel sudah kehilangan presisi.
                if (is_float($a) || (is_int($a) && $a > 999999999999999)) {
                    $lap['nomor_salah']++;
                    $this->_contoh($lap['contoh_salah'], 'Baris ' . $baris_ke . ': nomor pesanan berformat angka (ubah kolom A jadi Teks)');
                    continue;
                }
                $nomor = Pencairan_dana_fcd::nomor_pesanan(trim((string) $a));
                if ($nomor === '' || !preg_match('/^[A-Za-z0-9]+$/', $nomor)) {
                    $lap['nomor_salah']++;
                    $this->_contoh($lap['contoh_salah'], 'Baris ' . $baris_ke . ': nomor pesanan tidak valid (' . mb_substr((string) $a, 0, 40) . ')');
                    continue;
                }
                $tgl = $this->_tanggal($b);
                $dana = $this->_rupiah($c);
                // Tanggal boleh kosong asal dana diterima ada (dan sebaliknya); tanggal yang diisi tapi
                // tidak terbaca, atau dana yang diisi tapi bukan angka, menolak barisnya.
                if (($tgl === null && trim((string) $b) !== '') || ($dana === null && trim((string) $c) !== '')
                    || ($tgl === null && $dana === null)) {
                    $lap['tanggal_salah']++;
                    $this->_contoh($lap['contoh_salah'], 'Baris ' . $baris_ke . ': tanggal/dana tidak terbaca (' . mb_substr((string) $b, 0, 30) . ' | ' . mb_substr((string) $c, 0, 20) . ')');
                    continue;
                }
                if (isset($lihat[$nomor])) {
                    $lap['duplikat']++;
                    continue;
                }
                $lihat[$nomor] = true;

                $resi = $this->pencairan_dana_fcd->cari_resi($nomor);
                if (!$resi) {
                    $lap['tidak_ditemukan']++;
                    $this->_contoh($lap['contoh_tidak_ditemukan'], $nomor);
                    continue;
                }
                $perlu_tgl  = $tgl !== null && $resi->tanggal_pencairan === null;
                $perlu_dana = $dana !== null && $resi->dana_diterima_file === null;
                if (!$perlu_tgl && !$perlu_dana) {
                    $lap['sudah_terisi']++;
                    continue;
                }
                if ($resi->status_pesanan !== 'COMPLETED') $lap['belum_completed']++;
                if ($dry || $this->pencairan_dana_fcd->isi_pencairan($resi->id_printresi, $perlu_tgl ? $tgl : null, $perlu_dana ? $dana : null)) {
                    $lap['diisi']++;
                    if ($perlu_dana) $lap['nominal_diisi']++;
                }
            }

            if (!$dry) {
                $this->db->trans_complete();
                if ($this->db->trans_status() === false) {
                    throw new \Exception('Penyimpanan gagal; tidak ada data yang diubah.');
                }
            }

            $this->make_ajax_response(200, 'OK', $lap);
        } catch (\Exception $e) {
            $this->make_ajax_response(500, $e->getMessage());
        }
    }

    // ------------------------------------------------------------ utilitas

    /** Role user harus punya akses ke menu ini (sidebar saja tidak cukup untuk endpoint POST). */
    private function _boleh()
    {
        $role = isset($this->data['user']['hakakses']) ? $this->data['user']['hakakses'] : null;
        if (empty($role)) return false;

        $menu = $this->db->get_where('menu', array('uri' => self::URI))->row();
        if (!$menu) return false;

        return $this->db->where(array('menuid' => $menu->id, 'roleid' => $role))
            ->count_all_results('roleaccess') > 0;
    }

    /** Rentang tanggal COMPLETED 'Y-m-d H:i:s'; default 30 hari terakhir. */
    private function _rentang()
    {
        $start = $this->input->post_get('start_date');
        $end   = $this->input->post_get('end_date');

        $ok = function ($t) { return is_string($t) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $t) && strtotime($t) !== false; };
        $start = $ok($start) ? $start : date('Y-m-d', strtotime('-29 days'));
        $end   = $ok($end)   ? $end   : date('Y-m-d');
        if ($start > $end) { $tmp = $start; $start = $end; $end = $tmp; }

        return array($start . ' 00:00:00', $end . ' 23:59:59');
    }

    /** Nama marketplace terpilih dari POST mps[] ([] = semua). */
    private function _pilihan_mp()
    {
        $in = $this->input->post_get('mps');
        if (!is_array($in)) return array();
        $peta = array_flip(Pencairan_dana_fcd::peta_mp());   // nama => awalan
        $hasil = array();
        foreach ($in as $nama) {
            if (is_string($nama) && isset($peta[$nama])) $hasil[] = $peta[$nama];
        }
        return array_values(array_unique($hasil));
    }

    private function _tab()
    {
        $tab = $this->input->post_get('tab');
        return in_array($tab, array('semua', 'belum', 'sudah', 'selisih', 'full'), true) ? $tab : 'belum';
    }

    /** Baris untuk JSON: tanggal ISO (format tampil diurus view), status sudah dihitung. */
    private function _baris($rows)
    {
        $peta = Pencairan_dana_fcd::peta_mp();
        $hasil = array();
        foreach ($rows as $r) {
            $umur = (int) $r->umur;
            $hasil[] = array(
                'no'      => Pencairan_dana_fcd::nomor_pesanan($r->no_pesanan),
                'mp'      => isset($peta[$r->mp]) ? $peta[$r->mp] : $r->mp,
                'toko'    => (string) $r->toko,
                'kirim'   => $r->tgl_kirim ? substr((string) $r->tgl_kirim, 0, 10) : null,
                'deliver' => null,   // belum ada kolomnya di database
                'selesai' => substr((string) $r->tgl_selesai, 0, 10),
                'cair'    => $r->tgl_cair ? substr((string) $r->tgl_cair, 0, 10) : null,
                'selisih' => $r->tgl_cair ? $this->_selisih_hari($r->tgl_selesai, $r->tgl_cair) : null,
                'dana_mp'   => $r->dana_mp !== null ? (float) $r->dana_mp : null,
                'dana_file' => $r->dana_file !== null ? (float) $r->dana_file : null,
                'rp_selisih' => $this->_selisih_rp($r),
                'cek'     => $this->_cek_nominal($r),
                'umur'    => $umur,
                'status'  => $r->sudah ? 'sudah' : ($umur > Pencairan_dana_fcd::BATAS_KUNING ? 'merah' : 'kuning'),
            );
        }
        return $hasil;
    }

    /** Selisih rupiah Jubelio - laporan MP; null bila salah satu nominal belum ada. */
    private function _selisih_rp($r)
    {
        if ($r->dana_mp === null || $r->dana_file === null) return null;
        return round((float) $r->dana_mp - (float) $r->dana_file, 2);
    }

    /** Hasil banding nominal pesanan yang sudah cair: 'selisih', 'penuh', atau null (belum cair / nominal belum lengkap). */
    private function _cek_nominal($r)
    {
        if (!$r->sudah) return null;
        $d = $this->_selisih_rp($r);
        if ($d === null) return null;
        return abs($d) > Pencairan_dana_fcd::TOLERANSI ? 'selisih' : 'penuh';
    }

    /** Selisih hari kalender antara tanggal completed dan tanggal cair: negatif = cair sebelum Completed. */
    private function _selisih_hari($selesai, $cair)
    {
        $a = strtotime(substr((string) $selesai, 0, 10));
        $b = strtotime(substr((string) $cair, 0, 10));
        return (int) round(($b - $a) / 86400);
    }

    /**
     * Peta kolom dari baris judul laporan MP: 'id' (ID Pesanan / nomor pesanan), 'tgl' (Tanggal pencairan),
     * 'dana' (Harga final (setelah diskon); "Dana diterima" sementara tidak dipakai). Mengembalikan null bila bukan baris judul (kolom ID + salah satu dari
     * tanggal/dana tidak ketemu), supaya format lama A/B tetap berlaku.
     */
    private function _peta_kolom(array $baris)
    {
        $peta = array('id' => null, 'tgl' => null, 'dana' => null);
        foreach ($baris as $i => $sel) {
            $t = strtolower(trim(preg_replace('/\s+/', ' ', (string) $sel)));
            if ($t === '') continue;
            if ($peta['id'] === null && in_array($t, array('id pesanan', 'no pesanan', 'nomor pesanan', 'order id'), true)) $peta['id'] = $i;
            elseif ($peta['tgl'] === null && strpos($t, 'tanggal pencairan') === 0) $peta['tgl'] = $i;
            elseif ($peta['dana'] === null && ($t === 'harga final (setelah diskon)' || $t === 'harga final')) $peta['dana'] = $i;
        }
        return ($peta['id'] !== null && ($peta['tgl'] !== null || $peta['dana'] !== null)) ? $peta : null;
    }

    /**
     * Baca nominal rupiah dari sel: angka, atau teks seperti "Rp7.577", "-Rp1.870", "7.577,50".
     * Mengembalikan float, atau null bila kosong / bukan angka.
     */
    private function _rupiah($v)
    {
        if ($v === null || trim((string) $v) === '') return null;
        if (is_int($v) || is_float($v)) return (float) $v;
        $s = preg_replace('/[^0-9,.\-]/', '', (string) $v);
        if ($s === '' || $s === '-') return null;
        if (strpos($s, ',') !== false) {
            $s = str_replace(',', '.', str_replace('.', '', $s));      // 7.577,50 -> 7577.50
        } elseif (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $s)) {
            $s = str_replace('.', '', $s);                             // 7.577 -> 7577
        }
        return is_numeric($s) ? (float) $s : null;
    }

    /** Batasi contoh yang dikembalikan supaya respons tetap kecil. */
    private function _contoh(array &$daftar, $teks)
    {
        if (count($daftar) < 20) $daftar[] = $teks;
    }

    /**
     * Baca tanggal dari sel Excel: serial Excel, d-m-Y [H:i[:s]], d/m/Y, atau Y-m-d.
     * Mengembalikan 'Y-m-d H:i:s', atau null bila tidak terbaca / di masa depan.
     */
    private function _tanggal($v)
    {
        if ($v === null || $v === '') return null;
        $hasil = null;

        if (is_int($v) || is_float($v) || (is_string($v) && is_numeric($v))) {
            $n = (float) $v;
            if ($n > 40000 && $n < 80000) {
                $hasil = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($n)->format('Y-m-d H:i:s');
            }
        } elseif (is_string($v)) {
            $s = trim($v);
            if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})(?:[ T](\d{1,2}):(\d{2})(?::(\d{2}))?)?$/', $s, $m)) {
                if (checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
                    $hasil = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $m[3], $m[2], $m[1],
                        isset($m[4]) ? $m[4] : 0, isset($m[5]) ? $m[5] : 0, isset($m[6]) ? $m[6] : 0);
                }
            } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{1,2}):(\d{2})(?::(\d{2}))?)?/', $s, $m)) {
                if (checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                    $hasil = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $m[1], $m[2], $m[3],
                        isset($m[4]) ? $m[4] : 0, isset($m[5]) ? $m[5] : 0, isset($m[6]) ? $m[6] : 0);
                }
            }
        }

        // Tanggal pencairan tidak mungkin di masa depan (toleransi 1 hari untuk zona waktu).
        if ($hasil !== null && substr($hasil, 0, 10) > date('Y-m-d', strtotime('+1 day'))) return null;
        return $hasil;
    }
}
