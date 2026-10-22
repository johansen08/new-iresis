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

    /** Ringkasan + satu halaman daftar (JSON). */
    public function get_data()
    {
        if (!$this->_boleh()) {
            $this->make_ajax_response(403, 'Anda tidak punya akses ke menu ini.');
        }

        list($start, $end) = $this->_rentang();
        $mps   = $this->_pilihan_mp();
        $tab   = $this->_tab();
        $merah = $this->input->post('hanya_merah') === '1';

        $size = (int) $this->input->post('size');
        if (!in_array($size, array(10, 50, 100), true)) $size = 10;

        $ring = $this->pencairan_dana_fcd->ringkasan($start, $end);
        $awalan_dipilih = $this->_awalan_dipilih($mps);

        // Jumlah di daftar marketplace = semua pesanan pada rentang (tanpa filter marketplace).
        $per_mp = array();
        foreach (Pencairan_dana_fcd::peta_mp() as $kode => $nama) {
            $per_mp[] = array('nama' => $nama, 'jumlah' => $ring[$kode]['n']);
        }

        // Angka kartu dan tab mengikuti filter marketplace.
        $n = $sudah = $kuning = $merah_n = 0;
        foreach ($awalan_dipilih as $kode) {
            $n       += $ring[$kode]['n'];
            $sudah   += $ring[$kode]['sudah'];
            $kuning  += $ring[$kode]['kuning'];
            $merah_n += $ring[$kode]['merah'];
        }
        $belum = $kuning + $merah_n;

        if ($tab === 'sudah') {
            $total_tab = $sudah;
        } elseif ($tab === 'belum') {
            $total_tab = $merah ? $merah_n : $belum;
        } else {
            $total_tab = $n;
        }

        $halaman = max(1, (int) $this->input->post('page'));
        $maks_hal = max(1, (int) ceil($total_tab / $size));
        if ($halaman > $maks_hal) $halaman = $maks_hal;

        $rows = $this->pencairan_dana_fcd->daftar($start, $end, $mps, $tab, $merah, ($halaman - 1) * $size, $size);

        $this->make_ajax_response(200, 'OK', array(
            'ringkasan' => array(
                'total' => $n, 'sudah' => $sudah, 'kuning' => $kuning, 'merah' => $merah_n, 'belum' => $belum,
            ),
            'per_mp'    => $per_mp,
            'total_tab' => $total_tab,
            'page'      => $halaman,
            'pages'     => $maks_hal,
            'rows'      => $this->_baris($rows),
        ));
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
            $this->input->post_get('hanya_merah') === '1', 0, 0
        );

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sh = $ss->getActiveSheet();
        $sh->setTitle('Pencairan Dana');
        $sh->fromArray(array('ID Pesanan', 'Marketplace', 'Tanggal Completed', 'Tanggal Cair', 'Umur (hari)', 'Status'), null, 'A1');
        $sh->getStyle('A1:F1')->getFont()->setBold(true);

        $baris = 2;
        $peta = Pencairan_dana_fcd::peta_mp();
        $label = array('sudah' => 'Sudah cair', 'kuning' => 'Belum cair (kuning)', 'merah' => 'Belum cair (merah)');
        foreach ($rows as $r) {
            $st = $r->sudah ? 'sudah' : ($r->umur > Pencairan_dana_fcd::BATAS_KUNING ? 'merah' : 'kuning');
            // Nomor pesanan ditulis sebagai teks: 18 digit angka kehilangan presisi di Excel.
            $sh->setCellValueExplicit('A' . $baris, Pencairan_dana_fcd::nomor_pesanan($r->no_pesanan), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sh->setCellValue('B' . $baris, isset($peta[$r->mp]) ? $peta[$r->mp] : $r->mp);
            $sh->setCellValue('C' . $baris, substr((string) $r->tgl_selesai, 0, 10));
            $sh->setCellValue('D' . $baris, $r->tgl_cair ? substr((string) $r->tgl_cair, 0, 10) : '');
            $sh->setCellValue('E' . $baris, (int) $r->umur);
            $sh->setCellValue('F' . $baris, $label[$st]);
            $baris++;
        }
        foreach (array('A' => 26, 'B' => 14, 'C' => 18, 'D' => 14, 'E' => 12, 'F' => 22) as $kol => $lebar) {
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

            // Baris pertama = judul kolom bila kolom B-nya bukan tanggal.
            $mulai = ($this->_tanggal(isset($mentah[0][1]) ? $mentah[0][1] : '') === null) ? 1 : 0;

            $lap = array(
                'dry' => $dry, 'total' => 0, 'diisi' => 0, 'sudah_terisi' => 0, 'tidak_ditemukan' => 0,
                'tanggal_salah' => 0, 'nomor_salah' => 0, 'duplikat' => 0, 'belum_completed' => 0,
                'contoh_tidak_ditemukan' => array(), 'contoh_salah' => array(),
            );
            $lihat = array();

            if (!$dry) $this->db->trans_start();

            for ($i = $mulai; $i < count($mentah); $i++) {
                $a = isset($mentah[$i][0]) ? $mentah[$i][0] : '';
                $b = isset($mentah[$i][1]) ? $mentah[$i][1] : '';
                if (trim((string) $a) === '' && trim((string) $b) === '') continue;
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
                if ($tgl === null) {
                    $lap['tanggal_salah']++;
                    $this->_contoh($lap['contoh_salah'], 'Baris ' . $baris_ke . ': tanggal pencairan tidak terbaca (' . mb_substr((string) $b, 0, 30) . ')');
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
                if ($resi->tanggal_pencairan !== null) {
                    $lap['sudah_terisi']++;
                    continue;
                }
                if ($resi->status_pesanan !== 'COMPLETED') $lap['belum_completed']++;
                if ($dry || $this->pencairan_dana_fcd->isi_pencairan($resi->id_printresi, $tgl)) {
                    $lap['diisi']++;
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

    /** Awalan yang berlaku untuk perhitungan angka ([] pilihan = semua marketplace). */
    private function _awalan_dipilih($mps)
    {
        return empty($mps) ? array_keys(Pencairan_dana_fcd::peta_mp()) : $mps;
    }

    private function _tab()
    {
        $tab = $this->input->post_get('tab');
        return in_array($tab, array('semua', 'belum', 'sudah'), true) ? $tab : 'belum';
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
                'selesai' => substr((string) $r->tgl_selesai, 0, 10),
                'cair'    => $r->tgl_cair ? substr((string) $r->tgl_cair, 0, 10) : null,
                'umur'    => $umur,
                'status'  => $r->sudah ? 'sudah' : ($umur > Pencairan_dana_fcd::BATAS_KUNING ? 'merah' : 'kuning'),
            );
        }
        return $hasil;
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
