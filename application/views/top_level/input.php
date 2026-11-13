<?php
// View menu TIM CS -> Input Top Level. Dimuat lewat AJAX (SPA).
// CS memasukkan stok top level dari tim Restok: Petugas List dan Petugas Input (saran mengikuti ketikan), lalu baris SKU, no rak, qty.
// Setiap simpan menghasilkan satu nomor transfer TTL-TAHUN-NNNNN (stok masuk).
if (!empty($akses_ditolak)) : ?>
<div class="alert alert-danger"><i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu Input Top Level. Minta admin membukanya lewat menu Access.</div>
<?php return; endif;
$this->load->view('top_level/_dasar'); ?>
<div class="tl" id="tl-in"></div>
<script>
(function () {
  var T = window.TL, $r = $('#tl-in'), baris = [], selesai = null, galat = '', sibuk = false;
  var KOSONG = function () { return { sku: '', rak: '', qty: '' }; };
  // Arah transfer: tanpa default, penginput wajib memilih. MASUK menambah stok rak, KELUAR mengurangi stok rak (diingat selama menu tidak di-reload).
  var arah = window.__tlArah === 'KELUAR' || window.__tlArah === 'MASUK' ? window.__tlArah : '';
  var keluar = function () { return arah === 'KELUAR'; };
  baris = [KOSONG(), KOSONG(), KOSONG()];
  // Tandai sebagai kesalahan: pihak, jenis, nilai untuk keterangan, transfer asal, keterangan. pasangan = transfer kedua dari kejadian yang sama.
  var KSL0 = function () { return { on: false, pihak: [], jenis: [], fv: {}, ref: '', ket: '', kotor: false, pasangan: null }; };
  var ksl = KSL0();
  var TDK = 'Tidak Diketahui';
  var JENIS = [['LUPA_TULIS', 'Lupa tulis'], ['LUPA_INPUT', 'Lupa input'], ['SALAH_SKU', 'Salah SKU'], ['SALAH_QTY', 'Salah qty'], ['SALAH_RAK', 'Salah no. rak'], ['TIDAK_ADA', 'Tercatat padahal tidak ada / dobel']];
  var NAMA_J = {}; $.each(JENIS, function (i, j) { NAMA_J[j[0]] = j[1]; });
  var FJ = { SALAH_SKU: ['SKU', 'sku'], SALAH_QTY: ['Qty', 'qty'], SALAH_RAK: ['no. rak', 'rak'] };

  function judul() {
    return '<div class="tl-ph"><div class="t"><h1>Input Top Level</h1>' +
      T.info('Pilih arah: Masuk menambah stok rak, Keluar mengurangi stok rak.\nArah keluar hanya bisa untuk SKU dan rak yang stoknya cukup.\nArah keluar: SKU yang hanya ada di satu rak mengisi no rak otomatis; kalau ada di beberapa rak, saran rak muncul saat no rak diketik.\nArah masuk: saran no rak muncul saat no rak diketik.\nMasukkan stok top level dari tim Restok.\nPetugas List dan tanggal list: yang mendata stok. Petugas Input dan tanggal input: yang menginput.\nPilih nama dari saran atau ketik bebas.\nSetiap simpan menghasilkan satu nomor transfer TTL-TAHUN-NNNNN untuk stok masuk.\nRak baru dibuat otomatis.\nArah masuk: SKU dan rak yang sudah punya stok ditandai merah sebagai duplikat dan tidak bisa disimpan. Centang Tandai penambahan stok di baris itu bila stoknya memang bertambah (bukan input dobel).\nTandai sebagai kesalahan dipakai untuk memperbaiki catatan yang salah (lupa tulis, salah SKU, salah qty, salah no rak). Keterangan terisi otomatis dan bisa diedit. Salah SKU atau salah no rak butuh dua transfer (keluar dan masuk): setelah transfer pertama tersimpan, pilih Buat transfer pasangan supaya tetap dihitung satu kesalahan.\nSKU dan rak yang sama boleh diisi lebih dari satu baris asalkan qty-nya berbeda; qty dijumlahkan. Qty yang sama persis ditandai dobel.') + '</div></div>';
  }

  function paint() {
    if (selesai) {
      $r.html(judul() + '<section class="tl-panel"><div class="tl-res" style="padding:36px 16px"><div class="ring ok">' + T.ic('check', 26) + '</div>' +
        '<span class="muted">Nomor transfer</span><b class="mono tl-ttl">' + T.esc(selesai.nomor) + '</b>' +
        '<span class="muted">' + selesai.baris + ' baris · ' + selesai.qty + ' pcs · ' + tglIndo(selesai.tanggal) + '</span>' +
        (selesai.kesalahan ? '<span class="muted">Dicatat sebagai kesalahan <b class="mono">' + T.esc(selesai.kesalahan) + '</b></span>' : '') +
        '<div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap;justify-content:center"><button type="button" class="tl-btn" data-salin>Salin nomor</button><button type="button" class="tl-btn" data-lagi>Input lagi</button>' +
        (selesai.kesalahan ? '<button type="button" class="tl-btn" data-pasangan data-tip="Salah SKU atau salah no rak butuh dua transfer (keluar dan masuk). Transfer pasangan memakai catatan kesalahan yang sama, jadi tetap dihitung satu kesalahan.">' + T.ic('alert', 14) + 'Buat transfer pasangan</button>' : '') +
        '<button type="button" class="tl-btn pri" data-stok>Lihat Stok Top Level</button></div></div></section>');
      return;
    }
    // Kartu = nama petugas (dengan saran) + tanggalnya. Petugas List <-> TGL LIST, Petugas Input <-> TGL INPUT di Excel upload.
    var kartu = function (judulKartu, idIn, labelTgl, idTgl, extra) {
      return '<section class="tl-panel tl-pad"><div class="tl-pair">' +
        '<label for="' + idIn + '" class="tl-lbl">' + judulKartu + '</label><label for="' + idTgl + '" class="tl-lbl">' + labelTgl + '</label>' +
        '<div><input type="text" id="' + idIn + '" placeholder="Ketik nama, atau pilih dari saran" autocomplete="off" style="width:100%">' + (extra || '') + '</div>' +
        '<div><input type="date" id="' + idTgl + '" max="' + hariIni() + '" style="width:100%"></div></div></section>';
    };
    // Petugas list yang tidak bisa dilacak: hanya untuk transfer yang ditandai sebagai kesalahan (tombol muncul setelah tanda dicentang).
    var tombolTdk = '<button type="button" class="tl-btn sm ghost" id="tl-list-tdk" style="display:none;margin-top:6px" data-tip="Dipakai bila petugas list tidak bisa dilacak. Hanya untuk transfer yang ditandai sebagai kesalahan.">' + T.ic('alert', 14) + 'Tidak diketahui</button>';
    var h = judul() + '<div class="tl-cards3"><section class="tl-panel tl-pad"><label class="tl-lbl" for="tl-arah">Arah</label>' +
      '<select id="tl-arah" class="tl-arah ' + arah.toLowerCase() + '"><option value=""' + (arah ? '' : ' selected') + '>Pilih arah</option>' +
      '<option value="MASUK"' + (arah === 'MASUK' ? ' selected' : '') + '>Masuk</option>' +
      '<option value="KELUAR"' + (arah === 'KELUAR' ? ' selected' : '') + '>Keluar</option></select></section>' +
      kartu('Petugas List', 'tl-list', 'Tanggal list', 'tl-tgl-list', tombolTdk) +
      kartu('Petugas Input', 'tl-pinput', 'Tanggal input', 'tl-tgl') + '</div>' +
      '<section class="tl-panel tl-pad" id="tl-ksl-box"></section>' +
      '<section class="tl-panel"><div class="tl-line tl-line-h"><span>SKU</span><span>No rak top level</span><span>Qty</span><span></span></div><div id="tl-baris"></div>' +
      '<div class="tl-line"><div><button type="button" class="tl-btn sm ghost" data-tambah>' + T.ic('plus', 14) + 'Tambah baris</button></div></div>' +
      '<div class="tl-sumbar"><span class="muted" id="tl-ring"></span><div style="display:flex;gap:10px;align-items:center"><span class="tl-fmsg" id="tl-gal" style="min-height:0"></span>' +
      '<button type="button" class="tl-btn pri" data-simpan>Simpan</button></div></div></section>';
    $r.html(h);
    gambarKsl();
    $('#tl-list').val(window.__tlList || '');
    // Petugas Input bawaan = akun yang sedang login; tetap bisa diganti (pilihan terakhir diingat selama menu tidak di-reload).
    if (window.__tlInput === undefined) window.__tlInput = <?= json_encode((string) (isset($akun_login) ? $akun_login : '')) ?>;
    $('#tl-pinput').val(window.__tlInput || '');
    $('#tl-tgl').val(window.__tlTgl || hariIni());
    $('#tl-tgl-list').val(window.__tlTglList || hariIni());
    $.each([['#tl-list', '__tlList'], ['#tl-pinput', '__tlInput']], function (i, p) {
      T.combo($(p[0]), function (q, kirim) {
        T.post('cs/input-top-level/tim', { q: q }, function (d) {
          var daftar = $.map(d.rows, function (x) { return { nilai: x.nama, label: x.nama, grup: x.dari === 'terakhir' ? 'Terakhir dipakai' : 'Pegawai' }; });
          // Petugas list boleh "Tidak Diketahui" bila transfer ditandai sebagai kesalahan.
          if (p[0] === '#tl-list' && ksl.on && TDK.toLowerCase().indexOf(q.toLowerCase()) > -1) daftar.unshift({ nilai: TDK, label: TDK, sub: 'tidak bisa dilacak', grup: 'Khusus kesalahan' });
          kirim(daftar);
        }, function () { kirim([]); });
      }, function () { window[p[1]] = $(p[0]).val(); });
      $(p[0]).on('input', function () { window[p[1]] = this.value; });
    });
    $('#tl-arah').on('change', function () { arah = window.__tlArah = this.value; $(this).removeClass('masuk keluar').addClass(arah.toLowerCase()); dup = {};
      // Ganti arah: rak isian otomatis dilepas (masuk) atau diisi ulang dari SKU yang sudah ada (keluar).
      $.each(baris, function (i, b) {
        if (!keluar() && b.auto) { b.rak = ''; b.auto = false; $('#tl-baris .tl-line').eq(i).find('.f-rak').val(''); }
        else if (keluar() && b.sku) autoRak(i);
      });
      ringkas(); tandai(); cekDb(); });
    $('#tl-tgl').on('input', function () { window.__tlTgl = this.value; });
    $('#tl-tgl-list').on('input', function () { window.__tlTglList = this.value; });
    gambarBaris();
    ringkas();
  }

  function gambarBaris() {
    var h = '';
    $.each(baris, function (i, b) {
      h += '<div class="tl-line" data-i="' + i + '"><div><input type="text" class="f-sku" placeholder="Ketik kode SKU" autocomplete="off" value="' + T.esc(b.sku) + '"></div>' +
        '<div><input type="text" class="f-rak" placeholder="mis. TL-A1" autocomplete="off" value="' + T.esc(b.rak) + '"></div>' +
        '<div><input type="number" class="f-qty" min="1" placeholder="0" value="' + T.esc(b.qty) + '" style="text-align:right"></div>' +
        '<div><button type="button" class="tl-btn sm ghost" data-hapus aria-label="Hapus baris"' + (baris.length < 2 ? ' disabled' : '') + '>' + T.ic('x', 14) + '</button></div>' +
        '<div class="tl-dupmsg"></div><div class="tl-tmbrow"></div></div>';
    });
    $('#tl-baris').html(h);
    tandai();
    $('#tl-baris .f-sku').each(function () {
      var $in = $(this), i = +$in.closest('.tl-line').data('i');
      T.combo($in, function (q, kirim) {
        if (!q) { kirim([]); return; }
        T.post('cs/input-top-level/sku', { q: q }, function (d) {
          kirim($.map(d.rows, function (x) { return { nilai: x.sku, label: x.sku }; }));
        }, function () { kirim([]); });
      }, function () { baris[i].sku = $in.val(); autoRak(i); ringkas(); tandai(); cekDb(); $in.closest('.tl-line').find('.f-rak').focus(); });
    });
    // Saran no rak muncul hanya saat diketik. Keluar: rak yang menyimpan stok SKU baris itu. Masuk: semua rak sesuai ketikan.
    $('#tl-baris .f-rak').each(function () {
      var $in = $(this), i = +$in.closest('.tl-line').data('i');
      T.combo($in, function (q, kirim) {
        if (!q) { kirim([]); return; }
        T.post('cs/input-top-level/rak', { sku: baris[i] ? baris[i].sku : '', q: q, arah: arah }, function (d) {
          kirim($.map(d.rows, function (x) { return { nilai: x.rak, label: x.rak, sub: x.qty ? x.qty + ' pcs' : '' }; }));
        }, function () { kirim([]); });
      }, function () { baris[i].rak = $in.val(); baris[i].auto = false; ringkas(); tandai(); cekDb(); $in.closest('.tl-line').find('.f-qty').focus(); });
    });
  }

  // Arah keluar: begitu SKU diketik, kalau SKU itu hanya ada di satu rak maka no rak terisi otomatis.
  // Lebih dari satu rak: dibiarkan kosong, saran muncul saat no rak diketik. Isian otomatis ikut dilepas kalau SKU berubah.
  var timerRak = {};
  function autoRak(i) {
    clearTimeout(timerRak[i]);
    if (!keluar()) return;
    timerRak[i] = setTimeout(function () {
      var b = baris[i], sku = b && b.sku;
      if (!b) return;
      var terapkan = function (rak) {
        if (baris[i] !== b) return;
        b.rak = rak; b.auto = !!rak;
        $('#tl-baris .tl-line').eq(i).find('.f-rak').val(rak);
        ringkas(); tandai(); cekDb();
      };
      if (!sku) { if (b.auto) terapkan(''); return; }
      T.post('cs/input-top-level/rak', { sku: sku, q: '', arah: 'KELUAR' }, function (d) {
        if (baris[i] !== b || b.sku !== sku || !keluar()) return;
        if (d.rows.length === 1 && (!b.rak || b.auto)) terapkan(d.rows[0].rak);
        else if (d.rows.length !== 1 && b.auto) terapkan('');
      });
    }, 300);
  }

  // ---------- guard duplikat ----------
  // SKU + no rak yang sudah tersimpan dengan stok bukan nol = duplikat (bukan penambahan): baris langsung merah.
  var dup = {}, timerCek = null;
  var kunci = function (b) { return b.sku + '|' + String(b.rak).toUpperCase(); };
  function lengkap(b) { return b.sku && b.rak; }

  // Checkbox "Tandai penambahan stok" di baris Masuk yang SKU + rak-nya sudah ada stoknya.
  function tmbHtml(cek) {
    return '<label class="tl-tmb' + (cek ? ' cek' : '') + '"><input type="checkbox" data-tmb' + (cek ? ' checked' : '') + '>' + T.ic('tambahstok', 16) +
      '<span>Tandai penambahan stok<small>SKU dan no. rak sama, stok benar-benar bertambah (barang baru datang), bukan input dobel.</small></span></label>';
  }

  function tandai() {
    var lihat = {}, ada = 0;
    $('#tl-baris .tl-line').each(function () {
      var $l = $(this), b = baris[+$l.data('i')], msg = '', kand = false, tmb = false;
      if (b && lengkap(b)) {
        var k = kunci(b), q = parseInt(b.qty, 10) > 0 ? '|' + parseInt(b.qty, 10) : '';
        // SKU + rak sama dengan qty berbeda boleh (dijumlahkan); qty sama persis dianggap dobel.
        if (q && lihat[k + q] !== undefined) msg = 'Dobel: SKU, rak, dan qty sama dengan baris ' + (lihat[k + q] + 1) + '.';
        else if (keluar()) {
          if (q) lihat[k + q] = +$l.data('i');
          var stok = dup[k] || 0;
          if (!stok) msg = 'Tidak ada stok di rak ini.';
          else if (parseInt(b.qty, 10) > stok) msg = 'Stok di rak ini hanya ' + stok + ' pcs.';
        } else {
          if (q) lihat[k + q] = +$l.data('i');
          // Tanda kesalahan membuka guard ini (koreksi Masuk); tanpa itu, baris hanya lolos bila ditandai penambahan stok.
          if (dup[k] && !ksl.on) {
            kand = true;
            if (b.tambah && b.tambahKey === k) tmb = true;
            else msg = 'Duplikat: stok ' + dup[k] + ' sudah tersimpan di rak ini. Centang Tandai penambahan stok bila stoknya memang bertambah. ' + T.info('SKU dan no rak ini sudah tersimpan dengan stok ' + dup[k] + '.\nBila ini barang baru yang datang dan stoknya benar-benar bertambah, centang Tandai penambahan stok di baris ini.\nBila ini catatan yang salah, pakai Tandai sebagai kesalahan.\nBila ini input dobel, hapus atau ubah baris ini.');
          }
        }
      }
      $l.toggleClass('bad', !!msg).toggleClass('tmb', tmb);
      $l.find('.tl-dupmsg').html(msg);
      var $tr = $l.find('.tl-tmbrow'), hh = kand ? tmbHtml(tmb) : '';
      if ($tr.data('h') !== hh) { $tr.data('h', hh).html(hh); }
      $tr.toggleClass('on', kand);
      if (msg) ada++;
    });
    return ada;
  }

  function cekDb() {
    clearTimeout(timerCek);
    timerCek = setTimeout(function () {
      var kirim = $.map($.grep(baris, lengkap), function (b) { return { sku: b.sku, rak: String(b.rak).toUpperCase() }; });
      if (!kirim.length) { dup = {}; tandai(); return; }
      T.post('cs/input-top-level/cek', { rows: kirim }, function (d) {
        dup = {};
        $.each(d.rows, function (i, x) { dup[x.sku + '|' + String(x.rak).toUpperCase()] = x.qty; });
        tandai();
      });
    }, 350);
  }

  function ringkas() {
    var n = 0, q = 0;
    $.each(baris, function (i, b) { if (b.sku && b.rak && parseInt(b.qty, 10) > 0) { n++; q += parseInt(b.qty, 10); } });
    $('#tl-ring').text(n ? n + ' baris · ' + q + ' pcs' : '');
    if (ksl.on && !ksl.pasangan) isiKet(); // lupa tulis / lupa input / tidak ada memakai SKU, rak, qty dari baris pertama
  }

  // ---------- tandai sebagai kesalahan (koreksi) ----------
  function tanpaAsal() { // lupa tulis dan lupa input tidak punya transfer asal
    return ksl.jenis.length > 0 && $.grep(ksl.jenis, function (j) { return j !== 'LUPA_TULIS' && j !== 'LUPA_INPUT'; }).length === 0;
  }
  function barisPertama() { return $.grep(baris, function (x) { return x.sku; })[0] || {}; }
  function barangTeks() {
    var b = barisPertama();
    return (b.sku || '…') + ' rak ' + (String(b.rak || '').toUpperCase() || '…') + ' qty ' + (b.qty || '…');
  }
  // Satu kalimat per jenis; salah SKU/qty/rak satu kalimat per pihak: "Salah SKU ditulis A seharusnya B", "Salah Qty diinput 21 seharusnya 11".
  function ketOtomatis() {
    var L = [];
    $.each(ksl.jenis, function (i, j) {
      var f = FJ[j];
      if (f) {
        if (!ksl.pihak.length) { L.push('Salah ' + f[0] + ' … (pilih siapa yang salah)'); return; }
        $.each(ksl.pihak, function (x, p) {
          L.push('Salah ' + f[0] + ' ' + (p === 'LIST' ? 'ditulis' : 'diinput') + ' ' + (ksl.fv[j + '_' + p] || '…') + ' seharusnya ' + (ksl.fv[j + '_benar'] || '…'));
        });
      } else if (j === 'TIDAK_ADA') L.push(barangTeks() + ' tercatat padahal tidak ada');
      else L.push(NAMA_J[j] + ' ' + (arah === 'KELUAR' ? 'keluar' : arah === 'MASUK' ? 'tambah' : '…') + ' ' + barangTeks()); // lupa tulis / lupa input: barangnya keluar atau tambah
    });
    return L.join('\n');
  }
  // Keterangan terisi otomatis selama belum diedit petugas; "Isi ulang otomatis" mengembalikannya.
  function isiKet(paksa) {
    if (paksa) ksl.kotor = false;
    if (ksl.kotor) return;
    ksl.ket = ketOtomatis();
    $('#tl-ksl-ket').val(ksl.ket);
  }
  function refNote() {
    var w = !tanpaAsal();
    $('#tl-ksl-refnote').toggleClass('ok', !w).text(w ? 'wajib' : 'boleh kosong: lupa tulis / lupa input tidak punya transfer asal');
    return w;
  }
  function tglPendek(s) { var m = /^\d{4}-(\d{2})-(\d{2})/.exec(s || ''); return m ? m[2] + '/' + m[1] : ''; }

  function gambarKsl() {
    var $b = $('#tl-ksl-box');
    if (!$b.length) return;
    $('#tl-list-tdk').toggle(ksl.on);
    var h = '<label class="tl-ksl-h"><input type="checkbox" id="tl-ksl-on"' + (ksl.on ? ' checked' : '') + ' style="margin-top:4px">' + T.ic('alert', 18) +
      '<span>Tandai sebagai kesalahan<small>Transfer ini memperbaiki catatan yang salah karena petugas list atau petugas input.</small></span></label>';
    if (ksl.on && ksl.pasangan) {
      h += '<div class="tl-ksl"><div class="tl-ksl-pasangan">' + T.ic('alert', 16) + '<span>Transfer pasangan dari kesalahan <b class="mono">' + T.esc(ksl.pasangan.kode) +
        '</b>. Pihak, jenis, dan keterangan mengikuti transfer sebelumnya, dan dihitung sebagai satu kesalahan.</span><button type="button" class="tl-btn sm ghost" data-ksl-lepas>Lepas dari kesalahan ini</button></div></div>';
    } else if (ksl.on) {
      h += '<div class="tl-ksl"><div class="tl-ksl-2"><div><span class="tl-lbl">Siapa yang salah <span class="muted">(boleh keduanya)</span></span>' +
        '<label class="tl-chk"><input type="checkbox" data-k-pihak="LIST"' + ($.inArray('LIST', ksl.pihak) > -1 ? ' checked' : '') + '>Petugas list</label>' +
        '<label class="tl-chk"><input type="checkbox" data-k-pihak="INPUT"' + ($.inArray('INPUT', ksl.pihak) > -1 ? ' checked' : '') + '>Petugas input</label></div>' +
        '<div><span class="tl-lbl">Jenis kesalahan <span class="muted">(boleh lebih dari satu)</span></span>' +
        $.map(JENIS, function (j) { return '<label class="tl-chk"><input type="checkbox" data-k-jenis="' + j[0] + '"' + ($.inArray(j[0], ksl.jenis) > -1 ? ' checked' : '') + '>' + j[1] + '</label>'; }).join('') + '</div></div>' +
        '<div id="tl-ksl-detail"></div>' +
        '<div><label class="tl-lbl" for="tl-ksl-ref">Transfer asal yang dikoreksi <span class="tl-wajib" id="tl-ksl-refnote">wajib</span></label><div><input type="text" id="tl-ksl-ref" autocomplete="off" style="width:100%" placeholder="Ketik nomor TTL atau pilih dari rekomendasi" value="' + T.esc(ksl.ref) + '"></div></div>' +
        '<div><label class="tl-lbl" for="tl-ksl-ket">Keterangan <span class="muted">(terisi otomatis dari jenis kesalahan, bisa diedit)</span></label><textarea id="tl-ksl-ket" maxlength="500"></textarea>' +
        '<div style="margin-top:6px"><button type="button" class="tl-btn sm ghost" data-ksl-ulang>Isi ulang otomatis</button></div></div></div>';
    }
    $b.html(h);
    if (ksl.on && !ksl.pasangan) {
      gambarKslDetail();
      $('#tl-ksl-ket').val(ksl.ket);
      refNote();
      T.combo($('#tl-ksl-ref'), function (q, kirim) {
        var sku = barisPertama().sku || '';
        T.post('cs/input-top-level/transfer', { q: q, sku: sku }, function (d) {
          kirim($.map(d.rows, function (x) {
            return { nilai: x.nomor, label: x.nomor, sub: (x.arah === 'MASUK' ? 'Masuk ' : 'Keluar ') + x.ringkas + ' · ' + tglPendek(x.tgl), grup: x.cocok ? 'Memuat SKU ' + sku : 'Transfer terbaru' };
          }));
        }, function () { kirim([]); });
      }, function (it) { ksl.ref = it.nilai; $('#tl-ksl-ref').removeClass('bad'); });
    }
  }

  // Kotak "Nilai untuk keterangan": ditulis/diinput/seharusnya untuk jenis salah SKU, qty, atau rak; SKU dan no rak memberi rekomendasi.
  function gambarKslDetail() {
    var $d = $('#tl-ksl-detail'), h = '';
    $.each(ksl.jenis, function (i, j) {
      if (!FJ[j] || !ksl.pihak.length) return;
      var jenisIn = j === 'SALAH_QTY' ? 'type="number" min="0"' : 'type="text" autocomplete="off"', sug = j === 'SALAH_SKU' ? 'sku' : j === 'SALAH_RAK' ? 'rak' : '';
      var isi = function (kunciFv, label) {
        return '<div><span class="tl-lbl">' + label + '</span><div><input ' + jenisIn + ' data-kfv="' + kunciFv + '"' + (sug ? ' data-ksug="' + sug + '"' : '') + ' style="width:100%" value="' + T.esc(ksl.fv[kunciFv] || '') + '"></div></div>';
      };
      h += '<div><b>Salah ' + FJ[j][0] + '</b><div class="g" style="margin-top:6px">' +
        $.map(ksl.pihak, function (p) { return isi(j + '_' + p, p === 'LIST' ? 'Ditulis' : 'Diinput'); }).join('') + isi(j + '_benar', 'Seharusnya') + '</div></div>';
    });
    $d.html(h ? '<div class="tl-ksl-nilai"><b>Nilai untuk keterangan</b>' + h + '</div>' : '');
    $d.find('[data-ksug="sku"]').each(function () {
      var $in = $(this);
      T.combo($in, function (q, kirim) {
        if (!q) { kirim([]); return; }
        T.post('cs/input-top-level/sku', { q: q }, function (r) { kirim($.map(r.rows, function (x) { return { nilai: x.sku, label: x.sku }; })); }, function () { kirim([]); });
      }, function () { ksl.fv[$in.data('kfv')] = $in.val(); isiKet(); });
    });
    // Rak: yang menyimpan stok SKU di baris form lebih dulu (muncul sejak field difokus); ketikan tanpa hasil mencari semua rak.
    $d.find('[data-ksug="rak"]').each(function () {
      var $in = $(this);
      T.combo($in, function (q, kirim) {
        var sku = barisPertama().sku || '', peta = function (r) { return $.map(r.rows, function (x) { return { nilai: x.rak, label: x.rak, sub: x.qty ? x.qty + ' pcs' : '', grup: x.qty ? 'Rak ' + sku : 'Semua rak' }; }); };
        T.post('cs/input-top-level/rak', { sku: sku, q: q, arah: 'KELUAR' }, function (r) {
          if (r.rows.length || !q) { kirim(peta(r)); return; }
          T.post('cs/input-top-level/rak', { sku: sku, q: q, arah: 'MASUK' }, function (r2) { kirim(peta(r2)); }, function () { kirim([]); });
        }, function () { kirim([]); });
      }, function () { ksl.fv[$in.data('kfv')] = $in.val(); isiKet(); });
    });
  }

  function kslDl() {
    if (!ksl.on) return '';
    if (ksl.pasangan) return '<dt>Kesalahan</dt><dd>Pasangan dari <b class="mono">' + T.esc(ksl.pasangan.kode) + '</b></dd>';
    var p = $.map(ksl.pihak, function (x) { return x === 'LIST' ? 'Petugas list' : 'Petugas input'; }).join(' + '), j = $.map(ksl.jenis, function (x) { return NAMA_J[x]; }).join(', ');
    return '<dt>Kesalahan</dt><dd>' + T.esc(p) + ' · ' + T.esc(j) + (ksl.ref ? '<br>Transfer asal <b class="mono">' + T.esc(ksl.ref) + '</b>' : '') + '<br><span class="muted">' + T.esc(ksl.ket).replace(/\n/g, '<br>') + '</span></dd>';
  }
  var isTmb = function (x) { return !ksl.on && !keluar() && x.tambah && x.tambahKey === kunci(x); };

  function hariIni() {
    var d = new Date(), p = function (n) { return ('0' + n).slice(-2); };
    return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
  }
  function tglIndo(s) { var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s || ''); return m ? m[3] + '/' + m[2] + '/' + m[1] : s; }

  // Memeriksa isian lalu menampilkan konfirmasi ringkas sebelum benar-benar menyimpan.
  function simpan() {
    var pl = $.trim($('#tl-list').val()), pi = $.trim($('#tl-pinput').val()), tgl = $('#tl-tgl').val(), tglL = $('#tl-tgl-list').val();
    $('#tl-gal').text('');
    window.__tlList = pl; window.__tlInput = pi; window.__tlTgl = tgl; window.__tlTglList = tglL;
    if (!arah) { $('#tl-gal').text('Pilih arah: Masuk atau Keluar.'); $('#tl-arah').focus(); return; }
    if (!pl) { $('#tl-gal').text('Isi nama petugas list.'); $('#tl-list').focus(); return; }
    if (pl.toLowerCase() === TDK.toLowerCase() && !ksl.on) { $('#tl-gal').text('Petugas list "Tidak Diketahui" hanya untuk transfer yang ditandai sebagai kesalahan.'); $('#tl-list').focus(); return; }
    if (!pi) { $('#tl-gal').text('Isi nama petugas input.'); $('#tl-pinput').focus(); return; }
    if (!tglL) { $('#tl-gal').text('Isi tanggal list.'); $('#tl-tgl-list').focus(); return; }
    if (!tgl) { $('#tl-gal').text('Isi tanggal input.'); $('#tl-tgl').focus(); return; }
    if (tglL > tgl) { $('#tl-gal').text('Tanggal list tidak boleh setelah tanggal input.'); $('#tl-tgl-list').focus(); return; }
    var kirim = $.grep(baris, function (b) { return b.sku || b.rak || b.qty; });
    if (!kirim.length) { $('#tl-gal').text('Isi minimal satu baris.'); return; }
    var bad = $.grep(kirim, function (b) { return !b.sku || !b.rak || !(parseInt(b.qty, 10) > 0); });
    if (bad.length) { $('#tl-gal').text('Lengkapi SKU, no rak, dan qty (lebih dari 0) di semua baris yang diisi.'); return; }
    if (ksl.on && !ksl.pasangan) {
      if (!ksl.pihak.length) { $('#tl-gal').text('Pilih siapa yang salah: petugas list, petugas input, atau keduanya.'); return; }
      if (!ksl.jenis.length) { $('#tl-gal').text('Pilih minimal satu jenis kesalahan.'); return; }
      if (refNote() && !$.trim(ksl.ref)) { $('#tl-gal').text('Isi transfer asal yang dikoreksi.'); $('#tl-ksl-ref').addClass('bad').focus(); return; }
      if (ksl.ket.indexOf('…') > -1) { $('#tl-gal').text('Lengkapi nilai di bagian "Nilai untuk keterangan", atau ubah keterangannya.'); return; }
    }
    if (tandai() > 0) { $('#tl-gal').text(keluar() ? 'Ada baris bermasalah (merah). Hapus atau ubah baris itu.' : 'Ada baris duplikat (merah). Hapus atau ubah baris itu.'); return; }
    var q = 0;
    $.each(kirim, function (i, b) { q += parseInt(b.qty, 10); });
    var b = '<div class="tl-db"><dl class="tl-kv"><dt>Arah</dt><dd><b>' + (keluar() ? 'Keluar' : 'Masuk') + '</b></dd><dt>Petugas List</dt><dd>' + T.esc(pl) + ' · ' + tglIndo(tglL) + '</dd><dt>Petugas Input</dt><dd>' + T.esc(pi) + ' · ' + tglIndo(tgl) + '</dd><dt>Total</dt><dd><b>' + kirim.length + ' baris · ' + q + ' pcs</b></dd>' + kslDl() + '</dl>' +
      '<div class="tl-tw tl-panel" style="max-height:240px"><table class="tl-tbl"><thead><tr><th>SKU</th><th>No rak</th><th class="n">Qty</th></tr></thead><tbody>' +
      $.map(kirim, function (x) { return '<tr><td class="mono">' + T.esc(x.sku) + '</td><td class="mono">' + T.esc(String(x.rak).toUpperCase()) + (isTmb(x) ? ' ' + T.pill('penambahan stok', 'ok') : '') + '</td><td class="n">' + T.esc(x.qty) + '</td></tr>'; }).join('') +
      '</tbody></table></div></div><div class="tl-df"><button type="button" class="tl-btn" data-tl-tutup>Batal</button><button type="button" class="tl-btn pri" data-ya>Simpan</button></div>';
    var $m = T.modal({ judul: keluar() ? 'Simpan keluar top level?' : 'Simpan input top level?', body: b });
    $m.on('click', '[data-ya]', function () {
      if (sibuk) return;
      sibuk = true;
      $(this).prop('disabled', true);
      var rowsKirim = $.map(kirim, function (x) { return { sku: x.sku, rak: x.rak, qty: x.qty, tambah: isTmb(x) ? 1 : 0 }; });
      var koreksi = ksl.on ? (ksl.pasangan ? { aktif: 1, kesalahan_id: ksl.pasangan.id } : { aktif: 1, pihak: ksl.pihak, jenis: ksl.jenis, keterangan: ksl.ket, ref: $.trim(ksl.ref) }) : undefined;
      T.post('cs/input-top-level/simpan', { petugas_list: pl, petugas_input: pi, tanggal: tgl, tanggal_list: tglL, arah: arah, rows: rowsKirim, koreksi: koreksi }, function (d) {
        sibuk = false; T.closeModal(); selesai = d; selesai.arah = arah; arah = window.__tlArah = ''; ksl = KSL0(); paint();
      }, function (m2) { sibuk = false; T.closeModal(); $('#tl-gal').text(m2); cekDb(); });
    });
    $m.find('[data-ya]').trigger('focus');
  }

  $r.on('input', '.f-sku,.f-rak,.f-qty', function () {
    var $l = $(this).closest('.tl-line'), b = baris[+$l.data('i')];
    b.sku = $.trim($l.find('.f-sku').val());
    b.rak = $.trim($l.find('.f-rak').val());
    b.qty = $l.find('.f-qty').val();
    if ($(this).hasClass('f-rak')) b.auto = false;
    if ($(this).hasClass('f-sku')) autoRak(+$l.data('i'));
    ringkas();
    if (!$(this).hasClass('f-qty')) { tandai(); cekDb(); }
    else if (keluar()) tandai();
  });
  $r.on('keydown', '.f-qty', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    var i = +$(this).closest('.tl-line').data('i');
    if (i === baris.length - 1) { baris.push(KOSONG()); gambarBaris(); }
    $('#tl-baris .tl-line').eq(i + 1).find('.f-sku').focus();
  });
  $r.on('keydown', '.f-rak', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); $(this).closest('.tl-line').find('.f-qty').focus(); }
  });
  $r.on('click', '[data-tambah]', function () { baris.push(KOSONG()); gambarBaris(); $('#tl-baris .tl-line:last .f-sku').focus(); });
  $r.on('click', '[data-hapus]', function () { baris.splice(+$(this).closest('.tl-line').data('i'), 1); if (!baris.length) baris.push(KOSONG()); gambarBaris(); ringkas(); cekDb(); });
  $r.on('click', '[data-simpan]', simpan);
  $r.on('click', '#tl-list-tdk', function () { window.__tlList = TDK; $('#tl-list').val(TDK); $('.tl-combo-list').hide(); });
  $r.on('click', '[data-lagi]', function () { if (window.__tlList === TDK) window.__tlList = ''; selesai = null; ksl = KSL0(); baris = [KOSONG(), KOSONG(), KOSONG()]; paint(); });
  // Transfer pasangan: arah dibalik, catatan kesalahan yang sama dipakai ulang (satu kejadian = satu kesalahan).
  $r.on('click', '[data-pasangan]', function () {
    var s = selesai;
    ksl = KSL0(); ksl.on = true; ksl.pasangan = { id: s.kesalahan_id, kode: s.kesalahan };
    arah = window.__tlArah = s.arah === 'MASUK' ? 'KELUAR' : 'MASUK';
    selesai = null; baris = [KOSONG(), KOSONG(), KOSONG()]; paint();
  });
  // Tandai penambahan stok per baris: berlaku hanya untuk SKU + rak baris itu (tambahKey), hilang sendiri kalau SKU atau rak diganti.
  $r.on('change', '[data-tmb]', function () {
    var b = baris[+$(this).closest('.tl-line').data('i')];
    b.tambah = this.checked; b.tambahKey = kunci(b);
    tandai();
  });
  // Tandai sebagai kesalahan
  $r.on('change', '#tl-ksl-on', function () { ksl.on = this.checked; if (!ksl.on) ksl.pasangan = null; gambarKsl(); tandai(); });
  $r.on('change', '[data-k-pihak]', function () {
    ksl.pihak = $.map(['LIST', 'INPUT'], function (p) { return $('[data-k-pihak="' + p + '"]').prop('checked') ? p : null; });
    gambarKslDetail(); isiKet();
  });
  $r.on('change', '[data-k-jenis]', function () {
    ksl.jenis = $.map(JENIS, function (j) { return $('[data-k-jenis="' + j[0] + '"]').prop('checked') ? j[0] : null; });
    gambarKslDetail(); refNote(); isiKet();
  });
  $r.on('input', '[data-kfv]', function () { ksl.fv[$(this).data('kfv')] = this.value; isiKet(); });
  $r.on('input', '#tl-ksl-ref', function () { ksl.ref = this.value; $(this).removeClass('bad'); });
  $r.on('input', '#tl-ksl-ket', function () { ksl.ket = this.value; ksl.kotor = true; });
  $r.on('click', '[data-ksl-ulang]', function () { isiKet(true); });
  $r.on('click', '[data-ksl-lepas]', function () { ksl = KSL0(); gambarKsl(); tandai(); });
  $r.on('click', '[data-stok]', function () { $('a[href="cs/stok-top-level"]').first().trigger('click'); });
  $r.on('click', '[data-salin]', function () {
    var n = selesai.nomor;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(n).then(function () { T.toast('Nomor ' + n + ' disalin'); }, function () { T.toast(n); });
    } else T.toast(n);
  });

  paint();
})();
</script>
