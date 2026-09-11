<style>
    .header-red { background-color: #dc2626; color: #ffffff; font-weight: bold; text-align: center; }
    .text-center { text-align: center; }
    .title { font-size: 16px; font-weight: bold; }
    .mono { mso-number-format: "\@"; }
</style>

<table border="0">
    <tr>
        <td colspan="10" class="title">DAFTAR CANCEL ORDER &mdash; TIM RESI</td>
    </tr>
    <tr>
        <td colspan="10">Periode (<?= $filter_tanggal ?>): <?= $reportrange ?></td>
    </tr>
    <tr>
        <td colspan="10">Diunduh: <?= date('d/m/Y H:i:s') ?></td>
    </tr>
</table>

<br>

<table border="1">
  <thead>
    <tr class="header-red">
      <th width="40">#</th>
      <th width="160">No Resi</th>
      <th width="140">No Pesanan</th>
      <th width="110">Marketplace</th>
      <th width="140">Nama Toko</th>
      <th width="130">Status Marketplace</th>
      <th width="140">Tgl &amp; Jam Pesanan</th>
      <th width="140">Tgl &amp; Jam Cancel</th>
      <th width="110">Sumber Data</th>
      <th width="200">Catatan</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!empty($list_data)): ?>
      <?php $no = 1; foreach ($list_data as $row): ?>
        <tr>
          <td class="text-center"><?= $no++ ?></td>
          <td class="mono"><?= htmlspecialchars($row->noresi) ?></td>
          <td class="mono"><?= htmlspecialchars($row->no_pesanan ?: '-') ?></td>
          <td><?= htmlspecialchars($row->nama_marketplace ?: '-') ?></td>
          <td><?= htmlspecialchars($row->toko ?: '-') ?></td>
          <td class="text-center"><?= htmlspecialchars($row->status_marketplace ?: '-') ?></td>
          <td class="text-center"><?= $row->tanggal_pesan ? date('d/m/Y H:i:s', strtotime($row->tanggal_pesan)) : '-' ?></td>
          <td class="text-center"><?= $row->tanggal_cancel ? date('d/m/Y H:i:s', strtotime($row->tanggal_cancel)) : '-' ?></td>
          <td class="text-center"><?= $row->sumber === 'SCAN' ? 'SCAN MANUAL' : 'JUBELIO' ?></td>
          <td><?= htmlspecialchars($row->catatan ?: '') ?></td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr>
        <td colspan="10" class="text-center">Tidak ada data.</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>
