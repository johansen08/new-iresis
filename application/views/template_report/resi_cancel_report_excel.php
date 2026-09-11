<style>
    .header-red { background-color: #d9534f; color: #ffffff; font-weight: bold; text-align: center; }
    .header-light { background-color: #f1f4f9; font-weight: bold; }
    .text-center { text-align: center; }
    .title { font-size: 16px; font-weight: bold; }
</style>

<table border="0">
    <tr>
        <td colspan="8" class="title">LAPORAN RESI CANCEL</td>
    </tr>
    <tr>
        <td colspan="8">Periode: <?= $reportrange ?></td>
    </tr>
</table>

<br>

<table border="1">
  <thead>
    <tr class="header-red">
      <th width="50">#</th>
      <th width="150">Marketplace</th>
      <th width="150">Tgl Print Resi</th>
      <th width="180">No Resi</th>
      <th width="120">Kurir</th>
      <th width="250">Alasan Batal / Status</th>
      <th width="150">Picker</th>
      <th width="150">Packer</th>
      <th width="150">Scan By HO</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!empty($list_data)): ?>
      <?php foreach ($list_data as $row): ?>
        <tr>
          <td class="text-center"><?= $row[0] ?></td>
          <td class="text-center"><?= $row[1] ?></td>
          <td class="text-center"><?= $row[2] ?></td>
          <td><?= $row[3] ?></td>
          <td class="text-center"><?= $row[4] ?></td>
          <td><?= $row[5] ?></td>
          <td><?= $row[6] ?></td>
          <td><?= $row[7] ?></td>
          <td><?= $row[8] ?></td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr>
        <td colspan="9" class="text-center">Tidak ada data.</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>
