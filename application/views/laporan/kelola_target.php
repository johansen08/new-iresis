<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">Kelola Target Pegawai</h3>
      </div>
      <div class="panel-body">

        <!-- FORM TAMBAH/EDIT -->
        <div class="well">
          <form id="form-target" class="form-inline">
            <input type="hidden" name="id" id="target-id">

            <div class="form-group" style="margin-right:10px">
              <label>Role: </label>
              <select name="role" id="target-role" class="form-control" required>
                <option value="picker">Picker</option>
                <option value="packer">Packer</option>
              </select>
            </div>

            <div class="form-group" style="margin-right:10px">
              <label>Target/hari: </label>
              <input type="number" name="target" id="target-value" class="form-control" style="width:100px" min="1" required>
            </div>

            <div class="form-group" style="margin-right:10px">
              <label>Berlaku dari: </label>
              <input type="date" name="berlaku_dari" id="target-date" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">
              <i class="fa fa-save"></i> Simpan
            </button>
            <button type="button" class="btn btn-default" id="btn-reset-form">Reset</button>
          </form>
          <p class="text-muted" style="margin-top:5px; margin-bottom:0">
            <small>Target berlaku sebagai default untuk semua pegawai dalam role tersebut.</small>
          </p>
        </div>

        <!-- TABEL TARGET -->
        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th width="40">No</th>
              <th>Role</th>
              <th>Pegawai</th>
              <th class="text-center">Target/Hari</th>
              <th>Berlaku Dari</th>
              <th width="120">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($targets)): $i = 1; foreach ($targets as $t): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><span class="label label-<?= $t->role == 'picker' ? 'info' : 'success' ?>"><?= ucfirst($t->role) ?></span></td>
              <td><?= $t->nama_pegawai ?: ($t->nama_user ?: '<em class="text-muted">Semua ' . ucfirst($t->role) . '</em>') ?></td>
              <td class="text-center"><strong><?= $t->target ?></strong></td>
              <td><?= date('d-m-Y', strtotime($t->berlaku_dari)) ?></td>
              <td>
                <button class="btn btn-xs btn-warning btn-edit-target"
                        data-id="<?= $t->id ?>"
                        data-role="<?= $t->role ?>"
                        data-target="<?= $t->target ?>"
                        data-date="<?= $t->berlaku_dari ?>">
                  <i class="fa fa-pencil"></i>
                </button>
                <button class="btn btn-xs btn-danger btn-delete-target" data-id="<?= $t->id ?>">
                  <i class="fa fa-trash"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" class="text-center text-muted">Belum ada target yang diatur</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  // Set default date
  $('#target-date').val(moment().format('YYYY-MM-DD'));

  // Submit form
  $(document).off('submit', '#form-target').on('submit', '#form-target', function(e) {
    e.preventDefault();
    var formData = $(this).serialize();

    $.post('laporan/save-target', formData, function(res) {
      var data = (typeof res === 'string') ? JSON.parse(res) : res;
      noty({ text: data.message, type: data.code === 200 ? 'success' : 'error', timeout: 3000 });
      if (data.code === 200) {
        devScript.openPage('laporan/kelola-target');
      }
    });
  });

  // Edit
  $(document).off('click', '.btn-edit-target').on('click', '.btn-edit-target', function() {
    var btn = $(this);
    $('#target-id').val(btn.data('id'));
    $('#target-role').val(btn.data('role'));
    $('#target-value').val(btn.data('target'));
    $('#target-date').val(btn.data('date'));
  });

  // Delete
  $(document).off('click', '.btn-delete-target').on('click', '.btn-delete-target', function() {
    var id = $(this).data('id');
    if (!confirm('Hapus target ini?')) return;

    $.post('laporan/delete-target', { id: id }, function(res) {
      var data = (typeof res === 'string') ? JSON.parse(res) : res;
      noty({ text: data.message, type: 'success', timeout: 3000 });
      devScript.openPage('laporan/kelola-target');
    });
  });

  // Reset
  $(document).off('click', '#btn-reset-form').on('click', '#btn-reset-form', function() {
    $('#target-id').val('');
    $('#target-value').val('');
    $('#target-date').val(moment().format('YYYY-MM-DD'));
  });
})();
</script>
