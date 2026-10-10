<div id="urs-page">
<style>
/* Layout: kartu bertumpuk satu kolom, status diwakili pil/angka, bukan
   paragraf -- detail penjelasan cuma hidup di balik ikon (i) (hover/klik). */
#urs-page{
  --urs-bg-surface-2:#f8f9fb; --urs-border:#e2e4ea; --urs-muted:#6b7280;
  --urs-accent:#4f46e5; --urs-accent-weak:#eef0ff;
  --urs-success:#16794f; --urs-success-bg:#e8f7ef;
  --urs-danger:#b42318; --urs-danger-bg:#fdecec;
  --urs-warning:#92450d; --urs-warning-bg:#fef3e4;
  max-width:640px; margin:0 auto; display:flex; flex-direction:column; gap:14px;
  font-family:inherit; color:#1c1f2a;
}
#urs-page .mono{font-family:'Courier New',monospace; font-variant-numeric: tabular-nums;}
#urs-page .topbar{display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:2px;}
#urs-page .topbar h1{font-size:17px; font-weight:800; margin:0;}
#urs-page .badge-step{
  font-size:11px; font-weight:700; color:var(--urs-muted); text-transform:uppercase;
  letter-spacing:.06em; background:var(--urs-bg-surface-2); border:1px solid var(--urs-border);
  padding:4px 9px; border-radius:99px;
}
#urs-page .card{
  background:#fff; border:1px solid var(--urs-border); border-radius:14px;
  box-shadow:0 1px 2px rgba(20,22,35,.04), 0 8px 24px -12px rgba(20,22,35,.12);
  padding:18px; display:flex; flex-direction:column; gap:14px;
}
#urs-page .card-head{display:flex; align-items:center; justify-content:space-between; gap:8px;}
#urs-page .card-title{display:flex; align-items:center; gap:6px; font-size:14px; font-weight:700;}
#urs-page .card-title svg{width:16px; height:16px; flex:none;}

#urs-page .info{position:relative; display:inline-flex;}
#urs-page .info-btn{
  width:18px; height:18px; border-radius:50%; border:1px solid var(--urs-border);
  background:var(--urs-bg-surface-2); color:var(--urs-muted); font-size:11px; font-weight:800;
  display:inline-flex; align-items:center; justify-content:center; cursor:pointer;
  font-family:'Courier New',monospace; flex:none; padding:0;
}
#urs-page .info-btn:hover, #urs-page .info-btn.open{ background:var(--urs-accent-weak); color:var(--urs-accent); border-color:var(--urs-accent); }
#urs-page .info-pop{
  position:absolute; z-index:40; top:calc(100% + 8px); left:0;
  background:#fff; border:1px solid var(--urs-border); border-radius:10px;
  box-shadow:0 1px 2px rgba(20,22,35,.04), 0 8px 24px -12px rgba(20,22,35,.12); padding:10px 12px; font-size:12.5px; line-height:1.5;
  color:var(--urs-muted); width:max-content; max-width:260px;
  opacity:0; visibility:hidden; transform:translateY(-4px);
  transition:opacity .12s ease, transform .12s ease;
}
#urs-page .info-pop.list{min-width:260px; max-width:320px; padding:6px; max-height:220px; overflow-y:auto;}
#urs-page .info:hover .info-pop, #urs-page .info-pop.open{ opacity:1; visibility:visible; transform:translateY(0); }

