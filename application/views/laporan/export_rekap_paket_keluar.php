<table border="1">
  <tr><td colspan="4" style="font-weight:bold; font-size:14pt"><?= $title ?></td></tr>
  <tr><td colspan="4">Periode: <?= $start_date ?> s/d <?= $end_date ?></td></tr>
  <tr></tr>
  <tr style="font-weight:bold; background-color:#4472C4; color:#fff">
    <td>No</td>
    <td>Ekspedisi</td>
    <td>Tanggal</td>
    <td>Jumlah Paket</td>
  </tr>
  <?php $i = 1; foreach ($rows as $row): ?>
  <tr>
    <td><?= $i++ ?></td>
    <td><?= $row->nama_kurir ?></td>
    <td><?= $row->tanggal ?></td>
    <td><?= $row->jumlah_paket ?></td>
  </tr>
  <?php endforeach; ?>
</table>
