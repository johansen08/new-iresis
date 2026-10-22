<?php
/**
 * Hasil Cari Detail Resi untuk satu resi (potongan HTML, dimuat lewat AJAX oleh detail_resi/index).
 * Variabel: $keyword (string), $d (array dari Detail_resi_fcd::cari atau NULL).
 */
$e = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

if ($d === null) : ?>
  <div class="card notfound">Resi atau nomor pesanan <b class="mono"><?= $e($keyword) ?></b> tidak ditemukan.</div>
<?php return; endif;

$bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
// Tanggal kosong atau '0000-00-00 ...' dianggap tidak ada.
$ada = function ($v) {
    return !empty($v) && strpos((string) $v, '0000-00-00') !== 0;
};
$fd = function ($v) use ($ada, $bulan) {
    if (!$ada($v)) {
        return null;
    }
    $t = strtotime($v);
    if (!$t) {
        return null;
    }
    $tanggal = date('j', $t) . ' ' . $bulan[(int) date('n', $t) - 1];
    // Kolom bertipe tanggal (mis. tgl komplain) berjam 00:00 -- jam tidak ditampilkan.
    return date('H:i:s', $t) === '00:00:00' ? $tanggal : $tanggal . ' ' . date('H:i', $t);
};
$fd_pendek = function ($v) use ($ada, $bulan) {
    if (!$ada($v)) {
        return null;
    }
    $t = strtotime($v);
    return $t ? date('j', $t) . ' ' . $bulan[(int) date('n', $t) - 1] : null;
};
$info = function ($teks) use ($e) {
    return '<button type="button" class="i" aria-label="Info: ' . $e($teks) . '">i<span class="tip" role="tooltip">' . $e($teks) . '</span></button>';
};
$salin = function ($teks, $label) use ($e) {
    return '<button type="button" class="cpy" data-copy="' . $e($teks) . '" aria-label="Salin ' . $e($label) . '" title="Salin">'
        . '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="5.5" y="5.5" width="8" height="8" rx="1.5"/><path d="M10.5 3.5v-.5a1.5 1.5 0 0 0-1.5-1.5H4A1.5 1.5 0 0 0 2.5 3v5A1.5 1.5 0 0 0 4 9.5h.5"/></svg></button>';
};

$r      = $d['resi'];
$ambil  = $d['ambil'];
$pack   = $d['pack'];
$ho     = $d['ho'];
$kin    = $d['kinerja'];
$status = (string) $r['status_pesanan'];

$kelas_status = ['COMPLETED' => 'ok', 'SHIPPED' => 'info', 'PROCESSING' => 'warn', 'CANCELED' => 'bad', 'RETURNED' => 'bad', 'REQUEST_CANCEL' => 'bad'];
$kls = isset($kelas_status[$status]) ? $kelas_status[$status] : 'mute';

// Batas kirim: merah bila hari ini atau terlewat dan paket belum diserahkan di HO.
$tgl_ho   = $ho ? $ho['tgl'] : null;
$sudah_ho = $ada($tgl_ho);
$batas    = $ada($r['tanggal_bataskirim']) ? substr($r['tanggal_bataskirim'], 0, 10) : null;
$hari_ini = date('Y-m-d');
$kls_batas = '';
$teks_batas = '-';
if ($batas) {
    $teks_batas = $fd_pendek($r['tanggal_bataskirim']);
    if (!$sudah_ho && $batas === $hari_ini) {
        $kls_batas  = 'today';
        $teks_batas = 'Hari ini · ' . $teks_batas;
    } elseif (!$sudah_ho && !$ada($r['tanggal_selesai']) && $batas < $hari_ini) {
        $kls_batas  = 'late';
        $teks_batas = 'Terlewat · ' . $teks_batas;
    }
}

// Bagian sekunder: jumlah isi menentukan badge dan kartu otomatis terbuka.
$retur_ada = $ada($r['tanggal_retur']) || $ada($d['retur_diterima']) || !empty($d['retur_buka']) || $ada($d['retur_acc']);
$n_masalah = count($d['masalah']) + count($d['lost']);
$n_log     = (empty($d['video']) ? 0 : 1) + count($d['batalscan']) + ($ada($d['ndd']) ? 1 : 0);

