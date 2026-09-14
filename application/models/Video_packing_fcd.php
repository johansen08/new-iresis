<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Penyimpanan rekaman video packing.
 *
 * Satu baris tblvideopacking = satu sesi rekam (satu resi, satu kali packing).
 * Berkasnya sendiri ditulis bertahap oleh Packer::upload_video_packing karena
 * browser mengirim rekaman per potongan; model ini cuma mengurus metadatanya.
 */
class Video_packing_fcd extends CI_Model
{
    /**
     * Folder root tempat semua rekaman disimpan, kalau secrets.php tidak
     * menentukan lain (kunci 'video_packing_dir').
     *
     * Sengaja DI LUAR document root: berkasnya besar (±3 MB/menit) dan tidak
     * boleh ikut disalin/di-backup bersama kode, dan supaya tidak bisa diunduh
     * siapa pun yang tahu nomor resinya -- videonya hanya bisa diputar lewat
     * Cs::putar_video_packing() yang memeriksa login. Konsekuensinya Apache
     * tidak bisa menyajikan berkasnya langsung; lihat url_video().
     */
    const ROOT_UPLOAD_DEFAULT = 'C:/video-packing/';

    /**
     * Folder root yang berlaku, selalu berakhiran '/'.
     *
     * Dibaca dari secrets.php supaya folder dev dan produksi di PC yang sama
     * tidak saling menimpa (folder dev diarahkan ke tempat lain).
     */
    public function root_upload()
    {
        if (!function_exists('iresis_secret')) {
            require_once APPPATH . 'config/secrets_load.php';
        }

        $root = (string) iresis_secret('video_packing_dir', self::ROOT_UPLOAD_DEFAULT);
        $root = str_replace('\\', '/', trim($root));

        if ($root === '') {
            $root = self::ROOT_UPLOAD_DEFAULT;
        }

        return rtrim($root, '/') . '/';
    }

    /**
     * Path absolut folder penyimpanan, dibuat kalau belum ada.
     *
     * Rekaman baru semuanya ditaruh langsung di folder root ($folder kosong):
     * satu resi satu berkas bernama nomor resinya, jadi tidak perlu dipisah per
     * tanggal lagi. Baris lama yang folder-nya masih berisi tanggal tetap
     * dilayani apa adanya supaya videonya tidak hilang.
     */
    public function folder_path($folder)
    {
        $path = $this->root_upload() . ($folder === '' || $folder === NULL ? '' : $folder . '/');

        if (!is_dir($path)) {
            mkdir($path, 0777, TRUE);
        }

        return $path;
    }

    /**
     * URL untuk memutar satu rekaman, dipakai sebagai src tag <video>.
     *
     * Bukan URL berkas langsung -- foldernya di luar document root -- melainkan
     * endpoint yang mengalirkan isinya (Cs::putar_video_packing). Kuncinya id
     * baris, bukan nama berkas, supaya path apa pun tidak pernah lewat URL.
     */
    public function url_video($row)
    {
        return base_url('cs/putar-video-packing/' . (int) $row->id_videopacking);
    }

    /** Path absolut berkas satu baris rekaman. */
    public function path_berkas($row)
    {
        return $this->root_upload() . $this->sub_path($row);
    }

    public function get_by_id($id)
    {
        return $this->db->get_where('tblvideopacking', ['id_videopacking' => (int) $id])->row();
    }

    private function sub_path($row)
    {
        $folder = isset($row->folder) ? (string) $row->folder : '';

        return ($folder === '' ? '' : $folder . '/') . $row->nama_file;
    }

