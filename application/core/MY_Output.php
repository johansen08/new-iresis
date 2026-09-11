<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pengaman global untuk respons AJAX/JSON.
 *
 * MASALAH ASLINYA
 * ---------------
 * Hampir semua endpoint di aplikasi ini mengirim data dengan `echo json_encode(...)`
 * langsung dari controller, tanpa pernah menyentuh CI_Output. Akibatnya
 * CI_Output::$final_output tetap NULL sampai akhir request. Di penghujung request
 * CodeIgniter memanggil CI_Output::_display(), yang menjalankan:
 *
 *     $output = str_replace(array('{elapsed_time}', '{memory_usage}'), ..., $output);
 *
 * dengan $output beralias ke $final_output yang NULL. Sejak PHP 8.1 itu memicu
 * "Deprecated: str_replace(): Passing null to parameter #3".
 *
 * Karena index.php menyetel ENVIRONMENT 'development' (display_errors menyala),
 * CodeIgniter mencetak kotak HTML error tersebut ke body respons -- SESUDAH JSON
 * yang sudah terlanjur ter-echo. Body jadi:
 *
 *     {"view":"..."}<div style="border:1px solid #990000">A PHP Error ...</div>
 *
 * yang bukan JSON valid. Di sisi klien, $.ajax dengan dataType 'JSON' gagal parse,
 * masuk ke callback error, dan plugins.js dulu menaruh body mentah itu ke
 * .page-content-wrap -- halaman jadi menampilkan teks {"view":"... <\/div>\r\n ...
 *
 * SOLUSINYA
 * ---------
 * Cukup pastikan $final_output berupa string sejak awal, bukan NULL. str_replace()
 * pada string kosong aman, tidak ada Deprecated, tidak ada HTML nyasar di belakang
 * JSON. append_output() memakai operator .= sehingga tetap kompatibel, dan
 * set_output() tetap menimpa nilainya seperti biasa.
 *
 * Ini menutup seluruh endpoint sekaligus, termasuk controller yang tidak turun dari
 * MY_Controller (mis. Cron, Login).
 */
class MY_Output extends CI_Output
{
    public $final_output = '';
}
