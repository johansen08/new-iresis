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
    /** Folder root (relatif ke document root) tempat semua rekaman disimpan. */
    const ROOT_UPLOAD = 'assets/uploads/video_packing/';

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
        $path = FCPATH . self::ROOT_UPLOAD . ($folder === '' || $folder === NULL ? '' : $folder . '/');

        if (!is_dir($path)) {
            mkdir($path, 0777, TRUE);
        }

        return $path;
    }

    /** URL publik satu rekaman, dipakai langsung sebagai src tag <video>. */
    public function url_video($row)
    {
        return base_url(self::ROOT_UPLOAD . $this->sub_path($row));
    }

    /** Path absolut berkas satu baris rekaman. */
    public function path_berkas($row)
    {
        return FCPATH . self::ROOT_UPLOAD . $this->sub_path($row);
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