#urs-page label.field-label{
  font-size:11px; font-weight:700; color:var(--urs-muted); text-transform:uppercase;
  letter-spacing:.05em; display:flex; align-items:center; gap:5px; margin-bottom:6px;
}
#urs-page .combo{position:relative;}
#urs-page .combo input{
  width:100%; border:1px solid var(--urs-border); background:var(--urs-bg-surface-2);
  color:#1c1f2a; border-radius:9px; padding:10px 12px; font-size:14px;
  font-weight:600; font-family:inherit;
}
#urs-page .combo input:focus{outline:2px solid var(--urs-accent); outline-offset:-1px; background:#fff;}
#urs-page .combo-chosen{
  display:none; align-items:center; gap:8px; border:1px solid var(--urs-border);
  background:var(--urs-accent-weak); border-radius:9px; padding:8px 10px;
}
#urs-page .combo-chosen.show{display:flex;}
#urs-page .combo-chosen .av{
  width:26px; height:26px; border-radius:50%; background:var(--urs-accent); color:#fff;
  display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; flex:none;
}
#urs-page .combo-chosen .nm{font-size:13.5px; font-weight:700; flex:1;}
#urs-page .combo-chosen button{border:none; background:transparent; color:var(--urs-muted); cursor:pointer; font-size:15px; line-height:1; padding:2px;}
#urs-page .combo-list{
  position:absolute; z-index:30; top:calc(100% + 4px); left:0; right:0;
  background:#fff; border:1px solid var(--urs-border); border-radius:10px;
  box-shadow:0 1px 2px rgba(20,22,35,.04), 0 8px 24px -12px rgba(20,22,35,.12); max-height:220px; overflow-y:auto; display:none;
}
#urs-page .combo-list.show{display:block;}
#urs-page .combo-item{display:flex; align-items:center; gap:9px; padding:8px 10px; cursor:pointer; font-size:13px;}
#urs-page .combo-item:hover, #urs-page .combo-item.hi{background:var(--urs-accent-weak);}
#urs-page .combo-item .no{font-family:'Courier New',monospace; font-weight:700; color:var(--urs-accent); font-size:11.5px; min-width:34px;}
#urs-page .combo-item .nm{font-weight:700; flex:1;}
#urs-page .combo-item .kosong{color:var(--urs-muted); font-weight:400;}

#urs-page .drop{
  display:flex; align-items:center; gap:9px;
  border:1.5px dashed var(--urs-border); border-radius:9px; padding:9px 12px;
  cursor:pointer; transition:border-color .12s ease, background .12s ease;
}
#urs-page .drop:hover, #urs-page .drop.dragover{border-color:var(--urs-accent); background:var(--urs-accent-weak);}
#urs-page .drop svg{width:17px; height:17px; color:var(--urs-muted); flex:none;}
#urs-page .drop .txt{min-width:0; flex:1;}
#urs-page .drop .t{font-size:13px; font-weight:700; line-height:1.3;}
#urs-page .drop .s{font-size:11px; color:var(--urs-muted); line-height:1.3;}
#urs-page .file-chip{
  display:none; align-items:center; gap:8px; border:1px solid var(--urs-border);
  background:var(--urs-bg-surface-2); border-radius:9px; padding:8px 10px;
}
#urs-page .file-chip.show{display:flex;}
#urs-page .file-chip svg{width:18px; height:18px; flex:none; color:var(--urs-accent);}
#urs-page .file-chip .nm{font-size:13px; font-weight:700; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;}
#urs-page .file-chip .sz{font-size:11px; color:var(--urs-muted);}
#urs-page .file-chip button{border:none; background:transparent; color:var(--urs-muted); cursor:pointer; font-size:15px; padding:2px;}

#urs-page .statusrow{display:flex; align-items:center; justify-content:space-between;}
#urs-page .pill{
  display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700;
  background:var(--urs-accent-weak); color:var(--urs-accent); border-radius:99px; padding:5px 11px;
  font-family:'Courier New',monospace;
}

#urs-page .btn{
  border:none; border-radius:9px; padding:10px 16px; font-size:13.5px; font-weight:700;
  cursor:pointer; font-family:inherit; display:inline-flex; align-items:center; gap:6px;
}
#urs-page .btn:active{transform:scale(.98);}
#urs-page .btn-primary{background:var(--urs-accent); color:#fff;}
#urs-page .btn-primary:disabled{opacity:.4; cursor:not-allowed;}
#urs-page .btn-ghost{background:transparent; color:var(--urs-muted);}
#urs-page .btn-ghost:hover{color:#1c1f2a;}
#urs-page .btn-row{display:flex; gap:8px; align-items:center;}
#urs-page .btn-block{width:100%; justify-content:center;}

