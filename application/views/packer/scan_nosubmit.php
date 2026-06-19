<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <!-- title form -->
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Scan Resi Packer</strong></h3>
      </div>

      <!-- input scan noresi -->
      <div class="panel-body">
        <form class="form-horizontal" autocomplete="off" onsubmit="return false;">
          <div class="form-group">
            <div class="col-md-12">
              <div class="input-group">
                <input type="text" class="form-control" name="noresi" id="noresi" placeholder="Nomor resi" />
                <span class="input-group-btn" id="button-group">
                  <button class="btn btn-default" type="button" disabled>
                    <i class="fa fa-search"></i> Cari
                  </button>
                </span>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- info dan tabel -->
      <div id="result-info" style="display:none;">
        <div id="button-footer" class="text-left" style="margin-top:10px;"></div>

        <div class="row">
          <div class="col-md-4 text-center" style="border-right:1px solid #ccc;"><strong>Total Scan: <span id="total_scan">0</span></strong></div>
          <div class="col-md-4 text-center" style="border-right:1px solid #ccc;"><strong>Picker: <span id="nama_picker">-</span></strong></div>
          <div class="col-md-4 text-center"><strong>Komputer: <span id="komputer_packer">-</span></strong></div>
        </div>

        <div class="panel-body" id="table-scan-packer">
          <table class="table table-striped datatable-masalah-picker">
            <thead>
              <tr>
                <th>#</th>
                <th>Foto</th>
                <th>Nama Barang</th>
                <th>SKU</th>
                <th>Quantity</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="6" class="text-center">No details</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- success modal -->
      <div id="successModal" class="custom-popup-overlay" style="display:none;">
        <div class="custom-popup-box success-popup">
          <div class="panel panel-default">
            <div class="panel-heading" style="background-color:#5cb85c;color:white;">
              <h3 class="panel-title"><i class="fa fa-check-circle"></i> <strong>Success!</strong></h3>
            </div>
            <div class="panel-body">
              <p id="successMessage" style="font-size:16px;margin:20px 0;">Data berhasil disubmit!</p>
              <div class="progress" style="margin:10px 0;">
                <div id="progressBar" class="progress-bar progress-bar-success" style="width:100%;transition:width 1s linear;"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- error modal -->
      <div id="errorModal" class="custom-popup-overlay" style="display:none;">
        <div class="custom-popup-box error-popup">
          <div class="panel panel-default">
            <div class="panel-heading" style="background-color:#d9534f;color:white;">
              <h3 class="panel-title"><i class="fa fa-times-circle"></i> <strong>Error!</strong></h3>
            </div>
            <div class="panel-body">
              <p id="errorMessage" style="font-size:16px;margin:20px 0;">Terjadi kesalahan!</p>
              <div class="progress" style="margin:10px 0;">
                <div id="errorProgressBar" class="progress-bar progress-bar-danger" style="width:100%;transition:width 1s linear;"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- audio -->
      <audio id="audio-alert" src="<?= base_url('assets/sound/success.mp3') ?>" preload="auto"></audio>
      <audio id="audio-fail" src="<?= base_url('assets/sound/fail.mp3') ?>" preload="auto"></audio>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
  $('#noresi').focus();

  $('#noresi').on('change', function(){
    const noresi = $(this).val().trim();
    if(!noresi) return;

    $.ajax({
      url: "<?= site_url('packer/save_packer') ?>",
      type: "POST",
      dataType: "json",
      data: { noresi: noresi },
      success: function(res){
        if(res.status === 201){
          $('#successMessage').text(noresi + " berhasil disimpan!");
          $('#successModal').fadeIn();
          document.getElementById('audio-alert').play();
          setTimeout(()=>$('#successModal').fadeOut(),1000);
        } else if(res.status === 200){
          toastr.info('ℹ️ '+noresi+' sudah tersimpan.');
        } else {
          $('#errorMessage').text(res.message || "Gagal simpan!");
          $('#errorModal').fadeIn();
          document.getElementById('audio-fail').play();
          setTimeout(()=>$('#errorModal').fadeOut(),1000);
        }

        $('#noresi').val('').focus();
        $('#result-info').show();
        if(typeof table !== 'undefined') table.ajax.reload(null,false);
      },
      error: function(){
        $('#errorMessage').text("Gagal mengirim ke server!");
        $('#errorModal').fadeIn();
        document.getElementById('audio-fail').play();
        setTimeout(()=>$('#errorModal').fadeOut(),1000);
        $('#noresi').val('').focus();
      }
    });
  });

  // DataTable server side
  let noresiTbl = '';
  table = $('.datatable-masalah-picker').DataTable({
    scrollX:true,
    pageLength:10,
    processing:true,
    serverSide:true,
    ajax:{
      url:'<?= site_url("packer/get_scan_packer_data/") ?>'+noresiTbl,
      type:'POST'
    },
    columnDefs:[
      {width:'5%',targets:0},{width:'20%',targets:1},{width:'35%',targets:2},{width:'10%',targets:3},{width:'10%',targets:4},{width:'20%',targets:5},
      {className:'text-center',targets:[0,1,2,3,4,5]}
    ]
  });
});
</script>