    /**
     * Nama berkas untuk sebuah resi.
     *
     * Nomor resi ikut jadi nama berkas, jadi harus dikunci sekeras kode sesi
     * dulu: apa pun di luar huruf, angka, titik, strip, dan garis bawah diganti
     * supaya tidak bisa dipakai keluar dari folder upload.
     *
     * Bagian 1 memakai nomor resinya apa adanya. Bagian 2 dan seterusnya diberi
     * akhiran -2, -3, dan seterusnya: namanya sengaja mirip supaya berurutan
     * bersebelahan waktu folder diurutkan, tapi tetap berkas terpisah karena
     * rekaman lanjutan setelah tab mati memang tidak bisa disatukan.
     *
     * @return string Nama berkas, atau '' kalau resinya tidak menyisakan nama
     *                yang aman sama sekali.
     */
    public function nama_file_untuk($noresi, $bagian = 1)
    {
        $bersih = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $noresi);
        $bersih = trim($bersih, '.');
        $bersih = substr($bersih, 0, 100);

        if ($bersih === '' || $bersih === '_') {
            return '';
        }

        $bagian = (int) $bagian;
        if ($bagian > 1) {
            $bersih .= '-' . $bagian;
        }

        return $bersih . '.webm';
    }

    /**
     * Nomor bagian untuk rekaman berikutnya dari sebuah resi.
     *
     * Nisan DIBATALKAN tidak dihitung: berkasnya sudah dibuang, jadi nomornya
     * boleh dipakai ulang dan resi yang batal lalu discan lagi tetap mulai dari
     * bagian 1.
     */
    public function bagian_berikutnya($noresi)
    {
        $row = $this->db
            ->select('MAX(bagian) AS terakhir')
            ->where('noresi', $noresi)
            ->where('status !=', 'DIBATALKAN')
            ->get('tblvideopacking')
            ->row();

        return ($row && $row->terakhir) ? ((int) $row->terakhir + 1) : 1;
    }

    /**
     * Apakah nama berkas ini sudah dipakai resi LAIN.
     *
     * Akhiran bagian memakai karakter yang juga bisa muncul di nomor resi, jadi
     * resi bernama "X-2" menghasilkan nama yang sama dengan bagian 2 milik resi
     * "X". Jarang terjadi, tapi akibatnya video satu resi menimpa video resi
     * lain tanpa jejak -- jadi dicegat, bukan diharapkan tidak terjadi.
     */
    public function nama_dipakai_resi_lain($noresi, $nama_file)
    {
        return (bool) $this->db
            ->where('nama_file', $nama_file)
            ->where('noresi !=', $noresi)
            ->where('status !=', 'DIBATALKAN')
            ->get('tblvideopacking')
            ->row();
    }

    public function get_by_kode_sesi($kode_sesi)
    {
        return $this->db->get_where('tblvideopacking', ['kode_sesi' => $kode_sesi])->row();
    }

    /** Dipanggil saat potongan pertama masuk. */
    public function mulai_sesi($data)
    {
        $this->db->insert('tblvideopacking', $data);
        return $this->db->insert_id();
    }

    public function update_sesi($kode_sesi, $data)
    {
        $this->db->where('kode_sesi', $kode_sesi)->update('tblvideopacking', $data);
        return $this->db->affected_rows();
    }

    /**
     * Rekaman untuk satu resi, diurutkan menurut nomor bagian.
     *
     * Normalnya cuma satu baris. Kalau tab packer sempat mati di tengah packing,
     * isinya beberapa bagian yang harus ditonton berurutan -- karena itu urutannya
     * menaik, bukan terbaru dulu.
     */
    public function get_by_resi($noresi)
    {
        $this->db->select('v.*, u.name AS nama_packer');
        $this->db->from('tblvideopacking v');
        $this->db->join('tbluser u', 'u.id_user = v.id_user', 'left');
        $this->db->where('v.noresi', $noresi);
        // Sesi yang dibatalkan hanya tersisa sebagai nisan tanpa berkas; jangan
        // ikut ditawarkan ke CS.
        $this->db->where('v.status !=', 'DIBATALKAN');
        $this->db->order_by('v.bagian', 'ASC');
        $this->db->order_by('v.mulai_at', 'ASC');

        return $this->db->get()->result();
    }
}
