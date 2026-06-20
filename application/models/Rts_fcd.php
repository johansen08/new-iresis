<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rts_fcd extends CI_Model
{

    function check_rts($trip = null)
    {
        $tanggal = date('Y-m-d');

        $sql = "
            SELECT
                pr.id_printresi,
                pr.noresi,
                k.nama_kurir,
                pr.tanggal_printresi,
                pr.trip
            FROM tblprintresi pr
            LEFT JOIN tblkurir k ON k.id_kurir = pr.id_kurir
            WHERE DATE(pr.tanggal_printresi) = ?
              AND (pr.batal IS NULL OR pr.batal = '' OR pr.batal = '0')
              AND pr.status_rts = 1
        ";

        $params = [$tanggal];

        if ($trip) {
            $sql .= " AND pr.trip = ?";
            $params[] = $trip;
        }

        $sql .= " ORDER BY k.nama_kurir, pr.noresi";

        return $this->db->query($sql, $params);
    }

    function mark_checked($ids)
    {
        if (empty($ids)) return 0;

        $this->db->where_in('id_printresi', $ids);
        $this->db->update('tblprintresi', [
            'rts_checked_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows();
    }

    function log_rts_cycle($trip, $jumlah_rts, $detail_resi = [])
    {
        $this->db->insert('tb_rts_log', [
            'trip'       => $trip,
            'siklus_jam' => date('Y-m-d H:i:s'),
            'jumlah_rts' => $jumlah_rts,
            'detail'     => json_encode($detail_resi),
        ]);

        return $this->db->insert_id();
    }

    function get_rts_log($tanggal = null)
    {
        if (!$tanggal) $tanggal = date('Y-m-d');

        $sql = "
            SELECT * FROM tb_rts_log
            WHERE DATE(created_at) = ?
            ORDER BY created_at DESC
        ";

        return $this->db->query($sql, [$tanggal]);
    }

    function format_wa_rts($trip = null)
    {
        $tanggal = date('Y-m-d');
        $data = $this->check_rts($trip)->result();

        if (empty($data)) {
            return "*LAPORAN RTS*\n" .
                   "Tanggal: " . date('d-m-Y') . "\n\n" .
                   "Tidak ada paket RTS terdeteksi. ✅";
        }

        $msg = "*⚠️ LAPORAN RTS (Return to Sender)*\n";
        $msg .= "Tanggal: " . date('d-m-Y') . "\n";
        if ($trip) $msg .= "Trip: " . $trip . "\n";
        $msg .= "─────────────────\n";

        $by_kurir = [];
        foreach ($data as $row) {
            $kurir = $row->nama_kurir ?: 'Lainnya';
            $by_kurir[$kurir][] = $row->noresi;
        }

        foreach ($by_kurir as $kurir => $resi_list) {
            $msg .= "\n*{$kurir}* (" . count($resi_list) . " resi):\n";
            foreach ($resi_list as $noresi) {
                $msg .= "  - {$noresi}\n";
            }
        }

        $msg .= "\n─────────────────\n";
        $msg .= "*TOTAL: " . count($data) . " resi RTS*";

        return $msg;
    }
}
