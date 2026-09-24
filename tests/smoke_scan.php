<?php
/**
 * Smoke test endpoint scan iResis (backlog B-16).
 *
 * Memanggil endpoint scan utama di iresis-dev lewat HTTP sungguhan (Apache,
 * session, cookie -- sama persis dengan browser), lalu memeriksa bahwa tiap
 * balasan adalah JSON utuh tanpa satu byte pun di luarnya, dengan kode dan
 * EXCEPTION_CODE yang diharapkan. Sasarannya kasus "satu byte keluaran
 * nyasar" (CLAUDE.md, bagian SPA semu): PHP Warning, spasi sebelum tag PHP,
 * atau halaman error CI yang ikut tercetak dan membuat jQuery gagal parse.
 *
 * Pakai, dari root folder dev:
 *   C:/xampp/php/php.exe tests/smoke_scan.php              putaran penuh
 *   C:/xampp/php/php.exe tests/smoke_scan.php --baca-saja  tanpa menulis data
 *
 * Putaran penuh menghabiskan dua resi dev per jalan: satu resi normal dibawa
 * dari picker sampai Terima Retur, satu resi CANCELED ditolak di tiap meja.
 * Resi yang sudah terpakai tidak lolos query pencari lagi, jadi jalan
 * berikutnya otomatis memakai resi lain. Semua tulisan memakai nama komputer
 * UJI-SMOKE, sehingga barisnya bisa dikenali di iresis_dev.
 *
 * Mode --baca-saja hanya memakai resi karangan dan resi yang sudah selesai
 * sampai HO, sehingga semua scan ditolak dan tidak ada data yang tertulis.
 * Satu-satunya tulisan adalah login (tbluser.lastlogin dan log status performa).
 *
 * Butuh di application/config/secrets.php folder dev:
 *   'uji_username' => '...',  akun webmaster (role 1) yang aktif di iresis_dev
 *   'uji_password' => '...',
 *   'uji_base_url' => 'http://localhost/iresis-dev/',  opsional, ini bawaannya
 *
 * Penjaga: hanya jalan lewat CLI, hanya kalau secrets.php menunjuk database
 * iresis_dev, URL-nya mengandung /iresis-dev/, dan pusher_aktif FALSE. File
 * ini ikut ter-pull ke folder produksi, dan di sana penjaga itulah yang
 * menolaknya.
 *
 * Kode keluar: 0 semua lulus, 1 ada yang gagal, 2 tidak bisa mulai.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// secrets.php diawali "defined('BASEPATH') OR exit", jadi konstanta ini wajib ada.
define('BASEPATH', dirname(__DIR__) . '/system/');

const NAMA_PK         = 'UJI-SMOKE';
const STATUS_PERFORMA = 'NORMAL_PICKER';
const RESI_KARANGAN   = 'UJI-SMOKE-TIDAK-ADA';
const BATAS_LAMBAT_MS = 3000;

// Tanda halaman error PHP/CI yang ikut masuk ke string view.
const TANDA_ERROR = [
    'A PHP Error was encountered',
    'A Database Error Occurred',
    'An uncaught Exception was encountered',
    'Severity:',
    'Fatal error',
];

$baca_saja = in_array('--baca-saja', $argv, true);

function berhenti($pesan)
{
    fwrite(STDERR, "TIDAK BISA MULAI: $pesan\n");
    exit(2);
}

// --- Konfigurasi dan penjaga ---------------------------------------------

$file_secrets = dirname(__DIR__) . '/application/config/secrets.php';
if (!is_file($file_secrets)) {
    berhenti('application/config/secrets.php tidak ada.');
}
$secrets = require $file_secrets;

if (($secrets['db_database'] ?? '') !== 'iresis_dev') {
    berhenti("secrets.php menunjuk database '" . ($secrets['db_database'] ?? '') . "'. Smoke test hanya boleh jalan di folder dev (iresis_dev).");
}
if (($secrets['pusher_aktif'] ?? TRUE) !== FALSE) {
    berhenti("pusher_aktif belum FALSE. Tanpa itu, scan uji memunculkan notifikasi di browser produksi (docs/LINGKUNGAN_DEV.md §3).");
}
if (!array_key_exists('uji_username', $secrets) || !array_key_exists('uji_password', $secrets)) {
    berhenti("isi 'uji_username' dan 'uji_password' di secrets.php dengan akun webmaster iresis_dev (lihat secrets.php.example).");
}

$base_url = rtrim($secrets['uji_base_url'] ?? 'http://localhost/iresis-dev/', '/') . '/';
if (strpos($base_url, '/iresis-dev/') === FALSE) {
    berhenti("uji_base_url '$base_url' bukan URL folder dev (/iresis-dev/).");
}

mysqli_report(MYSQLI_REPORT_OFF);
$db = @new mysqli($secrets['db_hostname'] ?? '127.0.0.1', $secrets['db_username'] ?? '', $secrets['db_password'] ?? '', 'iresis_dev');
if ($db->connect_errno) {
    berhenti('koneksi ke iresis_dev gagal: ' . $db->connect_error);
}
$db->set_charset('utf8mb4');
// Tanpa ini MariaDB men-materialize subquery EXISTS dengan memindai penuh
// tbldetailprintresi dan tblresiambilbarang: query pencari resi butuh ±40
// detik. Dengan evaluasi per baris lewat indeks, cukup beberapa milidetik.
$db->query("SET SESSION optimizer_switch = 'semijoin=off,materialization=off'");

function satu_baris(mysqli $db, $sql, array $param = [])
{
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        berhenti('query gagal disiapkan: ' . $db->error);
    }
    if ($param) {
        $stmt->bind_param(str_repeat('s', count($param)), ...$param);
    }
    $stmt->execute();
    $hasil = $stmt->get_result();
    return $hasil ? $hasil->fetch_assoc() : NULL;
}

$akun = satu_baris($db, 'SELECT id_user, hakakses, isactive FROM tbluser WHERE username = ?', [$secrets['uji_username']]);
if (!$akun || (int) $akun['isactive'] !== 1) {
    berhenti("akun '{$secrets['uji_username']}' tidak ada atau tidak aktif di iresis_dev.");
}
if ((int) $akun['hakakses'] !== 1) {
    berhenti("akun '{$secrets['uji_username']}' bukan webmaster (role 1). Halaman Scan Resi Packer (Webcam) dan Laporan Lost Scan Picker hanya terbuka untuk role tertentu.");
}

// Dua picker berbeda: Update Picker yang mengganti ke nama yang sama di detik
// yang sama dengan scan picker-nya tidak mengubah satu kolom pun, sehingga
// affected_rows 0 dan balasannya 200 "Tidak ada data yang disimpan".
$sql_picker = "SELECT n.id_pegawai FROM tblnamaambilbarang n
     JOIN tblpegawai p ON p.kode_pegawai = n.id_pegawai
     WHERE p.status_aktif = 'AKTIF' ORDER BY n.id_pegawai LIMIT 1";
$picker = satu_baris($db, $sql_picker);
$picker_lain = satu_baris($db, $sql_picker . ' OFFSET 1');
if (!$picker || !$picker_lain) {
    berhenti('butuh minimal dua picker AKTIF di Master Picker iresis_dev.');
}
$id_picker = $picker['id_pegawai'];
$id_picker_lain = $picker_lain['id_pegawai'];

// Batas 14 hari dihitung dari resi terbaru di dev, bukan dari hari ini:
// data dev adalah salinan yang makin basi.
$batas = satu_baris($db, 'SELECT MAX(tanggal_printresi) - INTERVAL 14 DAY AS batas FROM tblprintresi')['batas'];

$saring_aktif = "COALESCE(pr.status_pesanan, '') NOT LIKE '%CANCEL%'
      AND COALESCE(pr.status_pesanan, '') <> 'COMPLETED'
      AND COALESCE(pr.batal, '') <> '1'";

// D: sudah di-picker, di-packing, dan di-HO. Dipakai untuk uji dobel tanpa menulis.
$resi_d = satu_baris($db,
    "SELECT pr.noresi FROM tblprintresi pr
     WHERE pr.tanggal_printresi >= ? AND $saring_aktif
       AND EXISTS (SELECT 1 FROM tblresiambilbarang a WHERE a.id_resi = pr.id_printresi)
       AND EXISTS (SELECT 1 FROM tblpacking k        WHERE k.id_resi = pr.id_printresi)
       AND EXISTS (SELECT 1 FROM tblresikeluar h     WHERE h.id_resi = pr.id_printresi)
     ORDER BY pr.id_printresi DESC LIMIT 1", [$batas]);

$resi_n = $resi_c = NULL;
if (!$baca_saja) {
    // N: normal, belum tersentuh meja mana pun, bukan kurir Shopee (id 7 ditolak mode NDD).
    $resi_n = satu_baris($db,
        "SELECT pr.noresi, pr.id_printresi FROM tblprintresi pr
         WHERE pr.tanggal_printresi >= ? AND pr.id_kurir <> 7 AND $saring_aktif
           AND EXISTS     (SELECT 1 FROM tbldetailprintresi d WHERE d.id_resi = pr.id_printresi)
           AND NOT EXISTS (SELECT 1 FROM tblresiambilbarang a WHERE a.id_resi = pr.id_printresi)
           AND NOT EXISTS (SELECT 1 FROM tblpacking k        WHERE k.id_resi = pr.id_printresi)
           AND NOT EXISTS (SELECT 1 FROM tblresikeluar h     WHERE h.id_resi = pr.id_printresi)
           AND NOT EXISTS (SELECT 1 FROM tblscan_ndd s       WHERE s.id_resi = pr.id_printresi)
           AND NOT EXISTS (SELECT 1 FROM tblresiretur r      WHERE r.id_resi = pr.id_printresi)
         ORDER BY pr.id_printresi DESC LIMIT 1", [$batas]);

    // C: CANCELED, belum di-picker, belum pernah ditolak di meja mana pun.
    $resi_c = satu_baris($db,
        "SELECT pr.noresi, pr.id_printresi FROM tblprintresi pr
         WHERE pr.tanggal_printresi >= ? AND pr.status_pesanan = 'CANCELED' AND pr.id_kurir <> 7
           AND NOT EXISTS (SELECT 1 FROM tblresiambilbarang a    WHERE a.id_resi = pr.id_printresi)
           AND NOT EXISTS (SELECT 1 FROM tblcancel_paket_tolak t WHERE t.id_resi = pr.id_printresi)
           AND NOT EXISTS (SELECT 1 FROM tblresiretur r          WHERE r.id_resi = pr.id_printresi)
         ORDER BY pr.id_printresi DESC LIMIT 1", [$batas]);

    if (!$resi_n || !$resi_c) {
        berhenti('resi uji normal/CANCELED tidak ditemukan. Data dev sudah terlalu basi: segarkan (docs/LINGKUNGAN_DEV.md §5), atau jalankan dengan --baca-saja.');
    }
}

// --- HTTP ----------------------------------------------------------------

$cookie_jar = tempnam(sys_get_temp_dir(), 'iresis_smoke_');
register_shutdown_function(function () use ($cookie_jar) {
    @unlink($cookie_jar);
});

/**
 * Satu request ke aplikasi dev. Redirect sengaja tidak diikuti: redirect ke
 * halaman login adalah tanda sesi hilang, dan itu harus terlihat sebagai gagal.
 */