#urs-page .stats{display:grid; grid-template-columns:repeat(3,1fr); gap:8px;}
#urs-page .stats.two{grid-template-columns:repeat(2,1fr);}
#urs-page .stat{border-radius:11px; padding:12px 10px; display:flex; flex-direction:column; gap:3px; position:relative;}
#urs-page .stat .n{font-size:22px; font-weight:800; font-family:'Courier New',monospace; line-height:1;}
#urs-page .stat .l{font-size:11px; font-weight:700;}
#urs-page .stat.ok{background:var(--urs-success-bg); color:var(--urs-success);}
#urs-page .stat.bad{background:var(--urs-danger-bg); color:var(--urs-danger);}
#urs-page .stat.dup{background:var(--urs-warning-bg); color:var(--urs-warning);}
#urs-page .stat .info{position:absolute; top:8px; right:8px;}
#urs-page .stat .info .info-btn{background:transparent; border-color:currentColor; color:inherit; opacity:.55;}
#urs-page .stat .info .info-btn:hover{opacity:1; background:rgba(255,255,255,.35);}

#urs-page .reason-row{display:flex; align-items:center; justify-content:space-between; gap:10px; padding:5px 4px; border-bottom:1px solid var(--urs-border); font-size:12px;}
#urs-page .reason-row:last-child{border-bottom:none;}
#urs-page .reason-row .rn{font-family:'Courier New',monospace; font-weight:700;}
#urs-page .reason-row .rr{color:var(--urs-muted); text-align:right;}

#urs-page .fade-in{animation:ursFadeIn .18s ease both;}
@keyframes ursFadeIn{from{opacity:0; transform:translateY(4px);} to{opacity:1; transform:translateY(0);}}
#urs-page [hidden]{display:none!important;}
</style>

<div class="topbar">
  <h1>Upload Resi Spesial</h1>
  <span class="badge-step" id="urs_step_badge">Langkah 1/3</span>
</div>

<div class="card" id="urs_card_input">
  <div class="card-head">
    <div class="card-title">
      Detail Batch
      <span class="info">
        <button class="info-btn" type="button" aria-label="Info batch">i</button>
        <span class="info-pop">Satu Nama Picker &amp; status performa berlaku untuk semua No Resi di file ini. Kolom lain di Excel (No Picklist, SKU, No Pesanan) diabaikan -- hanya kolom "No Resi" yang dibaca.</span>
      </span>
    </div>
  </div>

  <div>
    <label class="field-label">Nama Picker</label>
    <div class="combo">
      <input type="text" id="urs_picker_input" placeholder="Ketik no. absen atau nama…" autocomplete="off">
      <div class="combo-chosen" id="urs_picker_chosen">
        <div class="av" id="urs_picker_av"></div>
        <div style="flex:1">
          <div class="nm" id="urs_picker_nm"></div>
        </div>
        <span class="no mono" id="urs_picker_no"></span>
        <button type="button" id="urs_picker_clear" aria-label="Ganti picker">&times;</button>
      </div>
      <div class="combo-list" id="urs_picker_list"></div>
    </div>
    <input type="hidden" id="urs_id_pegawaipicker" value="">
  </div>

  <div class="statusrow">
    <label class="field-label" style="margin:0;">Status Performa</label>
    <span class="pill">
      1_SKU_PICKER
      <span class="info">
        <button class="info-btn" type="button" aria-label="Info status performa" style="width:15px;height:15px;font-size:10px;">i</button>
        <span class="info-pop">Dipasang otomatis, tidak bisa diubah di menu ini.</span>
      </span>
    </span>
  </div>

  <div>
    <label class="field-label">File Excel</label>
    <input type="file" id="urs_file_input" accept=".xlsx,.xls" hidden>
    <div class="drop" id="urs_dropzone">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 8l5-5 5 5"/><path d="M5 21h14"/></svg>
      <div class="txt">
        <div class="t">Pilih atau tarik file .xlsx</div>
        <div class="s">Wajib ada kolom &ldquo;No Resi&rdquo;</div>
      </div>
    </div>
    <div class="file-chip" id="urs_file_chip">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
      <span class="nm" id="urs_file_name"></span>
      <span class="sz" id="urs_file_size"></span>
      <button type="button" id="urs_file_clear" aria-label="Hapus file">&times;</button>
    </div>
  </div>

  <button class="btn btn-primary btn-block" id="urs_btn_validasi" disabled>Validasi</button>
</div>

