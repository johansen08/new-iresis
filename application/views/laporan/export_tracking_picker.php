<table border="1">
  <tr><td colspan="8" style="font-weight:bold; font-size:14pt">Tracking Picker</td></tr>
  <tr><td colspan="8">Tanggal: <?= $tanggal ?></td></tr>
  <tr></tr>

  <tr><td colspan="8" style="font-weight:bold; font-size:12pt">Rekap Per Picker</td></tr>
  <tr style="font-weight:bold; background-color:#4472C4; color:#fff">
    <td>No</td>
    <td>Nama Picker</td>
    <td>Total Resi</td>
    <td>Satuan</td>
    <td>Campuran</td>
    <td>Lantai Dikunjungi</td>
    <td>Pindah Lantai</td>
  </tr>
  <?php $i = 1; foreach ($summary as $row): ?>
  <tr>
    <td><?= $i++ ?></td>
    <td><?= $row['nama_pegawai'] ?></td>
    <td><?= $row['total_resi'] ?></td>
    <td><?= $row['resi_satuan'] ?></td>
    <td><?= $row['resi_campuran'] ?></td>
    <td><?= $row['lantai_dikunjungi'] ?></td>
    <td><?= $row['jumlah_pindah_lantai'] ?></td>
  </tr>
  <?php endforeach; ?>

  <tr></tr>
  <tr></tr>

  <tr><td colspan="8" style="font-weight:bold; font-size:12pt">Detail Urutan Resi Per Picker</td></tr>
  <tr style="font-weight:bold; background-color:#4472C4; color:#fff">
    <td>No</td>
    <td>Nama Picker</td>
    <td>Jam</td>
    <td>No Resi</td>
    <td>Tipe</td>
    <td>Jumlah SKU</td>
    <td>SKU</td>
    <td>Lantai</td>
    <td>Keterangan Perpindahan</td>
  </tr>
  <?php $i = 1; foreach ($detail as $d): ?>
  <tr>
    <td><?= $i++ ?></td>
    <td><?= $d['nama_pegawai'] ?></td>
    <td><?= $d['jam'] ?></td>
    <td><?= $d['noresi'] ?></td>
    <td><?= ucfirst($d['tipe_resi']) ?></td>
    <td><?= $d['jumlah_sku'] ?></td>
    <td><?= $d['sku_list'] ?></td>
    <td><?= $d['lantai_text'] ?></td>
    <td><?= $d['keterangan'] ?></td>
  </tr>
  <?php endforeach; ?>
</table>