$peringatan = [];
if (!empty($d['cancel'])) {
    $peringatan[] = ['cancel', 'Paket cancel ditemukan di ' . $d['cancel']['ditemukan_di'] . ' · ditolak scan ' . count($d['tolak']) . '×'];
}
if (!empty($d['masalah'])) {
    $peringatan[] = ['masalah', count($d['masalah']) . ' masalah picker/packer'];
}
if (!empty($d['lost'])) {
    $peringatan[] = ['masalah', count($d['lost']) . ' lost scan ' . $d['lost'][0]['jenis']];
}
if (!empty($d['komplain'])) {
    $peringatan[] = ['komplain', count($d['komplain']) . ' komplain CS'];
}
if ($retur_ada) {
    $peringatan[] = ['retur', 'Retur' . ($ada($d['retur_diterima']) ? ' diterima ' . $fd_pendek($d['retur_diterima']) : ' tercatat')];
}

// Alur: enam langkah. Langkah berikutnya setelah yang terakhir terisi = "sedang berjalan" (atau ✕ bila CANCELED).
$langkah = [
    ['Resi diproses', $r['tanggal_printresi'], $r['admin'], 'Resi masuk ke sistem. BOT berarti masuk lewat unggah otomatis.'],
    ['Scan picker', $ambil ? $ambil['tgl'] : null, $ambil ? $ambil['nama'] : null, null],
    ['Scan packer', $pack ? $pack['tgl'] : null, $pack ? $pack['nama'] : null, null],
    ['Scan HO', $tgl_ho, $ho ? $ho['nama'] : null, null],
    ['Selesai', $r['tanggal_selesai'], null, 'Tanggal pesanan selesai di marketplace.'],
    ['Dana cair', $r['tanggal_pencairan'], null, 'Diisi dari unggah Excel pencairan. Kosong bila belum diunggah.'],
];
$terakhir = -1;
foreach ($langkah as $i => $l) {
    if ($ada($l[1])) {
        $terakhir = $i;
    }
}
$total_qty = 0;
foreach ($d['items'] as $it) {
    $total_qty += (int) $it['jumlah'];
}
?>
<div data-resi="<?= $e($r['noresi']) ?>">

<section class="card ident">
  <div>
    <div class="row1">
      <span class="noresi"><?= $e($r['noresi']) ?></span><?= $salin($r['noresi'], 'nomor resi') ?>
      <span class="pill p-<?= $kls ?>"><?= $e($status !== '' ? $status : '-') ?></span>
    </div>
    <div class="meta">
      <span>No. pesanan <b class="mono"><?= $e($d['pesanan'] !== null ? $d['pesanan'] : '-') ?></b> <?= $d['pesanan'] !== null ? $salin($d['pesanan'], 'nomor pesanan') : '' ?></span>
      <span>Marketplace <b><?= $e($r['nama_marketplace'] ?: '-') ?></b></span>
      <span>Kurir <b><?= $e($r['nama_kurir'] ?: '-') ?></b></span>
    </div>
  </div>
  <div class="deadline <?= $kls_batas ?>">
    <div class="lbl">Batas kirim <?= $info('Tanggal paling lambat paket harus keluar dari gudang. Merah bila jatuh hari ini atau sudah lewat dan paket belum diserahkan di HO.') ?></div>
    <div class="v"><?= $e($teks_batas) ?></div>
  </div>
</section>

<?php if (!empty($peringatan)) : ?>
<div class="alerts" role="alert">
  <span class="lbl">Perlu perhatian</span>
  <?php foreach ($peringatan as $p) : ?>
    <button type="button" class="chip" data-open="<?= $p[0] ?>"><?= $e($p[1]) ?></button>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<section class="card flow" aria-label="Alur paket">
  <?php foreach ($langkah as $i => $l) :
      $isi = $ada($l[1]);
      if ($isi) {
          $kls_l = 'done'; $tanda = '✓';
      } elseif ($i === $terakhir + 1) {
          $kls_l = ($status === 'CANCELED') ? 'stop' : 'cur';
          $tanda = ($status === 'CANCELED') ? '✕' : ($i + 1);
      } else {
          $kls_l = 'todo'; $tanda = $i + 1;
      }
  ?>
    <div class="step <?= $kls_l ?>">
      <div class="dot"><?= $tanda ?></div>
      <div class="nm"><?= $e($l[0]) ?><?= $l[3] ? ' ' . $info($l[3]) : '' ?></div>
      <div class="tm"><?= $isi ? $e($fd($l[1])) : 'belum' ?></div>
      <div class="pic" title="<?= $e($l[2]) ?>"><?= $e($l[2]) ?>&nbsp;</div>
    </div>
  <?php endforeach; ?>
</section>