<div class="card fade-in" id="urs_card_validasi" hidden>
  <div class="card-head">
    <div class="card-title">Hasil Validasi</div>
    <span class="mono" style="font-size:12px; color:var(--urs-muted);" id="urs_total_unik"></span>
  </div>

  <div class="stats">
    <div class="stat ok">
      <div class="n" id="urs_stat_valid_n">0</div>
      <div class="l">Valid</div>
    </div>
    <div class="stat bad">
      <div class="n" id="urs_stat_bad_n">0</div>
      <div class="l">Tidak Valid</div>
      <span class="info">
        <button class="info-btn" type="button" aria-label="Lihat yang tidak valid">i</button>
        <div class="info-pop list" id="urs_pop_bad"></div>
      </span>
    </div>
    <div class="stat dup">
      <div class="n" id="urs_stat_dup_n">0</div>
      <div class="l">Duplikat</div>
      <span class="info">
        <button class="info-btn" type="button" aria-label="Lihat duplikat">i</button>
        <div class="info-pop list" id="urs_pop_dup"></div>
      </span>
    </div>
  </div>

  <div class="btn-row">
    <button class="btn btn-primary" id="urs_btn_simpan">Lanjutkan Simpan <span id="urs_btn_simpan_n">0</span> Resi</button>
    <button class="btn btn-ghost" id="urs_btn_batal">Batal</button>
  </div>
</div>

<div class="card fade-in" id="urs_card_simpan" hidden>
  <div class="card-head">
    <div class="card-title">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16794f" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
      Tersimpan
    </div>
  </div>

  <div class="stats two">
    <div class="stat ok">
      <div class="n" id="urs_stat_ok_save_n">0</div>
      <div class="l">Berhasil</div>
    </div>
    <div class="stat bad">
      <div class="n" id="urs_stat_bad_save_n">0</div>
      <div class="l">Gagal</div>
      <span class="info">
        <button class="info-btn" type="button" aria-label="Lihat yang gagal">i</button>
        <div class="info-pop list" id="urs_pop_gagal_save"></div>
      </span>
    </div>
  </div>

  <div class="btn-row">
    <button class="btn btn-primary" id="urs_btn_cetak">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
      Cetak Ringkasan
    </button>
    <button class="btn btn-ghost" id="urs_btn_reset">Upload batch baru</button>
  </div>
</div>
</div>

