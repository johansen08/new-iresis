<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #1a1a2e; color: #fff; padding: 24px; width: 800px; }
  .header { text-align: center; margin-bottom: 20px; }
  .header h1 { font-size: 22px; color: #e94560; margin-bottom: 4px; }
  .header .date { font-size: 14px; color: #aaa; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
  th { background: #16213e; color: #e94560; padding: 10px 12px; text-align: left; font-size: 13px; border-bottom: 2px solid #e94560; }
  th.num { text-align: center; }
  td { padding: 8px 12px; font-size: 13px; border-bottom: 1px solid #2a2a4a; }
  td.num { text-align: center; font-weight: bold; }
  td.warn { color: #ff6b6b; }
  td.ok { color: #51cf66; }
  tr:nth-child(even) { background: rgba(255,255,255,0.03); }
  .footer { background: #16213e; border-radius: 8px; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; }
  .footer .total { font-size: 18px; font-weight: bold; }
  .footer .detail { font-size: 13px; color: #aaa; }
  .footer .detail span { color: #ff6b6b; font-weight: bold; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
  .badge-danger { background: rgba(233,69,96,0.2); color: #ff6b6b; }
  .badge-success { background: rgba(81,207,102,0.2); color: #51cf66; }
  .watermark { text-align: center; color: #444; font-size: 11px; margin-top: 12px; }
</style>
</head>
<body>
  <div class="header">
    <h1>🚨 CONTROL PENGIRIMAN HARIAN</h1>
    <div class="date"><?= date('d F Y') ?></div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Ekspedisi</th>
        <th class="num">Total Resi</th>
        <th class="num">Belum Pick</th>
        <th class="num">Belum Pack</th>
        <th class="num">Belum HO</th>
        <th class="num">Selesai</th>
      </tr>
    </thead>
    <tbody>
      <?php
        $total_resi = 0; $total_pick = 0; $total_pack = 0; $total_ho = 0; $total_done = 0;
        foreach ($rows as $row):
          $done = $row->total_resi - $row->belum_pick - $row->belum_pack - $row->belum_ho;
          $total_resi += $row->total_resi;
          $total_pick += $row->belum_pick;
          $total_pack += $row->belum_pack;
          $total_ho   += $row->belum_ho;
          $total_done += $done;
          $pct = $row->total_resi > 0 ? round(($done / $row->total_resi) * 100) : 100;
      ?>
      <tr>
        <td><?= $row->nama_kurir ?></td>
        <td class="num"><?= number_format($row->total_resi) ?></td>
        <td class="num <?= $row->belum_pick > 0 ? 'warn' : 'ok' ?>"><?= $row->belum_pick ?></td>
        <td class="num <?= $row->belum_pack > 0 ? 'warn' : 'ok' ?>"><?= $row->belum_pack ?></td>
        <td class="num <?= $row->belum_ho > 0 ? 'warn' : 'ok' ?>"><?= $row->belum_ho ?></td>
        <td class="num">
          <span class="badge <?= $pct >= 100 ? 'badge-success' : 'badge-danger' ?>"><?= $pct ?>%</span>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="footer">
    <div class="total">📊 TOTAL: <?= number_format($total_resi) ?> resi</div>
    <div class="detail">
      ❌ Pick: <span><?= $total_pick ?></span> |
      ❌ Pack: <span><?= $total_pack ?></span> |
      ❌ HO: <span><?= $total_ho ?></span> |
      ✅ Selesai: <span style="color:#51cf66"><?= $total_done ?></span>
    </div>
  </div>

  <div class="watermark">Laporan otomatis IRESIS — <?= date('H:i') ?> WIB</div>
</body>
</html>
