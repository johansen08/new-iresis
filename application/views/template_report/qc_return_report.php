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

<h3>Laporan Pengembalian Barang QC</h3>
<p>Periode: <?= $start_date ?> sampai <?= $end_date ?></p>

<table>
  <thead>
    <tr>
      <th>No</th>
      <th>Tanggal</th>
      <th>SKU</th>
      <th>Qty</th>
      <th>No Rak</th>
      <th>Kondisi</th>
      <th>Submitted By</th>
      <th>Status</th>
      <th>Acc By</th>
      <th>Acc At</th>
    </tr>
  </thead>
  <tbody>
    <?php $i = 1; foreach ($list_data as $data) : ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= $data['tanggal'] ?></td>
        <td><?= $data['sku'] ?></td>
        <td><?= $data['qty'] . ($data['qty_kurang'] > 0 ? " (Kurang: " . $data['qty_kurang'] . ")" : "") ?></td>
        <td><?= $data['no_rak'] ?></td>
        <td><?= $data['kondisi'] . ($data['keterangan_reject'] ? ' (' . $data['keterangan_reject'] . ')' : '') ?></td>
        <td><?= $data['submitter'] ?></td>
        <td><?= $data['status'] ?></td>
        <td><?= $data['approver'] ?></td>
        <td><?= $data['acc_at'] ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
