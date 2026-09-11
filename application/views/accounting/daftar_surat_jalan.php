<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
.sj-page-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #334155 100%);
    border-radius: 16px; padding: 22px 28px; margin-bottom: 20px; color: #fff;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
    box-shadow: 0 4px 20px rgba(15,23,42,0.15);
}
.sj-page-header .header-title { font-size: 20px; font-weight: 800; font-family: 'Outfit', sans-serif; }
.sj-page-header .header-sub { font-size: 13px; opacity: 0.8; margin-top: 4px; }

.sj-stats-row { display: flex; gap: 14px; margin-bottom: 18px; flex-wrap: wrap; }
.sj-stat-card { flex: 1; min-width: 170px; background: #fff; border: 1px solid #eef0f3; border-radius: 12px; padding: 16px 20px; box-shadow: 0 1px 3px rgba(15,23,42,0.05); border-left: 5px solid #94a3b8; }
.sj-stat-card.blue { border-left-color: #3b82f6; } .sj-stat-card.yellow { border-left-color: #eab308; } .sj-stat-card.green { border-left-color: #22c55e; }
.sj-stat-card .sc-label { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.sj-stat-card .sc-value { font-size: 26px; font-weight: 800; color: #0f172a; margin-top: 4px; }

.sj-toolbar { background:#fff; border:1px solid #eef0f3; border-radius:12px 12px 0 0; border-bottom:none; padding:12px 16px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.sj-tabs { display:flex; gap:6px; }
.sj-tab { padding:8px 16px; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; border:1px solid #e2e8f0; background:#f8fafc; color:#475569; }
.sj-tab.active { background:#2563eb; color:#fff; border-color:#2563eb; }
.sj-filters { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.sj-filters .form-control { height:36px; }

.sj-pane { background:#fff; border:1px solid #eef0f3; border-radius:0 0 12px 12px; box-shadow:0 1px 3px rgba(15,23,42,0.05); padding:8px 12px 14px; }
.text-mono { font-family:'Courier New', monospace; font-size:12px; background:#f1f5f9; padding:2px 6px; border-radius:4px; color:#475569; font-weight:600; }

.doc-status-badge { display:inline-block; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.03em; background:#f3f4f6; color:#6b7280; }
.doc-status-badge.status-DRAFT { background:#fef9c3; color:#ca8a04; } .doc-status-badge.status-TERKIRIM { background:#dbeafe; color:#2563eb; } .doc-status-badge.status-SELESAI { background:#dcfce7; color:#16a34a; }

/* ===== Worksheet (excel-like) ===== */
.ws-wrap { overflow-x:auto; max-height:65vh; overflow-y:auto; border:1px solid #e5e7eb; border-radius:8px; }
table.ws { border-collapse:collapse; width:100%; font-size:11px; }
table.ws th { position:sticky; top:0; z-index:2; background:#f1f5f9; color:#334155; font-weight:700; text-transform:uppercase; font-size:10px; border:1px solid #d1d5db; padding:7px 6px; white-space:nowrap; text-align:center; }
table.ws td { border:1px solid #e5e7eb; padding:0; }
table.ws tr.ws-group-start td { border-top:2px solid #94a3b8; }
.ws-input { width:100%; border:1px solid transparent; background:transparent; padding:5px 6px; font-size:11px; color:#1f2937; font-family:inherit; }
.ws-input:focus { border-color:#3b82f6; background:#fff; box-shadow:0 0 0 2px rgba(59,130,246,.15); outline:none; }
.ws-num { text-align:right; }
/* Kolom Real: input utama saat closing -> lebih besar & tebal */
.ws-real-input { font-size:14px; font-weight:800; color:#0f172a; background:#fffbeb; }
.ws-real-input:focus { background:#fff; }
/* Warna header per kelompok kolom */
table.ws th.g-info    { background:#e0f2fe; }
table.ws th.g-sku     { background:#f1f5f9; }
table.ws th.g-restock { background:#dcfce7; }
table.ws th.g-stok    { background:#fef3c7; }
table.ws th.g-sj      { background:#ede9fe; }
table.ws th.g-rvjb    { background:#cffafe; }
table.ws th.g-hasil   { background:#fee2e2; }
.ws-ro { background:#f9fafb; color:#6b7280; }
.ws-nosj { background:#f8fafc; font-weight:700; color:#0f172a; white-space:nowrap; padding:5px 8px; font-family:'Courier New',monospace; font-size:11px; }
.ws-input.saving { background:#fef9c3; } .ws-input.saved { background:#dcfce7; }
.ws-input.cell-selected { background:#dbeafe !important; box-shadow: inset 0 0 0 1px #3b82f6; }
.ws-input.cell-active { box-shadow: inset 0 0 0 2px #2563eb; }
select.ws-input { cursor:pointer; }
.ws-statcell { text-align:center; padding:4px 6px; }
.ws-status { display:inline-block; padding:2px 8px; border-radius:999px; font-size:10px; font-weight:700; }
.stat-LEBIH { background:#fee2e2; color:#dc2626; }
.stat-KURANG { background:#ffedd5; color:#d97706; }
.stat-KLOP { background:#dcfce7; color:#16a34a; }

/* ===== Modal Riwayat ===== */
.sj-modal-ov { display:none; position:fixed; inset:0; background:rgba(15,23,42,.55); z-index:100000; align-items:center; justify-content:center; }
.sj-modal-ov.show { display:flex; }
.sj-modal-box { background:#fff; width:960px; max-width:95vw; max-height:88vh; display:flex; flex-direction:column; border-radius:12px; box-shadow:0 20px 40px rgba(0,0,0,.25); overflow:hidden; }
.sj-modal-hd { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-bottom:1px solid #eef0f3; }
.sj-modal-x { cursor:pointer; font-size:24px; color:#9ca3af; } .sj-modal-x:hover { color:#ef4444; }
.sj-modal-bd { padding:16px 20px; overflow:auto; }
</style>

<div class="sj-page-header">
    <div>
        <div class="header-title"><i class="fa fa-truck"></i>&nbsp; Surat Jalan Database</div>
        <div class="header-sub">Worksheet semua item &mdash; edit langsung tanpa buka per dokumen (tersimpan otomatis)</div>
    </div>
    <a class="link btn btn-success" href="<?= base_url('accounting/form-unggah-surat-jalan') ?>"><i class="fa fa-plus-circle"></i> Buat / Buka Workspace</a>
</div>

<div class="sj-stats-row">
    <div class="sj-stat-card blue"><div class="sc-label">Total Surat Jalan</div><div class="sc-value"><?= number_format($stats['total']) ?></div></div>
    <div class="sj-stat-card yellow"><div class="sc-label">Draft</div><div class="sc-value"><?= number_format($stats['draft']) ?></div></div>
    <div class="sj-stat-card blue"><div class="sc-label">Terkirim</div><div class="sc-value"><?= number_format($stats['terkirim']) ?></div></div>
    <div class="sj-stat-card green"><div class="sc-label">Selesai</div><div class="sc-value"><?= number_format($stats['selesai']) ?></div></div>
</div>

<div class="sj-toolbar">
    <div class="sj-tabs">
        <div class="sj-tab active" data-tab="worksheet"><i class="fa fa-table"></i> Worksheet</div>
        <div class="sj-tab" data-tab="daftar"><i class="fa fa-list"></i> Daftar</div>
    </div>
    <div class="sj-filters">
        <input type="text" id="sj-daterange" class="form-control" style="width:230px;" autocomplete="off">
        <select id="sj-status" class="form-control" style="width:140px;">
            <option value="">Semua Status</option>
            <option value="DRAFT">Draft</option>
            <option value="TERKIRIM">Terkirim</option>
            <option value="SELESAI">Selesai</option>
        </select>
        <input type="text" id="sj-search" class="form-control" style="width:200px;" placeholder="🔍 Cari No SJ / Jenis / SKU...">
        <button type="button" class="btn btn-primary" id="btn-upload-trf"><i class="fa fa-download"></i> Upload Transfer Jubelio</button>
        <button type="button" class="btn btn-info" id="btn-ws-history"><i class="fa fa-history"></i> Riwayat Edit</button>
        <input type="file" id="trf-file-input" accept=".xls,.xlsx" multiple style="display:none;">
    </div>
</div>

<!-- PANE: WORKSHEET -->
<div class="sj-pane" id="pane-worksheet">
    <div class="ws-wrap">
        <table class="ws" id="tbl-ws"><thead></thead><tbody></tbody></table>
    </div>
    <div id="ws-empty" style="display:none; padding:24px; text-align:center; color:#9ca3af;">Tidak ada item pada rentang/filter ini.</div>
</div>

<!-- PANE: DAFTAR -->
<div class="sj-pane" id="pane-daftar" style="display:none;">
    <table id="tbl-daftar-sj" class="table table-striped table-hover" style="width:100%;">
        <thead><tr>
            <th style="width:40px;">No</th><th>No SJ</th><th>Tanggal</th><th>Jenis SJ</th><th>No. Trf</th>
            <th>Status</th><th class="text-center">Item</th><th class="text-right">Total Req</th><th>Dibuat Oleh</th><th>Aksi</th>
        </tr></thead><tbody></tbody>
    </table>
</div>

<!-- MODAL HASIL UPLOAD TRANSFER JUBELIO -->
<div id="trf-res-ov" class="sj-modal-ov">
  <div class="sj-modal-box">
    <div class="sj-modal-hd">
      <h4 style="margin:0; font-size:16px; font-weight:700;"><i class="fa fa-download"></i> Hasil Cocok Transfer Jubelio</h4>
      <span class="sj-modal-x" id="trf-res-x">&times;</span>
    </div>
    <div class="sj-modal-bd" id="trf-res-body" style="font-size:13px;"></div>
  </div>
</div>

<!-- MODAL RIWAYAT EDIT -->
<div id="ws-hist-ov" class="sj-modal-ov">
  <div class="sj-modal-box">
    <div class="sj-modal-hd">
      <h4 style="margin:0; font-size:16px; font-weight:700;"><i class="fa fa-history"></i> Riwayat Pengeditan Worksheet</h4>
      <span class="sj-modal-x" id="ws-hist-x">&times;</span>
    </div>
    <div class="sj-modal-bd">
      <input type="text" id="ws-hist-search" class="form-control" placeholder="🔍 Cari No SJ / SKU / kolom..." style="max-width:300px; margin-bottom:10px;">
      <table id="tbl-ws-history" class="table table-striped table-hover" style="width:100%;">
        <thead><tr>
          <th>Waktu</th><th>No SJ</th><th>SKU</th><th>Kolom</th><th>Perubahan (Lama &rarr; Baru)</th><th>Jenis</th><th>Oleh</th><th>Aksi</th>
        </tr></thead><tbody></tbody>
      </table>
    </div>
  </div>
</div>

<script>
$(document).ready(function () {
    var start = moment().startOf('month'), end = moment().endOf('month');
    var BASE = '<?= base_url() ?>';

    $('#sj-daterange').daterangepicker({
        startDate: start, endDate: end,
        ranges: {
            'Hari Ini':[moment().startOf('day'),moment().endOf('day')],
            '7 Hari Terakhir':[moment().subtract(6,'days'),moment()],
            'Bulan Ini':[moment().startOf('month'),moment().endOf('month')],
            'Bulan Lalu':[moment().subtract(1,'month').startOf('month'),moment().subtract(1,'month').endOf('month')]
        },
        locale:{ format:'YYYY-MM-DD 00:00:00', separator:' - ' }
    }, function(s,e){
        $('#sj-daterange').val(s.format('YYYY-MM-DD 00:00:00')+' - '+e.format('YYYY-MM-DD 23:59:59'));
        reloadActive();
    });
    $('#sj-daterange').val(start.format('YYYY-MM-DD 00:00:00')+' - '+end.format('YYYY-MM-DD 23:59:59'));

    function noty2(msg,type){ if(typeof noty!=='undefined'){ noty({text:msg,layout:'topRight',type:type||'information',timeout:2500}); } }

    // ---------- Kolom worksheet ----------
    var COLS = [
        {k:'tgl', l:'Tgl', t:'date', doc:true, grp:'info'},
        {k:'jenis_sj', l:'Jenis SJ', t:'text', doc:true, grp:'info'},
        {k:'no_trf_jubelio', l:'No. Trf', t:'text', doc:true, grp:'info'},
        {k:'sku', l:'SKU', t:'text', grp:'sku'},
        {k:'qty_restock_rqst', l:'Rqst', t:'num', grp:'restock'},
        {k:'qty_restock_real', l:'Real', t:'num', grp:'restock'},
        {k:'qty_jubelio_disp', l:'Disp', t:'num', grp:'stok'},
        {k:'qty_jubelio_gd', l:'Gd', t:'num', grp:'stok'},
        {k:'sj_jubelio_sku', l:'SJ SKU', t:'text', grp:'sj'},
        {k:'sj_jubelio_qty', l:'SJ Qty', t:'num', grp:'sj'},
        {k:'real_vs_jb_sku', l:'RvJB SKU', t:'flag', grp:'rvjb'},
        {k:'real_vs_jb_qty', l:'RvJB Qty', t:'flag', grp:'rvjb'},
        {k:'selisih', l:'Selisih', t:'sel', grp:'hasil'},
        {k:'action_in_jubelio', l:'Action', t:'text', grp:'hasil'}
    ];

    function esc(v){ return $('<div>').text(v==null?'':v).html().replace(/"/g,'&quot;'); }
    function num(v){ v=parseInt(v); return isNaN(v)?0:v; }
    function selColor(s){ return s>0?'#dc2626':(s<0?'#d97706':'#16a34a'); }
    // Rekonsiliasi turunan (mirror backend _recon_values): NBP / REAL BLM / selisih.
    function reconRow(row){
        var gd = num(row.qty_jubelio_gd);
        var realRaw = row.qty_restock_real;
        var realBlank = (realRaw===null || realRaw==='' || typeof realRaw==='undefined');
        if (gd===0) return {selDisp:'0', fq:0, fs:0, blank:false, nbp:true};
        if (realBlank) return {selDisp:'REAL BLM', fq:0, fs:0, blank:true, nbp:false};
        var sel = num(row.sj_jubelio_qty) - num(realRaw);
        var sj = (row.sj_jubelio_sku||'').trim(), s = (row.sku||'').trim();
        return {selDisp:String(sel), fq:(sel!==0?1:0), fs:((sj!=='' && sj!==s)?1:0), blank:false, nbp:false};
    }

    function cellHtml(col, row){
        var v = row[col.k]; if (v==null) v='';
        var attrs = 'data-id="'+row.id_item+'" data-doc="'+row.id_doc+'" data-field="'+col.k+'"';
        if (col.t==='ro-nosj') return '<td class="ws-nosj">'+esc(v)+'</td>';
        if (col.t==='sel'){ var rc=reconRow(row); var scol=rc.blank?'#6b7280':selColor(parseInt(rc.selDisp)||0); return '<td><input class="ws-input ws-num ws-ro ws-selisih" readonly value="'+esc(rc.selDisp)+'" style="font-weight:700;color:'+scol+'"></td>'; }
        if (col.t==='flag'){ var rf=reconRow(row); var fv=(col.k==='real_vs_jb_qty')?rf.fq:rf.fs; return '<td><input class="ws-input ws-num ws-ro ws-'+col.k+'" readonly value="'+fv+'" style="width:38px;text-align:center;font-weight:700;color:'+(fv?'#dc2626':'#16a34a')+'"></td>'; }
        if (col.t==='ro') return '<td><input class="ws-input ws-num ws-ro" readonly value="'+esc(v)+'"></td>';
        if (col.t==='num'){ var nx=(col.k==='qty_restock_real')?' ws-real-input':''; return '<td><input type="number" class="ws-input ws-num'+nx+'" '+attrs+' value="'+esc(v)+'"></td>'; }
        if (col.t==='date') return '<td><input type="date" class="ws-input" '+attrs+' value="'+esc(v)+'"></td>';
        if (col.t==='status'){
            var opts=['DRAFT','TERKIRIM','SELESAI'].map(function(s){return '<option value="'+s+'"'+(v===s?' selected':'')+'>'+s+'</option>';}).join('');
            return '<td><select class="ws-input" '+attrs+'>'+opts+'</select></td>';
        }
        return '<td><input type="text" class="ws-input" '+attrs+' value="'+esc(v)+'"></td>';
    }

    function loadWorksheet(){
        $.ajax({
            url: BASE+'accounting/get-sj-worksheet', method:'POST',
            data:{ reportrange:$('#sj-daterange').val(), status:$('#sj-status').val(), search:$('#sj-search').val() },
            dataType:'json',
            success:function(res){
                var head='<tr>'+COLS.map(function(c){return '<th class="g-'+(c.grp||'sku')+'">'+c.l+'</th>';}).join('')+'</tr>';
                $('#tbl-ws thead').html(head);
                var rows=res.data||[]; var body='';
                var prev=null;
                rows.forEach(function(r){
                    var cls = (prev!==null && prev!==r.no_sj) ? ' class="ws-group-start"' : '';
                    prev = r.no_sj;
                    body += '<tr'+cls+'>'+COLS.map(function(c){return cellHtml(c,r);}).join('')+'</tr>';
                });
                $('#tbl-ws tbody').html(body);
                $('#ws-empty').toggle(rows.length===0);
            },
            error:function(){ noty2('Gagal memuat worksheet','error'); }
        });
    }

    // ---------- Autosave per sel ----------
    $('#tbl-ws').on('change', '.ws-input:not(.ws-ro)', function(){
        var $inp=$(this);
        var id=$inp.data('id'), field=$inp.data('field'), doc=$inp.data('doc'), val=$inp.val();
        $inp.addClass('saving');
        $.ajax({
            url: BASE+'accounting/save-sj-item-cell', method:'POST',
            data:{ id_item:id, field:field, value:val }, dataType:'json',
            success:function(res){
                $inp.removeClass('saving');
                if(res.status==='success'){
                    $inp.addClass('saved'); setTimeout(function(){$inp.removeClass('saved');},900);
                    var $row=$inp.closest('tr');
                    if(typeof res.sel_display!=='undefined'){
                        var blank=(res.sel_display==='REAL BLM');
                        $row.find('.ws-selisih').val(res.sel_display).css({'font-weight':'700','color':blank?'#6b7280':selColor(parseInt(res.sel_display)||0)});
                    }
                    if(typeof res.real_vs_jb_qty!=='undefined'){
                        $row.find('.ws-real_vs_jb_qty').val(res.real_vs_jb_qty).css('color', res.real_vs_jb_qty? '#dc2626':'#16a34a');
                    }
                    if(typeof res.real_vs_jb_sku!=='undefined'){
                        $row.find('.ws-real_vs_jb_sku').val(res.real_vs_jb_sku).css('color', res.real_vs_jb_sku? '#dc2626':'#16a34a');
                    }
                    if(typeof res.action_in_jubelio!=='undefined'){ $row.find('.ws-input[data-field="action_in_jubelio"]').val(res.action_in_jubelio); }
                    if(res.doc_level){ // propagate ke baris lain dokumen sama
                        $('#tbl-ws').find('.ws-input[data-doc="'+doc+'"][data-field="'+field+'"]').not($inp).val(val);
                    }
                } else { noty2(res.message||'Gagal menyimpan','error'); }
            },
            error:function(){ $inp.removeClass('saving'); noty2('Gagal koneksi saat menyimpan','error'); }
        });
    });

    // ===== Excel-like: pilih range + copy/paste (fill-down) di worksheet =====
    var $wsb = $('#tbl-ws tbody');
    var anchorInput=null, activeInput=null, internalClipboard=[];
    function cellCol($i){ return $i.closest('td').index(); }
    function cellRow($i){ return $i.closest('tr').index(); }
    function getCell(r,c){ var $tr=$wsb.find('tr').eq(r); if(!$tr.length) return null; var $i=$tr.children('td').eq(c).find('input.ws-input').first(); return $i.length?$i:null; }
    function clearSel(){ $wsb.find('.cell-selected').removeClass('cell-selected'); $wsb.find('.cell-active').removeClass('cell-active'); }
    function selectRange($from,$to){ clearSel(); var col=cellCol($from); var r1=cellRow($from),r2=cellRow($to); if(r1>r2){var t=r1;r1=r2;r2=t;} for(var r=r1;r<=r2;r++){ var $c=getCell(r,col); if($c && !$c.hasClass('ws-ro')) $c.addClass('cell-selected'); } $to.addClass('cell-active'); }
    function selectedInputs(){ var a=[]; $wsb.find('input.cell-selected').each(function(){a.push($(this));}); return a; }
    function setCellValue($i,val){ if(!$i || $i.prop('readonly')) return; $i.val(val).trigger('change'); }

    // Klik biasa = jangkar; Shift+Klik = perluas range (kolom sama)
    $wsb.on('mousedown','input.ws-input:not(.ws-ro)',function(e){
        if(e.shiftKey && anchorInput && cellCol(anchorInput)===cellCol($(this))){ e.preventDefault(); activeInput=$(this); this.focus(); selectRange(anchorInput,activeInput); return; }
        anchorInput=$(this); activeInput=$(this); clearSel();
    });

    // Enter turun 1 baris; Shift+Panah pilih range; Ctrl+C salin; Ctrl+D fill-down; Delete kosongkan
    $wsb.on('keydown','input.ws-input',function(e){
        var $inp=$(this); var ctrl=e.ctrlKey||e.metaKey;
        if(e.key==='Enter' && !e.shiftKey && !ctrl && $inp.attr('type')==='number'){ e.preventDefault(); var $d=getCell(cellRow($inp)+1,cellCol($inp)); if($d){$d[0].focus(); if($d[0].select)$d[0].select();} else {$inp.blur();} return; }
        if(e.shiftKey && (e.key==='ArrowDown'||e.key==='ArrowUp')){ if(!anchorInput)anchorInput=$inp; var base=activeInput||$inp; var nr=cellRow(base)+(e.key==='ArrowDown'?1:-1); var $n=getCell(nr,cellCol(base)); if($n){ e.preventDefault(); activeInput=$n; $n[0].focus(); selectRange(anchorInput,activeInput); } return; }
        if(ctrl && (e.key==='c'||e.key==='C')){ var sel=selectedInputs(); if(sel.length>1){ internalClipboard=sel.map(function($c){return $c.val();}); try{navigator.clipboard&&navigator.clipboard.writeText(internalClipboard.join('\n'));}catch(x){} e.preventDefault(); } else { internalClipboard=[$inp.val()]; if(this.selectionStart===this.selectionEnd){ try{navigator.clipboard&&navigator.clipboard.writeText($inp.val());}catch(x){} } } return; }
        if(ctrl && (e.key==='d'||e.key==='D')){ e.preventDefault(); var sd=selectedInputs(); if(sd.length>1){ var top=sd[0].val(); for(var i=1;i<sd.length;i++) setCellValue(sd[i],top); } else { var $ab=getCell(cellRow($inp)-1,cellCol($inp)); if($ab) setCellValue($inp,$ab.val()); } return; }
        if(e.key==='Delete' && selectedInputs().length>1){ e.preventDefault(); selectedInputs().forEach(function($c){ setCellValue($c,$c.attr('type')==='number'?0:''); }); return; }
    });

    // Paste: grid dari Excel (banyak baris), atau isi range terpilih dengan 1 nilai
    $wsb.on('paste','input.ws-input',function(e){
        var $inp=$(this); var cd=(e.originalEvent||e).clipboardData||window.clipboardData; var text=cd?cd.getData('text'):'';
        var multiline=text && /[\t\n\r]/.test(text); var sel=selectedInputs();
        if(multiline){ e.preventDefault(); var sr=cellRow($inp),sc=cellCol($inp); var lines=text.replace(/\r\n/g,'\n').replace(/\r/g,'\n').split('\n'); while(lines.length && lines[lines.length-1]==='') lines.pop(); for(var i=0;i<lines.length;i++){ var cols=lines[i].split('\t'); for(var j=0;j<cols.length;j++){ var $t=getCell(sr+i,sc+j); if($t) setCellValue($t,cols[j].trim()); } } return; }
        if(internalClipboard.length>1){ e.preventDefault(); var s2=cellRow($inp),c2=cellCol($inp); for(var k=0;k<internalClipboard.length;k++){ var $t2=getCell(s2+k,c2); if($t2) setCellValue($t2,internalClipboard[k]); } return; }
        var fillVal=internalClipboard.length===1?internalClipboard[0]:(text!==''?text:null);
        if(sel.length>1 && fillVal!==null){ e.preventDefault(); sel.forEach(function($c){ setCellValue($c,fillVal); }); return; }
    });

    $(document).on('keydown.wssel',function(e){ if(e.key==='Escape') clearSel(); });

    // ---------- Tab ----------
    var dtDaftar=null;
    function reloadActive(){
        if($('#pane-worksheet').is(':visible')) loadWorksheet();
        else if(dtDaftar) dtDaftar.ajax.reload();
    }
    $('.sj-tab').click(function(){
        $('.sj-tab').removeClass('active'); $(this).addClass('active');
        var tab=$(this).data('tab');
        if(tab==='worksheet'){ $('#pane-worksheet').show(); $('#pane-daftar').hide(); loadWorksheet(); }
        else { $('#pane-worksheet').hide(); $('#pane-daftar').show(); initDaftar(); dtDaftar.ajax.reload(); }
    });

    function initDaftar(){
        if(dtDaftar) return;
        dtDaftar=$('#tbl-daftar-sj').DataTable({
            processing:true, serverSide:true, searching:false, lengthChange:false, pageLength:25, order:[],
            ajax:{ url:BASE+'accounting/get-surat-jalan-docs-dt', type:'POST',
                data:function(d){ d.reportrange=$('#sj-daterange').val(); d.status=$('#sj-status').val(); d.search={value:$('#sj-search').val()}; } },
            columns:[{orderable:false,className:'text-center'},{orderable:false},{orderable:false},{orderable:false},{orderable:false},{orderable:false,className:'text-center'},{orderable:false,className:'text-center'},{orderable:false,className:'text-right'},{orderable:false},{orderable:false,className:'text-center'}],
            language:{ processing:'<i class="fa fa-spinner fa-spin"></i> Memuat...', emptyTable:'Belum ada Surat Jalan', info:'Menampilkan _START_–_END_ dari <strong>_TOTAL_</strong>', infoEmpty:'Tidak ada data', paginate:{previous:'‹',next:'›'} }
        });
    }

    // Filter change
    var st=null;
    $('#sj-search').on('keyup', function(){ clearTimeout(st); st=setTimeout(reloadActive,350); });
    $('#sj-status').on('change', reloadActive);

    // ---------- Modal Riwayat Edit ----------
    var dtHist=null;
    $('#btn-ws-history').click(function(){
        $('#ws-hist-ov').addClass('show');
        if(!dtHist){
            dtHist=$('#tbl-ws-history').DataTable({
                processing:true, serverSide:true, searching:false, lengthChange:false, pageLength:25, order:[],
                ajax:{ url:BASE+'accounting/get-sj-item-history', type:'POST', data:function(d){ d.search={value:$('#ws-hist-search').val()}; } },
                columns:[{orderable:false},{orderable:false},{orderable:false},{orderable:false},{orderable:false},{orderable:false},{orderable:false},{orderable:false,className:'text-center'}],
                language:{ processing:'<i class="fa fa-spinner fa-spin"></i> Memuat...', emptyTable:'Belum ada perubahan', info:'_TOTAL_ perubahan', infoEmpty:'Tidak ada data', paginate:{previous:'‹',next:'›'} }
            });
        } else { dtHist.ajax.reload(); }
        setTimeout(function(){ if(dtHist) dtHist.columns.adjust(); }, 60);
    });
    function closeHist(){ $('#ws-hist-ov').removeClass('show'); }
    $('#ws-hist-x').click(closeHist);
    $('#ws-hist-ov').click(function(e){ if(e.target===this) closeHist(); });

    // ---------- Upload Transfer Jubelio (auto-match) ----------
    $('#btn-upload-trf').click(function(){ $('#trf-file-input').val('').click(); });
    $('#trf-file-input').on('change', function(){
        if(!this.files || !this.files.length) return;
        var fd=new FormData();
        for(var i=0;i<this.files.length;i++) fd.append('trfFiles[]', this.files[i]);
        var $btn=$('#btn-upload-trf'); var old=$btn.html();
        $btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Mencocokkan...');
        $.ajax({
            url: BASE+'accounting/upload-transfer-jubelio', method:'POST',
            data:fd, processData:false, contentType:false, dataType:'json',
            success:function(res){
                if(res.status==='success'){
                    showTrfResult(res);
                    loadWorksheet();
                    noty2('Cocok: '+res.total.matched+' baris dari '+res.total.files+' file','success');
                } else { noty2(res.message||'Gagal memproses','error'); }
            },
            error:function(){ noty2('Gagal koneksi saat upload','error'); },
            complete:function(){ $btn.prop('disabled',false).html(old); }
        });
    });
    function esch(s){ return $('<div>').text(s==null?'':s).html(); }
    function showTrfResult(res){
        var t=res.total||{};
        var h='<div style="margin-bottom:10px;padding:10px;background:#f0f9ff;border-radius:6px;">'+
              '<b>'+(t.files||0)+'</b> file diproses &middot; <b style="color:#16a34a;">'+(t.matched||0)+'</b> baris cocok &middot; '+
              '<b style="color:#dc2626;">'+(t.unmatched_sj||0)+'</b> SKU SJ tak ada di Jubelio &middot; '+
              '<b style="color:#d97706;">'+(t.extra_jb||0)+'</b> SKU Jubelio tak ada di SJ</div>';
        (res.results||[]).forEach(function(r){
            h+='<div style="border:1px solid #e5e7eb;border-radius:6px;padding:8px 10px;margin-bottom:8px;">';
            h+='<div style="font-weight:700;">'+esch(r.file)+'</div>';
            if(r.error){ h+='<div style="color:#dc2626;">⚠ '+esch(r.error)+'</div>'; }
            else {
                h+='<div style="color:#6b7280;">No.Transfer <b>'+esch(r.trf)+'</b> &middot; baris SJ terkait: '+r.sj_rows+' &middot; cocok: <b style="color:#16a34a;">'+r.matched+'</b></div>';
                if(r.unmatched_sj && r.unmatched_sj.length) h+='<div style="color:#dc2626;margin-top:4px;">SKU di SJ tapi tak ada di transfer: '+esch(r.unmatched_sj.join(', '))+'</div>';
                if(r.extra_jb && r.extra_jb.length) h+='<div style="color:#d97706;margin-top:4px;">SKU di transfer tapi tak ada di SJ: '+esch(r.extra_jb.join(', '))+'</div>';
            }
            h+='</div>';
        });
        $('#trf-res-body').html(h);
        $('#trf-res-ov').addClass('show');
    }
    $('#trf-res-x').click(function(){ $('#trf-res-ov').removeClass('show'); });
    $('#trf-res-ov').click(function(e){ if(e.target===this) $(this).removeClass('show'); });
    var ht=null;
    $('#ws-hist-search').on('keyup', function(){ clearTimeout(ht); ht=setTimeout(function(){ if(dtHist) dtHist.ajax.reload(); },350); });

    // Kembalikan (revert)
    $('#tbl-ws-history').on('click', '.btn-revert', function(){
        var hid=$(this).data('id');
        if(!confirm('Kembalikan kolom ini ke nilai sebelumnya?')) return;
        $.ajax({
            url:BASE+'accounting/revert-sj-item-field', method:'POST', data:{ history_id:hid }, dataType:'json',
            success:function(res){
                if(res.status==='success'){
                    noty2(res.message||'Dikembalikan','success');
                    if(dtHist) dtHist.ajax.reload(null,false);
                    if($('#pane-worksheet').is(':visible')) loadWorksheet();
                } else { noty2(res.message||'Gagal','error'); }
            },
            error:function(){ noty2('Gagal koneksi server','error'); }
        });
    });

    // Muat awal
    loadWorksheet();
});
</script>
