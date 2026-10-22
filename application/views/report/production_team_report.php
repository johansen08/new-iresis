<div class="row">
  <div class="col-md-12">

    <div class="panel panel-default tabs">
      <ul class="nav nav-tabs nav-justified nav-tabs-ptr">
        <li class="active"><a href="#tab0" data-toggle="tab">Laporan Picker</a></li>
        <li><a href="#tab1" data-toggle="tab">Laporan Packer</a></li>
        <li><a href="#tab2" data-toggle="tab">Laporan HO</a></li>
      </ul>
      <div class="panel-body tab-content">
        <div class="tab-pane active" id="tab0">
          <?php $this->load->view('report/production_team_report_tab0') ?>
        </div>
        <div class="tab-pane" id="tab1">
          <?php $this->load->view('report/production_team_report_tab1') ?>
        </div>
        <div class="tab-pane" id="tab2">
          <?php $this->load->view('report/production_team_report_tab2') ?>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
    .tabs > .panel-body.tab-content { padding: 8px 10px; }
</style>
<script type="text/javascript">
    // Header tabel di tab tersembunyi perlu disesuaikan ulang saat tab dibuka
    $(document).off('shown.bs.tab.ptr').on('shown.bs.tab.ptr', 'a[data-toggle="tab"]', function() {
        $($.fn.dataTable.fnTables(true)).each(function() { $(this).DataTable().columns.adjust(); });
    });
</script>