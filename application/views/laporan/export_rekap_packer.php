<table border="1">
  <tr><td colspan="6" style="font-weight:bold; font-size:14pt"><?= $title ?></td></tr>
  <tr><td colspan="6">Periode: <?= $start_date ?> s/d <?= $end_date ?></td></tr>
  <tr></tr>
  <tr style="font-weight:bold; background-color:#4472C4; color:#fff">
    <td>No</td>
    <td>Nama Packer</td>
    <td>Tanggal</td>
    <td>Total Resi</td>
    <td>Satuan</td>
    <td>Campuran</td>
  </tr>
  <?php $i = 1; foreach ($rows as $row): ?>
  <tr>
    <td><?= $i++ ?></td>
    <td><?= $row->nama_packer ?></td>
    <td><?= $row->tanggal ?></td>
    <td><?= $row->total_resi ?></td>
    <td><?= $row->satuan ?></td>
    <td><?= $row->campuran ?></td>
  </tr>
  <?php endforeach; ?>
</table>