function minta($metode, $path, array $data = [])
{
    global $base_url, $cookie_jar;

    $ch = curl_init($base_url . ltrim($path, '/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_HEADER         => TRUE,
        CURLOPT_FOLLOWLOCATION => FALSE,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_COOKIEJAR      => $cookie_jar,
        CURLOPT_COOKIEFILE     => $cookie_jar,
        // jQuery mengirim header ini; MY_Controller memakainya untuk memilih
        // balasan JSON 401 alih-alih redirect saat sesi habis.
        CURLOPT_HTTPHEADER     => ['X-Requested-With: XMLHttpRequest'],
    ]);
    if ($metode === 'POST') {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $mulai = microtime(true);
    $mentah = curl_exec($ch);
    $ms = (int) round((microtime(true) - $mulai) * 1000);

    if ($mentah === FALSE) {
        $galat = curl_error($ch);
        curl_close($ch);
        return ['status' => 0, 'body' => '', 'lokasi' => '', 'ms' => $ms, 'galat' => $galat];
    }

    $ukuran_header = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    $header = substr($mentah, 0, $ukuran_header);
    $lokasi = preg_match('/^Location:\s*(\S+)/mi', $header, $m) ? $m[1] : '';

    return ['status' => $status, 'body' => (string) substr($mentah, $ukuran_header), 'lokasi' => $lokasi, 'ms' => $ms, 'galat' => ''];
}

// --- Pemeriksaan ---------------------------------------------------------

$jumlah = ['lulus' => 0, 'gagal' => 0, 'lewat' => 0];

function lapor($status, $judul, $ms = NULL, $rincian = '')
{
    global $jumlah;

    $jumlah[strtolower($status)]++;
    $waktu = $ms === NULL ? '' : sprintf(' (%d ms%s)', $ms, $ms > BATAS_LAMBAT_MS ? ', LAMBAT' : '');
    echo str_pad("[$status]", 8) . $judul . $waktu . "\n";
    if ($rincian !== '') {
        echo '         ' . str_replace("\n", "\n         ", $rincian) . "\n";
    }
}

function cuplik($teks, $panjang = 300)
{
    $teks = (string) $teks;
    return strlen($teks) > $panjang ? substr($teks, 0, $panjang) . '... (dipotong)' : $teks;
}

/**
 * Balasan wajib JSON utuh: HTTP 200 dan byte pertama/terakhirnya kurung
 * kurawal. json_decode sendiri memaafkan spasi di tepi, jadi pemeriksaan
 * tepi dilakukan terpisah -- byte di luar JSON adalah gejala yang dicari.
 *
 * @return array [array|null $json, string|null $alasan_gagal]
 */
function urai_json(array $r)
{
    if ($r['status'] === 0) {
        return [NULL, 'request gagal: ' . $r['galat']];
    }
    if ($r['status'] !== 200) {
        $tambahan = $r['lokasi'] !== '' ? " -> {$r['lokasi']}" : '';
        return [NULL, "HTTP {$r['status']}$tambahan, seharusnya 200.\nIsi: " . cuplik($r['body'])];
    }

    $body = $r['body'];
    if ($body === '') {
        return [NULL, 'balasan kosong.'];
    }

    $awal = strpos($body, '{');
    $akhir = strrpos($body, '}');
    if ($awal !== 0 || $akhir !== strlen($body) - 1) {
        $luar = ($awal === FALSE ? strlen($body) : $awal) + ($akhir === FALSE ? 0 : strlen($body) - 1 - $akhir);
        return [NULL, "ada $luar byte di luar JSON.\nIsi: " . cuplik($body)];
    }

    $json = json_decode($body, TRUE);
    if (!is_array($json)) {
        return [NULL, 'bukan JSON valid (' . json_last_error_msg() . ").\nIsi: " . cuplik($body)];
    }

    return [$json, NULL];
}

/**
 * Endpoint AJAX: cek kode di body, EXCEPTION_CODE bila diminta, lalu
 * pemeriksaan tambahan yang mengembalikan pesan gagal atau NULL.
 */
function uji_endpoint($judul, array $r, $kode, $exception = NULL, ?callable $tambahan = NULL)
{
    list($json, $alasan) = urai_json($r);

    if ($alasan === NULL && ($json['code'] ?? NULL) !== $kode) {
        $alasan = 'code ' . var_export($json['code'] ?? NULL, TRUE) . ", seharusnya $kode. Pesan: " . ($json['message'] ?? '-');
    }
    if ($alasan === NULL && $exception !== NULL && ($json['data']['EXCEPTION_CODE'] ?? NULL) !== $exception) {
        $alasan = 'EXCEPTION_CODE ' . var_export($json['data']['EXCEPTION_CODE'] ?? NULL, TRUE) . ", seharusnya $exception. Pesan: " . ($json['message'] ?? '-');
    }
    if ($alasan === NULL && $tambahan) {
        $alasan = $tambahan($json);
    }

    lapor($alasan === NULL ? 'LULUS' : 'GAGAL', $judul, $r['ms'], (string) $alasan);
    return $json;
}

/**
 * Halaman menu (dimuat plugins.js): JSON {view} yang tidak kosong dan tidak
 * menyimpan halaman error PHP/CI di dalam HTML-nya.
 */
function uji_halaman($path)
{
    $r = minta('GET', $path);
    list($json, $alasan) = urai_json($r);

    if ($alasan === NULL && (!isset($json['view']) || !is_string($json['view']) || trim($json['view']) === '')) {
        $alasan = "tidak ada 'view' di balasan. Pesan: " . ($json['message'] ?? '-');
    }
    if ($alasan === NULL) {
        foreach (TANDA_ERROR as $tanda) {
            $posisi = strpos($json['view'], $tanda);
            if ($posisi !== FALSE) {
                // Dipotong mulai dari tandanya (bukan sebelumnya, supaya tidak jatuh
                // di tengah atribut tag) lalu spasi backtrace CI dipadatkan.
                $alasan = "view memuat \"$tanda\":\n" . cuplik(trim(preg_replace('/\s+/', ' ', strip_tags(substr($json['view'], $posisi, 1500)))));
                break;
            }
        }
    }

    lapor($alasan === NULL ? 'LULUS' : 'GAGAL', "halaman $path", $r['ms'], (string) $alasan);
}

function pesan_memuat($potongan)
{
    return function (array $json) use ($potongan) {
        return stripos($json['message'] ?? '', $potongan) !== FALSE
            ? NULL
            : "pesan tidak memuat \"$potongan\": " . ($json['message'] ?? '-');
    };
}

// --- Jalankan ------------------------------------------------------------

echo "Smoke test endpoint scan -- $base_url" . ($baca_saja ? ' (baca saja)' : '') . "\n";
echo 'Resi: ' . implode(', ', array_filter([
    $resi_n ? "N={$resi_n['noresi']}" : NULL,
    $resi_c ? "C={$resi_c['noresi']}" : NULL,
    $resi_d ? "D={$resi_d['noresi']}" : NULL,
    'X=' . RESI_KARANGAN,
])) . "\n\n";

echo "== Sesi\n";
$r = minta('POST', 'picker/save-scan-picker', ['noresi' => RESI_KARANGAN]);
list($json, $alasan) = urai_json($r);
if ($alasan === NULL && ($json['status'] ?? NULL) !== 401) {
    $alasan = 'status ' . var_export($json['status'] ?? NULL, TRUE) . ', seharusnya 401.';
}
lapor($alasan === NULL ? 'LULUS' : 'GAGAL', 'tanpa login: endpoint membalas JSON 401, bukan halaman login', $r['ms'], (string) $alasan);

$r = minta('POST', 'auth', [
    'username'        => $secrets['uji_username'],
    'password'        => $secrets['uji_password'],
    'nama_pk'         => NAMA_PK,
    'status_performa' => STATUS_PERFORMA,
]);
// redirect() CI3 membalas 303 untuk POST lewat HTTP/1.1, 302 selain itu.
if (!in_array($r['status'], [302, 303], TRUE) || $r['lokasi'] === '' || preg_match('#/login/?$#', $r['lokasi'])) {
    lapor('GAGAL', 'login', $r['ms'], "HTTP {$r['status']} -> " . ($r['lokasi'] ?: '-') . '. Periksa uji_username/uji_password.');
    exit(1);
}
lapor('LULUS', 'login', $r['ms']);

echo "\n== Halaman menu\n";
foreach ([
    'picker/scan_picker',
    'picker/update_picker',
    'packer/scan_packer',
    'packer/scan_packer_webcam',
    'scan-paket-ndd-new',
    'retur/scan_retur',
    'receipt/upload_receipt',
    'lost-scan-picker',
] as $path) {
    uji_halaman($path);
}

echo "\n== Tolakan tanpa menulis data\n";
$x = RESI_KARANGAN;
uji_endpoint('picker: metode GET ditolak', minta('GET', 'picker/save-scan-picker'), 400);
uji_endpoint('picker: resi tidak ditemukan', minta('POST', 'picker/save-scan-picker', ['noresi' => $x, 'id_pegawaipicker' => $id_picker, 'status_performa' => STATUS_PERFORMA]), 400, 'NOT_FOUND');
uji_endpoint('update picker: resi tidak ditemukan', minta('POST', 'picker/save-update-picker', ['noresi' => $x, 'id_pegawaipicker' => $id_picker]), 400, 'NOT_FOUND');
uji_endpoint('packer detail: resi tidak ditemukan', minta('POST', 'packer/detail-resi', ['noresi' => $x]), 404, 'NOT_FOUND');
uji_endpoint('packer simpan: resi tidak ditemukan', minta('POST', 'packer/save-packer', ['noresi' => $x]), 400, 'NOT_FOUND');
uji_endpoint('HO: resi tidak ditemukan', minta('POST', 'scan-paket-ndd-new/save', ['noresi' => $x, 'is_ndd' => 'true']), 404, 'NOT_FOUND');
uji_endpoint('retur terima: resi tidak ditemukan', minta('POST', 'retur/save-retur', ['hasil_scan' => $x, 'is_update' => 0, 'is_complain' => 0]), 400, NULL, pesan_memuat('tidak ditemukan'));

if ($resi_d) {
    $d = $resi_d['noresi'];
    uji_endpoint('picker: dobel (resi D)', minta('POST', 'picker/save-scan-picker', ['noresi' => $d, 'id_pegawaipicker' => $id_picker, 'status_performa' => STATUS_PERFORMA]), 400, 'ALREADY_PICKED');
    uji_endpoint('packer: dobel (resi D)', minta('POST', 'packer/save-packer', ['noresi' => $d]), 400, 'ALREADY_PACKED');
    uji_endpoint('HO REGULER: dobel (resi D)', minta('POST', 'scan-paket-ndd-new/save', ['noresi' => $d, 'is_ndd' => 'false']), 400, 'ALREADY_HANDOVER');
} else {
    lapor('LEWAT', 'uji dobel: tidak ada resi yang sudah selesai sampai HO dalam 14 hari terakhir data dev');
}

if ($baca_saja) {
    echo "\n";
} else {
    $n = $resi_n['noresi'];
    $c = $resi_c['noresi'];

    echo "\n== Alur penuh resi N dan tolakan resi C (menulis ke iresis_dev)\n";
    uji_endpoint('picker: simpan N', minta('POST', 'picker/save-scan-picker', ['noresi' => $n, 'id_pegawaipicker' => $id_picker, 'status_performa' => STATUS_PERFORMA]), 201, NULL,
        function ($json) {
            // Jalur sukses ini masih echo json_encode sendiri (bukan make_ajax_response):
            // items ada di akar, bukan di data. Layar summary picker membacanya.
            return !empty($json['items']) && is_array($json['items']) ? NULL : "tidak ada 'items' di akar balasan.";
        });
    uji_endpoint('picker: dobel N', minta('POST', 'picker/save-scan-picker', ['noresi' => $n, 'id_pegawaipicker' => $id_picker, 'status_performa' => STATUS_PERFORMA]), 400, 'ALREADY_PICKED');
    uji_endpoint('picker: C dibatalkan', minta('POST', 'picker/save-scan-picker', ['noresi' => $c, 'id_pegawaipicker' => $id_picker, 'status_performa' => STATUS_PERFORMA]), 400, 'ORDER_CANCELED');
    uji_endpoint('update picker: N ke picker lain', minta('POST', 'picker/save-update-picker', ['noresi' => $n, 'id_pegawaipicker' => $id_picker_lain]), 201);

    uji_endpoint('packer detail: N', minta('POST', 'packer/detail-resi', ['noresi' => $n]), 200, NULL,
        function ($json) {
            if (empty($json['data']['items'])) {
                return "tidak ada data.items.";
            }
            return ($json['data']['nama_picker'] ?? '-') !== '-' ? NULL : 'nama_picker kosong, padahal N sudah di-picker.';
        });
    uji_endpoint('packer: simpan N', minta('POST', 'packer/save-packer', ['noresi' => $n]), 201, NULL,
        function ($json) {
            return (int) ($json['data']['total_scan'] ?? 0) >= 1 ? NULL : 'data.total_scan tidak terisi.';
        });
    uji_endpoint('packer: dobel N', minta('POST', 'packer/save-packer', ['noresi' => $n]), 400, 'ALREADY_PACKED');
    uji_endpoint('packer: C dibatalkan', minta('POST', 'packer/save-packer', ['noresi' => $c]), 400, 'ORDER_CANCELED');

    uji_endpoint('HO REGULER: simpan N', minta('POST', 'scan-paket-ndd-new/save', ['noresi' => $n, 'is_ndd' => 'false']), 201, NULL,
        function ($json) {
            return !empty($json['data']['ho_inserted']) && empty($json['data']['ndd_inserted']) ? NULL : 'seharusnya ho_inserted TRUE dan ndd_inserted FALSE: ' . json_encode($json['data'] ?? NULL);
        });
    uji_endpoint('HO REGULER: dobel N', minta('POST', 'scan-paket-ndd-new/save', ['noresi' => $n, 'is_ndd' => 'false']), 400, 'ALREADY_HANDOVER');
    uji_endpoint('HO + NDD: simpan N (sudah HO, NDD baru)', minta('POST', 'scan-paket-ndd-new/save', ['noresi' => $n, 'is_ndd' => 'true']), 201, NULL,
        function ($json) {
            return empty($json['data']['ho_inserted']) && !empty($json['data']['ndd_inserted']) ? NULL : 'seharusnya ho_inserted FALSE dan ndd_inserted TRUE: ' . json_encode($json['data'] ?? NULL);
        });
    uji_endpoint('HO + NDD: dobel N', minta('POST', 'scan-paket-ndd-new/save', ['noresi' => $n, 'is_ndd' => 'true']), 400, 'ALREADY_SCANNED');
    uji_endpoint('HO: C dibatalkan', minta('POST', 'scan-paket-ndd-new/save', ['noresi' => $c, 'is_ndd' => 'false']), 400, 'ORDER_CANCELED');

    uji_endpoint('retur terima: N', minta('POST', 'retur/save-retur', ['hasil_scan' => $n, 'is_update' => 0, 'is_complain' => 0]), 201, NULL, pesan_memuat('1 resi berhasil diproses'));
    uji_endpoint('retur terima: dobel N (kode 409)', minta('POST', 'retur/save-retur', ['hasil_scan' => $n, 'is_update' => 0, 'is_complain' => 0]), 409, NULL, pesan_memuat('sudah diinput'));
    uji_endpoint('retur terima: C dibatalkan', minta('POST', 'retur/save-retur', ['hasil_scan' => $c, 'is_update' => 0, 'is_complain' => 0]), 400, NULL, pesan_memuat('CANCEL'));

    echo "\n== Keadaan akhir di database\n";
    $akhir_n = satu_baris($db,
        "SELECT (SELECT COUNT(*) FROM tblresiambilbarang a WHERE a.id_resi = pr.id_printresi AND a.nama_komputer = ?) AS picker,
                (SELECT COUNT(*) FROM tblpacking k        WHERE k.id_resi = pr.id_printresi) AS packing,
                (SELECT COUNT(*) FROM tblresikeluar h     WHERE h.id_resi = pr.id_printresi) AS ho,
                (SELECT COUNT(*) FROM tblscan_ndd s       WHERE s.id_resi = pr.id_printresi) AS ndd,
                (SELECT GROUP_CONCAT(r.status_retur) FROM tblresiretur r WHERE r.id_resi = pr.id_printresi) AS retur
         FROM tblprintresi pr WHERE pr.noresi = ?", [NAMA_PK, $n]);
    $harapan_n = ['picker' => 1, 'packing' => 1, 'ho' => 1, 'ndd' => 1, 'retur' => 'Terima Retur'];
    $beda = [];
    foreach ($harapan_n as $kolom => $nilai) {
        if ((string) ($akhir_n[$kolom] ?? '') !== (string) $nilai) {
            $beda[] = "$kolom=" . var_export($akhir_n[$kolom] ?? NULL, TRUE) . " (seharusnya $nilai)";
        }
    }
    lapor($beda ? 'GAGAL' : 'LULUS', "resi N: picker/packing/HO/NDD/retur tercatat", NULL, implode("\n", $beda));

    // docs/PAKET_CANCEL.md §7: tiap tolakan cancel menulis satu baris jejak;
    // paket cancel baru lahir di packer (di picker barang belum diambil), lalu
    // tolakan HO menambah jumlah_tolak. Tolakan retur tidak tercatat di sini.
    $akhir_c = satu_baris($db,
        "SELECT (SELECT GROUP_CONCAT(t.tahap ORDER BY t.id_tolak) FROM tblcancel_paket_tolak t WHERE t.id_resi = ?) AS tolak,
                (SELECT CONCAT(c.status, ' x', c.jumlah_tolak) FROM tblcancel_paket c WHERE c.id_resi = ?) AS paket",
        [$resi_c['id_printresi'], $resi_c['id_printresi']]);
    $beda = [];
    if (($akhir_c['tolak'] ?? '') !== 'PICKER,PACKER,HO') {
        $beda[] = 'jejak tolakan ' . var_export($akhir_c['tolak'] ?? NULL, TRUE) . ' (seharusnya PICKER,PACKER,HO)';
    }
    if (($akhir_c['paket'] ?? '') !== 'DITEMUKAN x2') {
        $beda[] = 'paket cancel ' . var_export($akhir_c['paket'] ?? NULL, TRUE) . ' (seharusnya DITEMUKAN x2)';
    }
    lapor($beda ? 'GAGAL' : 'LULUS', 'resi C: jejak paket cancel tercatat', NULL, implode("\n", $beda));
    echo "\n";
}

echo "Hasil: {$jumlah['lulus']} lulus, {$jumlah['gagal']} gagal, {$jumlah['lewat']} dilewati.\n";
exit($jumlah['gagal'] > 0 ? 1 : 0);