<div class="main">
  <section class="card">
    <div class="ch"><h2>Barang dalam resi <span class="pill p-mute" style="height:20px"><?= count($d['items']) ?> SKU</span></h2></div>
    <div class="scroll">
      <table>
        <thead><tr><th>SKU</th><th>Rak <?= $info('Nomor rak dari master SKU. Tertulis BELUM DITENTUKAN bila belum diisi.') ?></th><th class="n">Qty</th></tr></thead>
        <tbody>
        <?php if (empty($d['items'])) : ?>
          <tr><td colspan="3" class="empty">Resi ini belum punya baris barang.</td></tr>
        <?php endif; ?>
        <?php foreach ($d['items'] as $it) : ?>
          <tr>
            <td><span class="sku"><?= $e($it['sku']) ?></span></td>
            <td><span class="rak"><?= $e($it['no_rak']) ?></span></td>
            <td class="n qty"><?= (int) $it['jumlah'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="2">Total</td><td class="n qty"><?= $total_qty ?></td></tr></tfoot>
      </table>
    </div>
  </section>

  <section class="card people">
    <div class="ch"><h2>Petugas <?= $info('Kategori kinerja adalah jenis order yang menentukan target harian (mis. NORMAL, 1_SKU, NDD), bukan nilai cepat atau lambat.') ?></h2></div>

    <div class="person">
      <span class="lbl">Picker</span>
      <span class="who"><?= $e($ambil && $ambil['nama'] ? $ambil['nama'] : '–') ?></span>
      <?= $ambil && $ambil['kategori'] ? '<span class="pill p-mute">' . $e($ambil['kategori']) . '</span>' : '<span></span>' ?>
      <span class="sub"><?= $ambil ? ($ambil['nama_komputer'] ? 'Komputer <b class="mono">' . $e($ambil['nama_komputer']) . '</b>' : '') : 'Belum diambil' ?></span>
    </div>

    <div class="person">
      <span class="lbl">Packer</span>
      <span class="who"><?= $e($pack && $pack['nama'] ? $pack['nama'] : '–') ?></span>
      <?= $pack && $pack['kategori'] ? '<span class="pill p-mute">' . $e($pack['kategori']) . '</span>' : '<span></span>' ?>
      <span class="sub">
      <?php if (!$pack) : ?>
        Belum dipacking
      <?php elseif ($pack['sync']) : ?>
        Disinkronkan dari scan picker, bukan scan packer <?= $info('Packing dibuat massal lewat menu Inbound Picker (keterangan SYNC_FROM_PICKER). Tidak ada komputer, durasi, maupun video.') ?>
      <?php elseif ($pack['lost']) : ?>
        Dibuat dari lost scan HO langsung, bukan scan packer <?= $info('Packing dicatat saat paket dilaporkan lost scan dan diserahkan langsung di HO. Tidak ada komputer, durasi, maupun video.') ?>
      <?php else : ?>
        <?php if ($pack['komputer'] !== '') : ?>Komputer <b class="mono"><?= $e($pack['komputer']) ?></b><?php endif; ?>
        <?php if ($kin) : ?>
          · Durasi <b><?= (int) $kin['durasi_aktual'] ?> dtk</b> / target <?= (int) $kin['durasi_target'] ?>
          <span class="pill p-<?= $kin['is_slow'] ? 'bad' : 'ok' ?>" style="height:18px"><?= $kin['is_slow'] ? 'Lambat' : 'Sesuai' ?></span>
          <?= $info('Durasi scan packer dibanding target kategori kinerjanya.') ?>
        <?php endif; ?>
        <?php if (!empty($d['video'])) : ?>· <a href="cs/putar-video-packing/<?= (int) $d['video'][0]['id_videopacking'] ?>" target="_blank" rel="noopener">Video</a><?php endif; ?>
      <?php endif; ?>
      </span>
    </div>

    <div class="person">
      <span class="lbl">HO</span>
      <span class="who"><?= $e($ho && $ho['nama'] ? $ho['nama'] : '–') ?></span>
      <span></span>
      <span class="sub"><?= $sudah_ho ? 'Diserahkan ' . $e($fd($tgl_ho)) : 'Belum diserahkan' ?></span>
    </div>
  </section>
</div>

<div class="more">
  <?php
  $kartu = [
      ['retur', 'Retur', $retur_ada ? 1 : 0, true],
      ['masalah', 'Masalah & lost', $n_masalah, false],
      ['cancel', 'Paket cancel', !empty($d['cancel']) ? 1 : 0, false],
      ['komplain', 'Komplain CS', count($d['komplain']), false],
      ['log', 'Video & log scan', $n_log, true],
      ['data', 'Data tambahan', null, true],
  ];
  foreach ($kartu as $k) :
      $punya = $k[2] > 0;
  ?>
    <button type="button" class="tg<?= $punya ? ' has' . ($k[3] ? ' soft' : '') : '' ?>" aria-expanded="false" data-k="<?= $k[0] ?>">
      <span class="t"><?= $e($k[1]) ?></span><?= $k[2] === null ? '' : '<span class="c">' . (int) $k[2] . '</span>' ?>
    </button>
  <?php endforeach; ?>
</div>

<div class="panels">

  <section class="card pn" data-p="retur" hidden>
    <div class="ch"><h2>Retur</h2><button type="button" class="iconbtn" data-close="retur">Tutup</button></div>
    <div class="body">
    <?php if (!$retur_ada) : ?>
      <span class="empty">Belum ada retur untuk resi ini.</span>
    <?php else : ?>
      <div class="kv">
        <?php if ($ada($r['tanggal_retur'])) : ?><div><span>Tanggal retur <?= $info('Dari data pesanan (Jubelio).') ?></span><b><?= $e($fd($r['tanggal_retur'])) ?></b></div><?php endif; ?>
        <?php if ($ada($d['retur_diterima'])) : ?><div><span>Diterima</span><b><?= $e($fd($d['retur_diterima'])) ?></b></div><?php endif; ?>
        <?php if (!empty($d['retur_buka'])) : ?><div><span>Dibuka</span><b><?= $e($fd(end($d['retur_buka'])['tgl'])) ?></b></div><?php endif; ?>
        <?php if ($ada($d['retur_acc'])) : ?><div><span>Tanggal ACC <?= $info('Waktu retur terverifikasi.') ?></span><b><?= $e($fd($d['retur_acc'])) ?></b></div><?php endif; ?>
      </div>
      <?php if (!empty($d['retur_buka'])) : ?>
      <ul class="lst" style="margin-top:8px">
        <?php foreach ($d['retur_buka'] as $b) : ?>
          <li><b class="mono"><?= $e($b['sku']) ?></b><span>×<?= (int) $b['qty'] ?></span><span class="pill p-<?= $b['status'] === 'KE_DISPLAY' ? 'info' : 'bad' ?>"><?= $e($b['status']) ?></span></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    <?php endif; ?>
    </div>
  </section>

  <section class="card pn" data-p="masalah" hidden>
    <div class="ch"><h2>Masalah &amp; lost scan</h2><button type="button" class="iconbtn" data-close="masalah">Tutup</button></div>
    <div class="body">
    <?php if ($n_masalah === 0) : ?>
      <span class="empty">Tidak ada masalah tercatat.</span>
    <?php else : ?>
      <ul class="lst">
        <?php foreach ($d['masalah'] as $m) : ?>
          <li>
            <span class="when"><?= $e($fd($m['tgl'])) ?></span><b><?= $e($m['jenis']) ?> · <?= $e($m['tipe']) ?></b>
            <span class="sub2"><span class="mono"><?= $e($m['sku']) ?></span><?= $m['sku_salah'] ? ' → <span class="mono">' . $e($m['sku_salah']) . '</span>' : '' ?> ×<?= (int) $m['qty'] ?><?= $m['ket'] ? ' · ' . $e($m['ket']) : '' ?><?= $m['oleh'] ? ' · ' . $e($m['oleh']) : '' ?></span>
          </li>
        <?php endforeach; ?>
        <?php foreach ($d['lost'] as $m) : ?>
          <li>
            <span class="when"><?= $e($fd($m['tgl'])) ?></span><b>Lost scan <?= $e($m['jenis']) ?></b>
            <span class="sub2"><?= $e(trim($m['oleh'])) ?> · status resi saat itu <?= $e($m['status_resi']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    </div>
  </section>

  <section class="card pn" data-p="cancel" hidden>
    <div class="ch"><h2>Paket cancel</h2><button type="button" class="iconbtn" data-close="cancel">Tutup</button></div>
    <div class="body">
    <?php if (empty($d['cancel'])) : ?>
      <span class="empty">Resi ini tidak tercatat sebagai paket cancel.</span>
    <?php else : $c = $d['cancel']; ?>
      <div class="kv">
        <div><span>Ditemukan di</span><b><?= $e($c['ditemukan_di']) ?></b></div>
        <?php if ($c['oleh']) : ?><div><span>Ditemukan oleh</span><b><?= $e($c['oleh']) ?></b></div><?php endif; ?>
        <?php if ($c['ditemukan_komputer']) : ?><div><span>Komputer</span><b><?= $e($c['ditemukan_komputer']) ?></b></div><?php endif; ?>
        <?php if ($ada($c['ditemukan_at'])) : ?><div><span>Waktu</span><b><?= $e($fd($c['ditemukan_at'])) ?></b></div><?php endif; ?>
      </div>
      <?php if (!empty($d['tolak'])) : ?>
        <div class="lbl" style="margin:10px 0 4px">Riwayat scan ditolak (CANCELED)</div>
        <ul class="lst">
          <?php foreach ($d['tolak'] as $t) : ?>
            <li><span class="when"><?= $e($fd($t['waktu'])) ?></span><b><?= $e($t['tahap']) ?></b><span class="sub2"><?= $e($t['nama_komputer']) ?></span></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    <?php endif; ?>
    </div>
  </section>

  <section class="card pn" data-p="komplain" hidden>
    <div class="ch"><h2>Komplain CS</h2><button type="button" class="iconbtn" data-close="komplain">Tutup</button></div>
    <div class="body">
    <?php if (empty($d['komplain'])) : ?>
      <span class="empty">Tidak ada komplain.</span>
    <?php else : ?>
      <ul class="lst">
        <?php foreach ($d['komplain'] as $k) :
            $selesai = ($k['status'] === 'Selesai');
            $nama = array_filter([
                $k['nama_packer'] ? 'Packer ' . $k['nama_packer'] : '',
                $k['nama_picker'] ? 'Picker ' . $k['nama_picker'] : '',
            ]);
        ?>
          <li>
            <span class="when"><?= $e($fd($k['tgl'])) ?></span><b><?= $e($k['kategori']) ?></b>
            <span class="pill p-<?= $selesai ? 'ok' : 'warn' ?>"><?= $e($k['status'] ?: 'Belum ditangani') ?></span>
            <span class="sub2"><?= ((float) $k['nominal'] > 0 ? 'Rp ' . number_format((float) $k['nominal'], 0, ',', '.') . ' · ' : '') . $e(implode(' · ', $nama)) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    </div>
  </section>

  <section class="card pn" data-p="log" hidden>
    <div class="ch"><h2>Video &amp; log scan</h2><button type="button" class="iconbtn" data-close="log">Tutup</button></div>
    <div class="body">
    <?php if ($n_log === 0) : ?>
      <span class="empty">Tidak ada video atau log scan tambahan.</span>
    <?php else : ?>
      <ul class="lst">
        <?php foreach ($d['video'] as $v) : ?>
          <li><b>Video packing<?= count($d['video']) > 1 ? ' bagian ' . (int) $v['bagian'] : '' ?></b>
            <a href="cs/putar-video-packing/<?= (int) $v['id_videopacking'] ?>" target="_blank" rel="noopener">Putar rekaman</a>
            <span class="sub2"><?= $e($fd($v['mulai_at'])) ?><?= $v['durasi_detik'] ? ' · ' . (int) $v['durasi_detik'] . ' dtk' : '' ?></span></li>
        <?php endforeach; ?>
        <?php foreach ($d['batalscan'] as $b) : ?>
          <li><span class="when"><?= $e($fd($b['tgl'])) ?></span><b>Batal scan packer</b>
            <span class="sub2"><?= $e($b['nama']) ?> · <?= $e($b['nama_komputer']) ?> · <?= (int) $b['durasi_detik'] ?> dtk</span></li>
        <?php endforeach; ?>
        <?php if ($ada($d['ndd'])) : ?>
          <li><span class="when"><?= $e($fd($d['ndd'])) ?></span><b>Scan NDD</b></li>
        <?php endif; ?>
      </ul>
    <?php endif; ?>
    </div>
  </section>

  <section class="card pn" data-p="data" hidden>
    <div class="ch"><h2>Data tambahan</h2><button type="button" class="iconbtn" data-close="data">Tutup</button></div>
    <div class="body">
      <div class="kv">
        <?php if ($r['nomorpicklist'] !== null && $r['nomorpicklist'] !== '') : ?><div><span>No. picklist</span><b><?= $e($r['nomorpicklist']) ?></b></div><?php endif; ?>
        <?php if ($r['tipe_resi']) : ?><div><span>Tipe resi <?= $info('Satuan = satu SKU; campuran = lebih dari satu SKU.') ?></span><b><?= $e($r['tipe_resi']) ?></b></div><?php endif; ?>
        <?php if ($ada($r['tanggal_pesan'])) : ?><div><span>Tanggal pesan</span><b><?= $e($fd($r['tanggal_pesan'])) ?></b></div><?php endif; ?>
        <?php if ($ada($r['tanggal_pengiriman'])) : ?><div><span>Tanggal pengiriman <?= $info('Waktu pesanan dikirim menurut marketplace.') ?></span><b><?= $e($fd($r['tanggal_pengiriman'])) ?></b></div><?php endif; ?>
      </div>
    </div>
  </section>

</div>

</div>
