<?php
// View menu TIM CS -> Stok Top Level. Dimuat lewat AJAX (SPA).
// Satu kali muat semua SKU beserta rak-raknya; cari, urut, filter, dan halaman diproses di browser.
// Dua tampilan: Per SKU dan Per Rak. Baris induk bisa dibuka untuk melihat rincian; dari rincian bisa Edit
// (qty per rak, ganti no rak, ganti SKU yang harus ada di master Jubelio) dan Histori.
if (!empty($akses_ditolak)) : ?>
<div class="alert alert-danger"><i class="fa fa-lock"></i> Role akun Anda tidak punya akses ke menu Stok Top Level. Minta admin membukanya lewat menu Access.</div>
<?php return; endif;
$this->load->view('top_level/_dasar'); ?>
<div class="tl" id="tl-stok"></div>
<script>
(function () {
  var T = window.TL, $r = $('#tl-stok');
  var TIPIS = 20;                                   // stok total <= angka ini ditandai Tipis
  var ALASAN = ['Opname', 'Salah catat', 'Barang rusak', 'Pindah rak'];
  var data = [], v = 'sku', q = '', filt = '', tab = 'semua', sortK = 'sku', sortD = 1, page = 0, ps = 10, bukaSku = null, bukaRak = null, timer = null;

  var SV = {
    box: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"><path d="M12 3 4 7v10l8 4 8-4V7z"/><path d="m4 7 8 4 8-4M12 11v10"/></svg>',
    cp: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h9"/></svg>',
    clk: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    pen: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h4L19 9l-4-4L4 16z"/></svg>',
    chv: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>'
  };

  $r.html(
    '<div class="tl-ph"><div class="t"><h1>Stok Top Level</h1>' +
    T.info('Stok top level per SKU dan per rak.\nKlik baris untuk melihat rincian, lalu Edit untuk koreksi qty, ganti no rak, atau ganti SKU.\nSKU baru wajib ada di master (Jubelio).\nStok bertambah lewat Upload Top Level dan berkurang saat CS input qty dari slip.') +
    '</div></div><div class="tls-seg" id="tls-tab" style="margin-bottom:10px"></div><div class="tls-kpi" id="tls-kpi"></div>' +
    '<section class="tl-panel tls-panel"><div class="tls-tb">' +
    '<label class="tl-search tls-cari">' + T.ic('search') + '<input type="text" id="tl-q" placeholder="Cari SKU atau nomor rak" autocomplete="off"></label>' +
    '<div class="tls-seg" id="tls-seg"><button type="button" class="on" data-v="sku">Per SKU</button><button type="button" data-v="rak">Per Rak</button></div>' +
    '<span class="tls-sp"></span><span class="tls-cnt" id="tls-cnt"></span>' +
    '<label class="tls-cnt">Per halaman <select id="tls-ps"><option>10</option><option>50</option><option>100</option></select></label>' +
    '<button type="button" class="tl-btn tls-xls" id="tls-xls" title="Unduh semua stok top level (SKU, no rak, stok)">' + T.ic('download', 18) + 'Export Excel</button></div>' +
    '<div id="tls-tbl"></div><div class="tls-pg"><span id="tls-info"></span><span><button type="button" class="tl-btn sm" id="tls-prev">&lsaquo;</button> <button type="button" class="tl-btn sm" id="tls-next">&rsaquo;</button></span></div></section>'
  );

  function cari(sku) { for (var i = 0; i < data.length; i++) if (data[i].sku === sku) return data[i]; return null; }

  function salin(t) {
    function ok() { T.toast('Tersalin: ' + t); }
    function cadangan() {
      var a = document.createElement('textarea');
      a.value = t; a.style.position = 'fixed'; a.style.opacity = '0';
      document.body.appendChild(a); a.select();
      try { document.execCommand('copy'); ok(); } catch (e) { T.galat('Gagal menyalin'); }
      document.body.removeChild(a);
    }
    if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(t).then(ok, cadangan); else cadangan();
  }

  // ---------- kartu ringkasan ----------
  function lolosTab(d) { return tab === 'ada' ? d.total > 0 : tab === 'kosong' ? d.total <= 0 : true; }

  function tabs() {
    var n = { semua: data.length, ada: 0, kosong: 0 };
    $.each(data, function (i, d) { if (d.total > 0) n.ada++; else n.kosong++; });
    var def = [['semua', 'Semua'], ['ada', 'Ada stok'], ['kosong', 'Stok kosong']], h = '';
    $.each(def, function (i, t) { h += '<button type="button" data-t="' + t[0] + '" class="' + (tab === t[0] ? 'on' : '') + '">' + t[1] + ' (' + n[t[0]].toLocaleString('id-ID') + ')</button>'; });
    $('#tls-tab').html(h);
  }

  function kpi() {
    var unit = 0, rk = {}, tipis = 0;
    $.each($.grep(data, lolosTab), function (i, d) { unit += d.total; if (d.total <= TIPIS) tipis++; $.each(d.rak, function (j, z) { if (z.stok > 0) rk[z.rak] = 1; }); });
    $('#tls-kpi').html(
      '<div class="tls-k act' + (filt === '' ? ' on' : '') + '" data-k=""><small>Total SKU</small><b>' + $.grep(data, lolosTab).length + '</b></div>' +
      '<div class="tls-k"><small>Total stok</small><b>' + unit.toLocaleString('id-ID') + '</b></div>' +
      '<div class="tls-k"><small>Rak terpakai</small><b>' + Object.keys(rk).length + '</b></div>' +
      '<div class="tls-k act w' + (filt === 'low' ? ' on' : '') + '" data-k="low"><small>Stok tipis (&le;' + TIPIS + ')</small><b>' + tipis + '</b></div>');
  }

  function rowsSku() {
    var k = q.toLowerCase();
    var L = $.grep(data, function (d) {
      if (!lolosTab(d)) return false;
      if (filt === 'low' && d.total > TIPIS) return false;
      if (!k) return true;
      if (d.sku.toLowerCase().indexOf(k) > -1) return true;
      for (var i = 0; i < d.rak.length; i++) if (d.rak[i].rak.toLowerCase().indexOf(k) > -1) return true;
      return false;
    });
    L.sort(function (a, b) {
      var x = sortK === 'sku' ? a.sku : sortK === 'tot' ? a.total : a.rak.length, y = sortK === 'sku' ? b.sku : sortK === 'tot' ? b.total : b.rak.length;
      return (x > y ? 1 : x < y ? -1 : 0) * sortD;
    });
    return L;
  }

  function rowsRak() {
    var m = {}, k = q.toLowerCase(), out = [];
    $.each(data, function (i, d) {
      if (!lolosTab(d)) return;
      if (filt === 'low' && d.total > TIPIS) return;
      $.each(d.rak, function (j, z) {
        if (z.stok <= 0 && tab !== 'kosong') return;
        if (!m[z.rak]) m[z.rak] = { rak: z.rak, sku: [], unit: 0 };
        m[z.rak].sku.push({ sku: d.sku, stok: z.stok, total: d.total });
        m[z.rak].unit += z.stok;
      });
    });
    $.each(m, function (rak, x) {
      if (k && rak.toLowerCase().indexOf(k) === -1) {
        var ada = false;
        $.each(x.sku, function (i, s) { if (s.sku.toLowerCase().indexOf(k) > -1) ada = true; });
        if (!ada) return;
      }
      out.push(x);
    });
    out.sort(function (a, b) { return a.rak < b.rak ? -1 : a.rak > b.rak ? 1 : 0; });
    return out;
  }

  function th(k, teks, c) {
    return '<div class="tls-gh ' + (c || '') + (k ? ' srt' : '') + '"' + (k ? ' data-s="' + k + '"' : '') + '>' + teks + (sortK === k && k ? (sortD > 0 ? ' &#9650;' : ' &#9660;') : '') + '</div>';
  }
  function salinIkon(t, judul) { return ' <i class="tls-cp" data-cp="' + T.esc(t) + '" title="' + judul + '">' + SV.cp + '</i>'; }
  function aksi(sku, rak) {
    return '<div class="c n"><button type="button" class="tls-ab" data-e="' + T.esc(sku) + '">' + SV.pen + ' Edit</button> <button type="button" class="tls-ab" data-h="' + T.esc(sku) + '" data-rak="' + T.esc(rak) + '">' + SV.clk + ' Histori</button></div>';
  }
  function strip(attr, kunci, label, terbuka) {
    var t = terbuka ? 'Tutup' : 'Lihat rincian';
    return '<div class="tls-strip" ' + attr + '="' + T.esc(kunci) + '"><span>' + label + '</span><b class="tls-tg' + (terbuka ? ' up' : '') + '" title="' + t + '" aria-label="' + t + '">' + SV.chv + '</b></div>';
  }

  function gambar() {
    tabs();
    kpi();
    var h, semua, L;
    if (v === 'sku') {
      semua = rowsSku();
      L = semua.slice(page * ps, page * ps + ps);
      h = '<div class="tls-gt"><div class="tls-gr tls-gp hd">' + th('sku', 'Produk (SKU)') + th('rk', 'Rak', 'n') + th('tot', 'Total stok', 'n') + th('', 'Status', 'n') + '</div>';
      $.each(L, function (i, d) {
        var o = bukaSku === d.sku;
        h += '<div class="tls-grp"><div class="tls-gr tls-gp prow" data-o="' + T.esc(d.sku) + '"><div class="pn"><span class="pic">' + SV.box + '</span><div><b class="mono">' + T.esc(d.sku) + '</b>' + salinIkon(d.sku, 'Salin SKU') + '</div></div>' +
          '<div class="c n">' + d.rak.length + '</div><div class="c n"><b>' + d.total + '</b></div><div class="c n">' + (d.total <= 0 ? T.pill('Kosong', 'bad') : d.total <= TIPIS ? T.pill('Tipis', 'warn') : T.pill('Aman', 'ok')) + '</div></div>' +
          strip('data-o', d.sku, d.rak.length + ' rak', o);
        if (o) {
          h += '<div class="tls-nest"><div class="tls-gr tls-gn hd"><div class="tls-gh">Rak</div><div class="tls-gh n">Stok</div><div class="tls-gh"></div><div class="tls-gh n">Aksi</div></div>';
          $.each(d.rak, function (j, z) {
            h += '<div class="tls-gr tls-gn"><div class="c"><b class="mono">' + T.esc(z.rak) + '</b>' + salinIkon(z.rak, 'Salin no rak') + '</div><div class="c n">' + z.stok + '</div><div class="c"></div>' + aksi(d.sku, z.rak) + '</div>';
          });
          if (!d.rak.length) h += '<div class="tls-gr tls-gn"><div class="c muted">Tidak ada stok di rak mana pun</div><div class="c n"></div><div class="c"></div><div class="c n"></div></div>';
          h += '<div class="tls-gr tls-gn"><div class="c"></div><div class="c n"></div><div class="c"></div><div class="c n"><button type="button" class="tls-ab" data-b="' + T.esc(d.sku) + '">' + SV.clk + ' Histori SKU</button></div></div>';
          h += '</div>';
        }
        h += '</div>';
      });
      h += '</div>';
    } else {
      semua = rowsRak();
      L = semua.slice(page * ps, page * ps + ps);
      h = '<div class="tls-gt"><div class="tls-gr tls-gp hd">' + th('', 'No rak') + th('', 'Jumlah SKU', 'n') + th('', 'Total stok', 'n') + th('', '', 'n') + '</div>';
      $.each(L, function (i, x) {
        var o = bukaRak === x.rak;
        h += '<div class="tls-grp"><div class="tls-gr tls-gp prow" data-or="' + T.esc(x.rak) + '"><div class="pn"><span class="pic">' + SV.box + '</span><div><b class="mono">' + T.esc(x.rak) + '</b>' + salinIkon(x.rak, 'Salin no rak') + '</div></div>' +
          '<div class="c n">' + x.sku.length + '</div><div class="c n"><b>' + x.unit + '</b></div><div class="c n"></div></div>' + strip('data-or', x.rak, x.sku.length + ' SKU', o);
        if (o) {
          h += '<div class="tls-nest"><div class="tls-gr tls-gn hd"><div class="tls-gh">SKU</div><div class="tls-gh n">Stok di rak ini</div><div class="tls-gh n">Total stok SKU</div><div class="tls-gh n">Aksi</div></div>';
          $.each(x.sku, function (j, z) {
            h += '<div class="tls-gr tls-gn"><div class="c"><b class="mono">' + T.esc(z.sku) + '</b>' + salinIkon(z.sku, 'Salin SKU') + '</div><div class="c n">' + z.stok + '</div><div class="c n">' + z.total + '</div>' + aksi(z.sku, x.rak) + '</div>';
          });
          h += '</div>';
        }
        h += '</div>';
      });
      h += '</div>';
    }
    $('#tls-tbl').html(semua.length ? h : T.empty('db', data.length ? 'Tidak ada data yang cocok' : 'Belum ada stok top level. Unggah Excel di menu Upload Top Level.'));
    $('#tls-cnt').text(semua.length + (v === 'sku' ? ' SKU' : ' rak'));
    $('#tls-info').text(semua.length ? (page * ps + 1) + '–' + Math.min(semua.length, page * ps + ps) + ' dari ' + semua.length : '');
    $('#tls-prev').prop('disabled', page === 0);
    $('#tls-next').prop('disabled', (page + 1) * ps >= semua.length);
  }

  function muat() {
    T.post('cs/stok-top-level/data', { q: '' }, function (d) { data = d.rows; gambar(); }, T.galat);
  }

  // ---------- histori ----------
  function histori(sku, rak) {
    T.post('cs/stok-top-level/riwayat', { sku: sku, rak: rak }, function (d) {
      var b = '<div class="tl-db"><div class="mono" style="font-size:16px;font-weight:600">' + T.esc(sku) + ' &middot; ' + T.esc(rak) + '</div>';
      if (!d.rows.length) b += T.empty('db', 'Belum ada riwayat');
      else {
        b += '<div class="tl-tw"><table class="tl-tbl"><thead><tr><th>Waktu</th><th>Keterangan</th><th class="n">Perubahan</th><th class="n">Stok</th></tr></thead><tbody>';
        $.each(d.rows, function (i, x) {
          var s = x.selisih;
          b += '<tr><td class="mono">' + T.esc(T.tgl(x.waktu)) + '</td><td>' + T.esc(x.keterangan || x.jenis) + '<div class="muted" style="font-size:12px">' + T.esc(x.oleh || '') + '</div></td>' +
            '<td class="n"><b style="color:' + (s > 0 ? 'var(--tl-ok)' : s < 0 ? 'var(--tl-bad)' : 'inherit') + '">' + (s > 0 ? '+' : '') + s + '</b></td><td class="n">' + x.qty_sebelum + ' &rarr; ' + x.qty_sesudah + '</td></tr>';
        });
        b += '</tbody></table></div>';
      }
      var $m = T.modal({ judul: 'Histori stok', wide: true, body: b + '</div><div class="tl-df"><button type="button" class="tl-btn" data-tl-tutup>Tutup</button></div>' });
    }, T.galat);
  }

  // ---------- histori SKU: masuk, keluar, saldo (semua rak) ----------
  function buku(sku) {
    T.post('cs/stok-top-level/buku', { sku: sku }, function (d) {
      var b = '<div class="tl-db"><div class="mono" style="font-size:16px;font-weight:600">' + T.esc(sku) + ' &middot; saldo sekarang ' + d.total + '</div>';
      if (!d.rows.length) b += T.empty('db', 'Belum ada pencatatan masuk atau keluar');
      else {
        b += '<div class="tl-tw"><table class="tl-tbl"><thead><tr><th>No. Transfer</th><th>Keterangan</th><th>List</th><th>Input</th><th class="n">Masuk</th><th class="n">Keluar</th><th class="n">Saldo</th></tr></thead><tbody>';
        var dua = function (nama, waktu) { return nama ? '<div class="tl-cell2"><span>' + T.esc(nama) + '</span><small>' + (waktu ? T.esc(T.tgl(waktu)) : '') + '</small></div>' : '<span class="muted">&mdash;</span>'; };
        $.each(d.rows, function (i, x) {
          b += '<tr><td class="mono">' + (x.nomor ? T.esc(x.nomor) : '<span class="muted">&mdash;</span>') + '</td><td>' + T.esc(x.keterangan || x.jenis) + '<div class="muted" style="font-size:12px">' + (x.no_rak ? 'Rak ' + T.esc(x.no_rak) : '') + '</div></td>' +
            '<td>' + dua(x.list_nama, x.list_tgl) + '</td><td>' + dua(x.input_nama, x.input_waktu) + '</td>' +
            '<td class="n">' + (x.masuk ? '<b style="color:var(--tl-ok)">+' + x.masuk + '</b>' : '') + '</td><td class="n">' + (x.keluar ? '<b style="color:var(--tl-bad)">-' + x.keluar + '</b>' : '') + '</td><td class="n"><b>' + x.saldo + '</b></td></tr>';
        });
        b += '</tbody></table></div>' + (d.lebih ? '<div class="muted" style="font-size:12px">Menampilkan 300 catatan terbaru.</div>' : '');
      }
      T.modal({ judul: 'Histori SKU', wide: true, body: b + '</div><div class="tl-df"><button type="button" class="tl-btn" data-tl-tutup>Tutup</button></div>' });
    }, T.galat);
  }

  // ---------- edit: qty per rak, no rak, SKU ----------
  function edit(sku) {
    var d = cari(sku);
    if (!d) return;
    var ed = { sku: sku, skuBaru: sku, skuCek: 'ok', rows: $.map(d.rak, function (z) { return { asal: z.rak, rak: z.rak, lama: z.stok, qty: String(z.stok) }; }) };
    var $m = T.modal({ judul: 'Edit stok top level', body: '' });

    function qtyOk(x) { return /^\d+$/.test($.trim(x.qty)); }
    function kosong(x) { return x.asal === '' && $.trim(x.rak) === '' && $.trim(x.qty) === ''; }
    function kotor() {
      if (ed.skuBaru !== ed.sku) return true;
      var k = false;
      $.each(ed.rows, function (i, x) {
        if (kosong(x)) return;
        if (x.asal === '' || +x.qty !== x.lama || $.trim(x.rak).toUpperCase() !== x.asal) k = true;
      });
      return k;
    }
    function boleh() {
      if (!kotor()) return false;
      if (ed.skuBaru !== ed.sku && ed.skuCek !== 'ok') return false;
      var ok = true;
      $.each(ed.rows, function (i, x) { if (!kosong(x) && ($.trim(x.rak) === '' || !qtyOk(x))) ok = false; });
      return ok;
    }
    function status() {
      if (ed.skuBaru === ed.sku) return '';
      if (ed.skuCek === 'cek') return '<span class="muted">Memeriksa master&hellip;</span>';
      if (ed.skuCek !== 'ok') return '<span style="color:var(--tl-bad)">&#10007; SKU tidak ada di master Jubelio</span>';
      return cari(ed.skuBaru) ? '<span style="color:var(--tl-warn)">&#9888; SKU ini sudah punya stok top level, rak akan digabung</span>' : '<span style="color:var(--tl-ok)">&#10003; Cocok dengan master Jubelio</span>';
    }
    function ringkas() {
      var lama = 0, baru = 0;
      $m.find('.tls-er').each(function () {
        var x = ed.rows[+$(this).data('i')];
        if (x.lama != null) lama += x.lama;
        baru += qtyOk(x) ? +x.qty : 0;
        $(this).find('.tls-qty').toggleClass('chg', !kosong(x) && (x.asal === '' || +x.qty !== x.lama));
        var rn = $.trim(x.rak).toUpperCase();
        $(this).find('.tls-rn').toggleClass('chg', x.asal !== '' && rn !== x.asal);
        $(this).find('.tls-dari').text(x.asal === '' ? 'rak baru' : (rn !== x.asal ? 'dari ' + x.asal + ' · ' : '') + 'sebelumnya ' + x.lama);
      });
      var sel = baru - lama;
      $m.find('#tls-sum').html('Total: ' + lama + ' &rarr; <b>' + baru + '</b> <span class="muted">(' + (sel > 0 ? '+' : '') + sel + ')</span>');
      $m.find('#tls-skst').html(status());
      $m.find('[data-simpan]').prop('disabled', !boleh());
    }
    function periksaSku(teks) {
      ed.skuBaru = teks;
      if (teks === ed.sku) { ed.skuCek = 'ok'; ringkas(); return; }
      ed.skuCek = teks === '' ? 'tidak' : 'cek';
      ringkas();
    }
    function render() {
      var b = '<div class="tl-db"><label class="tls-fl">SKU (harus sesuai master Jubelio)</label><div class="tls-skubox"><input type="text" id="tls-sk" class="mono tls-skin" value="' + T.esc(ed.skuBaru) + '" autocomplete="off"></div><div id="tls-skst" class="tls-skst"></div>' +
        '<label class="tls-fl" style="margin-top:6px">Rak dan stok</label>';
      $.each(ed.rows, function (i, x) {
        b += '<div class="tls-er" data-i="' + i + '"><div><input type="text" class="tls-rn mono" value="' + T.esc(x.rak) + '" placeholder="No rak baru, mis. TL-F3"><small class="tls-dari"></small></div><input type="number" min="0" class="tls-qty" value="' + T.esc(x.qty) + '"></div>';
      });
      b += '<div><button type="button" class="tl-btn sm ghost" data-rak>' + T.ic('plus', 14) + 'Tambah rak</button></div>' +
        '<div class="tl-fld">Alasan<select id="tl-alasan">' + $.map(ALASAN, function (a) { return '<option' + (a === ed.alasan ? ' selected' : '') + '>' + a + '</option>'; }).join('') + '</select></div></div>' +
        '<div class="tl-df"><span id="tls-sum" class="tls-sum"></span><button type="button" class="tl-btn" data-tl-tutup>Batal</button><button type="button" class="tl-btn pri" data-simpan disabled>Simpan</button></div>';
      $m.find('.tl-body').html(b);
      T.combo($m.find('#tls-sk'), function (teks, kirim) {
        var cek = $.trim(teks).toUpperCase();
        T.post('cs/stok-top-level/sku', { q: teks }, function (res) {
          var ada = false;
          $.each(res.rows, function (i, r) { if (String(r.sku).toUpperCase() === ed.skuBaru.toUpperCase()) { ada = true; if (cek === ed.skuBaru.toUpperCase()) ed.skuBaru = r.sku; } });
          if (ed.skuBaru !== ed.sku && cek === ed.skuBaru.toUpperCase()) { ed.skuCek = ada ? 'ok' : 'tidak'; ringkas(); }
          kirim($.map(res.rows, function (r) { return { nilai: r.sku, label: r.sku }; }));
        }, function () { kirim([]); });
      }, function (it) { ed.skuBaru = it.nilai; ed.skuCek = it.nilai === ed.sku ? 'ok' : 'ok'; ringkas(); });
      ringkas();
    }

    $m.on('input', '#tls-sk', function () { periksaSku($.trim(this.value).toUpperCase()); });
    $m.on('input', '.tls-rn', function () { ed.rows[+$(this).closest('.tls-er').data('i')].rak = this.value; ringkas(); });
    $m.on('input', '.tls-qty', function () { ed.rows[+$(this).closest('.tls-er').data('i')].qty = this.value; ringkas(); });
    $m.on('change', '#tl-alasan', function () { ed.alasan = this.value; });
    $m.on('click', '[data-rak]', function () {
      ed.alasan = $m.find('#tl-alasan').val();
      ed.rows.push({ asal: '', rak: '', lama: null, qty: '' });
      render();
      $m.find('.tls-rn').last().focus();
    });
    $m.on('click', '[data-simpan]', function () {
      var kirim = [];
      $.each(ed.rows, function (i, x) { if (!kosong(x)) kirim.push({ asal: x.asal, rak: $.trim(x.rak), qty: $.trim(x.qty) }); });
      $(this).prop('disabled', true);
      T.post('cs/stok-top-level/simpan', { sku: ed.sku, sku_baru: ed.skuBaru, alasan: $m.find('#tl-alasan').val(), rows: kirim }, function (r, j) {
        T.closeModal(); T.toast(j.message);
        if (bukaSku === ed.sku) bukaSku = ed.skuBaru;
        muat();
      }, function (m) { T.galat(m); ringkas(); });
    });
    render();
  }

  // ---------- peristiwa ----------
  $r.on('click', '[data-cp]', function (e) { e.stopPropagation(); salin($(this).data('cp')); });
  $r.on('click', '[data-e]', function (e) { e.stopPropagation(); edit($(this).data('e')); });
  $r.on('click', '[data-h]', function (e) { e.stopPropagation(); histori($(this).data('h'), $(this).data('rak')); });
  $r.on('click', '[data-b]', function (e) { e.stopPropagation(); buku($(this).data('b')); });
  $r.on('click', '[data-o]', function () { var s = $(this).attr('data-o'); bukaSku = bukaSku === s ? null : s; gambar(); });
  $r.on('click', '[data-or]', function () { var s = $(this).attr('data-or'); bukaRak = bukaRak === s ? null : s; gambar(); });
  $r.on('click', '[data-s]', function () { var k = $(this).data('s'); if (sortK === k) sortD *= -1; else { sortK = k; sortD = 1; } page = 0; gambar(); });
  $r.on('click', '#tls-tab button', function () { tab = $(this).data('t'); page = 0; gambar(); });
  $r.on('click', '[data-k]', function () { filt = $(this).data('k'); page = 0; gambar(); });
  $r.on('click', '#tls-seg button', function () { v = $(this).data('v'); page = 0; $('#tls-seg button').removeClass('on'); $(this).addClass('on'); gambar(); });
  $r.on('click', '#tls-xls', function () {
    // POST lewat form tersembunyi supaya respons file diunduh browser tanpa meninggalkan halaman.
    var f = $('<form method="post" class="nojs" action="cs/stok-top-level/export" style="display:none"></form>').appendTo('body');
    f.trigger('submit'); setTimeout(function () { f.remove(); }, 1000);
  });
  $r.on('click', '#tls-prev', function () { page--; gambar(); });
  $r.on('click', '#tls-next', function () { page++; gambar(); });
  $r.on('change', '#tls-ps', function () { ps = +this.value; page = 0; gambar(); });
  $r.on('input', '#tl-q', function () { var s = this.value; clearTimeout(timer); timer = setTimeout(function () { q = $.trim(s); page = 0; gambar(); }, 200); });
  muat();
  $('#tl-q').trigger('focus');                       // kursor langsung aktif di kolom cari
})();
</script>
