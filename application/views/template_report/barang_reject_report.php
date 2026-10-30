<style>
  table {
    font-family: "Open Sans", sans-serif;
    font-size: 12px;
    border-collapse: collapse;
  }
  th, td {
    border: 1px solid #ccc;
    padding: 5px;
  }
  th {
    background-color: #f2f2f2;
  }
</style>

<h3>Laporan Barang Reject</h3>
<p>Periode: <?= $start_date ?> sampai <?= $end_date ?></p>

<table>
  <thead>
    <tr>
      <th>No</th>
      <th>Tanggal</th>
      <th>No Resi</th>
      <th>SKU</th>
      <th>Nama Barang</th>
      <th>Jenis Masalah</th>
      <th>Qty</th>
      <th>Picker</th>
      <th>Packer</th>
    </tr>
  </thead>
  <tbody>
    <?php $i = 1; foreach ($list_data as $d) : ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= htmlspecialchars($d['created']) ?></td>
        <td style="mso-number-format:'\@'"><?= htmlspecialchars($d['noresi']) ?></td>
        <td><?= htmlspecialchars($d['sku']) ?></td>
        <td><?= htmlspecialchars($d['nama_barang']) ?></td>
        <td><?= htmlspecialchars($d['type_masalah']) ?></td>
        <td><?= $d['qty_bermasalah'] > 0 ? $d['qty_bermasalah'] . ' / ' . $d['qty'] : $d['qty'] ?></td>
        <td><?= htmlspecialchars((string) $d['nama_picker']) ?></td>
        <td><?= htmlspecialchars((string) $d['nama_packer']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