<script type="text/javascript">
(function(){
  var ROSTER = <?= json_encode($roster_picker, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var MAKS_SARAN = 8;

  function esc(s){ return $('<div>').text(s == null ? '' : String(s)).html(); }

  var $pickerInput = $('#urs_picker_input');
  var $pickerList = $('#urs_picker_list');
  var $pickerChosen = $('#urs_picker_chosen');
  var $idPicker = $('#urs_id_pegawaipicker');
  var chosen = null;

  function cariPicker(q){
    q = (q || '').trim().toLowerCase();
    if (q === '') return ROSTER.slice(0, MAKS_SARAN);
    if (/^\d+$/.test(q)) {
      var angka = parseInt(q, 10);
      var persis = [], lain = [];
      $.each(ROSTER, function(_, p){
        if (parseInt(p.no_absen, 10) === angka) persis.push(p);
        else if (p.no_absen.indexOf(q) !== -1) lain.push(p);
      });
      return persis.concat(lain).slice(0, MAKS_SARAN);
    }
    return $.grep(ROSTER, function(p){
      return p.nama.toLowerCase().indexOf(q) !== -1;
    }).slice(0, MAKS_SARAN);
  }

  function renderPicker(q){
    var hasil = cariPicker(q);
    if (hasil.length === 0) {
      $pickerList.html('<div class="combo-item kosong">Tidak ada yang cocok</div>').addClass('show');
      $pickerList.data('hasil', []);
      return;
    }
    $pickerList.html($.map(hasil, function(p, i){
      return '<div class="combo-item" data-i="' + i + '"><span class="no mono">' + esc(p.no_absen) + '</span><span class="nm">' + esc(p.nama) + '</span></div>';
    }).join('')).addClass('show');
    $pickerList.data('hasil', hasil);
  }

  function pilihPicker(p){
    chosen = p;
    $('#urs_picker_av').text(p.nama.charAt(0).toUpperCase());
    $('#urs_picker_nm').text(p.nama);
    $('#urs_picker_no').text(p.no_absen);
    $idPicker.val(p.id_pegawai);
    $pickerChosen.addClass('show');
    $pickerInput.hide();
    $pickerList.removeClass('show');
    cekSiap();
  }

  $pickerInput.on('focus input', function(){ renderPicker($(this).val()); });
  $pickerInput.on('blur', function(){ setTimeout(function(){ $pickerList.removeClass('show'); }, 150); });
  $pickerList.on('mousedown', '.combo-item', function(e){
    var i = $(this).data('i');
    var hasil = $pickerList.data('hasil') || [];
    if (hasil[i] === undefined) return;
    e.preventDefault();
    pilihPicker(hasil[i]);
  });

  $('#urs_picker_clear').on('click', function(){
    chosen = null;
    $idPicker.val('');
    $pickerChosen.removeClass('show');
    $pickerInput.show().val('').focus();
    cekSiap();
  });

  // ---- file ----
  var fileTerpilih = null;
  var $dropzone = $('#urs_dropzone');
  var $fileInput = $('#urs_file_input');
  var $fileChip = $('#urs_file_chip');

  function formatUkuran(bytes){
    if (bytes >= 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    return Math.max(1, Math.round(bytes / 1024)) + ' KB';
  }

  function pakaiFile(file){
    if (!file) return;
    fileTerpilih = file;
    $('#urs_file_name').text(file.name);
    $('#urs_file_size').text(formatUkuran(file.size));
    $dropzone.hide();
    $fileChip.addClass('show');
    cekSiap();
  }

  $dropzone.on('click', function(){ $fileInput.trigger('click'); });
  $fileInput.on('change', function(){ pakaiFile(this.files[0]); });
  $dropzone.on('dragover', function(e){ e.preventDefault(); $(this).addClass('dragover'); });
  $dropzone.on('dragleave', function(){ $(this).removeClass('dragover'); });
  $dropzone.on('drop', function(e){
    e.preventDefault();
    $(this).removeClass('dragover');
    var f = e.originalEvent.dataTransfer.files[0];
    if (f) pakaiFile(f);
  });
  $('#urs_file_clear').on('click', function(e){
    e.stopPropagation();
    fileTerpilih = null;
    $fileInput.val('');
    $dropzone.show();
    $fileChip.removeClass('show');
    cekSiap();
  });

  function cekSiap(){
    $('#urs_btn_validasi').prop('disabled', !(chosen && fileTerpilih));
  }

  // ---- ikon info: hover (CSS) + klik mengunci terbuka ----
  $('#urs-page .info-btn').on('click', function(e){
    e.stopPropagation();
    var $pop = $(this).next('.info-pop');
    var terbuka = $pop.hasClass('open');
    $('#urs-page .info-pop.open').removeClass('open');
    $('#urs-page .info-btn.open').removeClass('open');
    if (!terbuka) { $pop.addClass('open'); $(this).addClass('open'); }
  });
  $(document).on('click', function(){
    $('#urs-page .info-pop.open').removeClass('open');
    $('#urs-page .info-btn.open').removeClass('open');
  });

  function isiDaftarAlasan($el, rows){
    $el.html($.map(rows, function(r){
      return '<div class="reason-row"><span class="rn">' + esc(r.noresi) + '</span><span class="rr">' + esc(r.alasan) + '</span></div>';
    }).join('') || '<div class="reason-row"><span class="rr">Tidak ada</span></div>');
  }

  // ---- langkah 1 -> validasi ----
  var noresiValidTerakhir = [];

  $('#urs_btn_validasi').on('click', function(){
    if (!chosen || !fileTerpilih) return;

    var formData = new FormData();
    formData.append('resiFile', fileTerpilih);

    var $btn = $(this);
    $btn.prop('disabled', true).text('Memvalidasi...');

    $.ajax({
      url: 'picker/validasi-upload-resi-spesial',
      type: 'post',
      data: formData,
      contentType: false,
      processData: false,
      timeout: 600000,
      success: function(res){
        if (typeof res === 'string') { try { res = JSON.parse(res); } catch(e) {} }
        if (!res || (res.code !== 200 && res.code !== 201)) {
          noty({ text: (res && res.message) || 'Validasi gagal', layout: 'topRight', type: 'error', timeout: 4000 });
          return;
        }

        var d = res.data || res;
        noresiValidTerakhir = d.valid || [];

        $('#urs_total_unik').text(d.total_unik + ' No Resi');
        $('#urs_stat_valid_n').text(d.total_valid);
        $('#urs_stat_bad_n').text(d.total_tidak_valid);
        $('#urs_stat_dup_n').text(d.total_duplikat || 0);

        isiDaftarAlasan($('#urs_pop_bad'), d.tidak_valid || []);
        isiDaftarAlasan($('#urs_pop_dup'), d.duplikat || []);

        $('#urs_btn_simpan_n').text(d.total_valid);
        $('#urs_btn_simpan').prop('disabled', d.total_valid === 0).css('opacity', d.total_valid === 0 ? .4 : 1);

        $('#urs_card_simpan').attr('hidden', true);
        $('#urs_card_validasi').attr('hidden', false);
        $('#urs_step_badge').text('Langkah 2/3');
      },
      error: function(xhr){
        var msg = 'Gagal menghubungi server';
        try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e) {}
        noty({ text: msg, layout: 'topRight', type: 'error', timeout: 4000 });
      },
      complete: function(){
        $btn.prop('disabled', false).text('Validasi');
        cekSiap();
      }
    });
  });

  $('#urs_btn_batal').on('click', function(){
    noresiValidTerakhir = [];
    $('#urs_card_validasi').attr('hidden', true);
    $('#urs_step_badge').text('Langkah 1/3');
  });

  // ---- langkah 3 -> cetak ringkasan hasil simpan ----
  var ringkasanTerakhir = null;

  function cetakRingkasan(r){
    if (!r) return;

    // Format sama dengan "Print Thermal" di menu Tim Picker -> Scan Resi
    // Picker (application/views/picker/scan_picker.php): kertas 100mm,
    // monospace, @page 100mm x 150mm, window 400x600 -- supaya hasil cetak
    // konsisten antar menu di printer thermal yang sama.
    // Isi sama dengan summary Scan Resi Picker: Rak / SKU / Qty, urut rak lalu
    // qty terbesar (agregat dihitung di Picker::simpan_upload_resi_spesial).
    var items = ($.extend(true, [], r.ringkasan || [])).sort(function(a, b){
      if (a.rak < b.rak) return -1;
      if (a.rak > b.rak) return 1;
      return b.qty - a.qty;
    });
    var totalQty = 0;
    var baris = $.map(items, function(it){
      totalQty += it.qty;
      return '<tr style="border-bottom: 1px dashed #ccc;">' +
        '<td style="padding: 5px 0;">' + (it.rak === 'NO_RAK' ? '-' : esc(it.rak)) + '</td>' +
        '<td style="padding: 5px 0;">' + esc(it.sku) + '</td>' +
        '<td style="text-align: right; padding: 5px 0; font-weight: bold;">' + it.qty + '</td>' +
      '</tr>';
    }).join('');

    var printContents = '' +
      '<div style="width: 100mm; font-family: monospace; font-size: 14px; padding: 5px;">' +
        '<h3 style="text-align: center; margin: 0 0 10px 0;">PICKING SUMMARY</h3>' +
        '<p style="margin: 0;">Picker : ' + esc(r.picker_nama) + '</p>' +
        '<p style="margin: 0;">Waktu  : ' + esc(r.waktu) + '</p>' +
        '<p style="margin: 0;">Total Resi: ' + r.total_berhasil + '</p>' +
        '<p style="margin: 0; margin-bottom: 10px; font-weight: bold; font-size: 16px;">Total Qty : ' + totalQty + ' <span style="font-weight: normal; font-size: 14px;">(' + items.length + ' SKU)</span></p>' +
        '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">' +
          '<thead><tr style="border-bottom: 1px solid #000; border-top: 1px solid #000;">' +
            '<th style="text-align: left; padding: 5px 0;">Rak</th>' +
            '<th style="text-align: left; padding: 5px 0;">SKU</th>' +
            '<th style="text-align: right; padding: 5px 0;">Qty</th>' +
          '</tr></thead>' +
          '<tbody>' + baris + '</tbody>' +
        '</table>' +
        '<p style="text-align: center;">--- END OF SUMMARY ---</p>' +
      '</div>';

    var printWindow = window.open('', '_blank', 'width=400,height=600');
    if (!printWindow) {
      noty({ text: 'Popup diblokir browser. Izinkan popup untuk mencetak ringkasan.', layout: 'topRight', type: 'warning', timeout: 4000 });
      return;
    }
    printWindow.document.write('<html><head><title>Print Summary</title>');
    printWindow.document.write('<style>@page { margin: 0; size: 100mm 150mm; } body { margin: 0; padding: 10px; background-color: #fff; }</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(printContents);
    printWindow.document.write('</body></html>');
    printWindow.document.close();

    printWindow.onload = function(){
      printWindow.focus();
      printWindow.print();
    };
  }

  $('#urs_btn_cetak').on('click', function(){ cetakRingkasan(ringkasanTerakhir); });

  // ---- langkah 2 -> simpan ----
  $('#urs_btn_simpan').on('click', function(){
    if (noresiValidTerakhir.length === 0) return;

    if (!confirm('Simpan ' + noresiValidTerakhir.length + ' resi sebagai di-picker oleh ' + chosen.nama + ' dengan status 1_SKU_PICKER?')) {
      return;
    }

    var $btn = $(this);
    $btn.prop('disabled', true).text('Menyimpan...');
    $('#urs_btn_batal').prop('disabled', true);

    $.ajax({
      url: 'picker/simpan-upload-resi-spesial',
      type: 'post',
      // Dikirim sebagai SATU field JSON: kalau array dikirim langsung, jQuery
      // memecahnya jadi 1 variabel POST per resi dan PHP (max_input_vars=1000)
      // diam-diam membuang semua yang di atas 1000.
      data: {
        id_pegawaipicker: $idPicker.val(),
        list_noresi: JSON.stringify(noresiValidTerakhir),
        jumlah_dikirim: noresiValidTerakhir.length
      },
      timeout: 600000,
      success: function(res){
        if (typeof res === 'string') { try { res = JSON.parse(res); } catch(e) {} }
        if (!res || (res.code !== 200 && res.code !== 201)) {
          noty({ text: (res && res.message) || 'Simpan gagal', layout: 'topRight', type: 'error', timeout: 4000 });
          return;
        }

        var d = res.data || res;
        $('#urs_stat_ok_save_n').text(d.total_berhasil);
        $('#urs_stat_bad_save_n').text(d.total_gagal);
        isiDaftarAlasan($('#urs_pop_gagal_save'), d.gagal || []);

        ringkasanTerakhir = $.extend({}, d, {
          picker_nama: chosen.nama,
          picker_no_absen: chosen.no_absen,
          waktu: new Date().toLocaleString('id-ID')
        });

        $('#urs_card_validasi').attr('hidden', true);
        $('#urs_card_simpan').attr('hidden', false);
        $('#urs_step_badge').text('Langkah 3/3');

        noty({ text: 'Berhasil menyimpan ' + d.total_berhasil + ' resi', layout: 'topRight', type: 'success', timeout: 3000 });
        noresiValidTerakhir = [];
      },
      error: function(xhr){
        var msg = 'Gagal menghubungi server';
        try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e) {}
        noty({ text: msg, layout: 'topRight', type: 'error', timeout: 4000 });
      },
      complete: function(){
        $btn.prop('disabled', false).html('Lanjutkan Simpan <span id="urs_btn_simpan_n">0</span> Resi');
        $('#urs_btn_batal').prop('disabled', false);
      }
    });
  });

  $('#urs_btn_reset').on('click', function(){
    ringkasanTerakhir = null;
    $('#urs_card_simpan').attr('hidden', true);
    $('#urs_card_validasi').attr('hidden', true);
    $('#urs_step_badge').text('Langkah 1/3');
    $('#urs_file_clear').trigger('click');
    $('#urs_picker_clear').trigger('click');
  });
})();
</script>
