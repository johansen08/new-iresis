<style>
  .complain-form .form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
  }

  .complain-form .form-group {
    margin-bottom: 15px;
  }

  @media (max-width: 1400px) {
    .complain-form .col-md-6 {
      width: 100% !important;
      float: none !important;
    }
  }
  
  /* Tab styling improvements */
  .nav-tabs > li > a {
    font-weight: 600;
    font-size: 14px;
    padding: 10px 20px;
    transition: all 0.3s ease;
  }
  
  .nav-tabs > li.active > a,
  .nav-tabs > li.active > a:focus,
  .nav-tabs > li.active > a:hover {
    border-top-color: #565249;
    background-color: #fff;
    color: #333;
  }
  
  /* Panel body padding */
  .panel-body.tab-content {
    padding: 20px;
    background-color: #fff;
  }
  
  
  /* Form styling improvements */
  .complain-form {
    padding: 10px 0;
  }
  
  .complain-form .btn {
    margin-right: 10px;
    border-radius: 4px;
  }

  /* Sync button styling */
  .btn-sync-resi {
    min-width: 40px;
  }

  .btn-sync-resi i {
    transition: transform 0.3s ease;
  }

  .btn-sync-resi:hover i {
    transform: rotate(180deg);
  }

  .btn-sync-resi:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
</style>

<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default tabs">
      <div class="panel-heading">
        <h3 class="panel-title"><strong>Proses Retur Complain</strong></h3>
      </div>

      <ul class="nav nav-tabs nav-justified" role="tablist">
        <li class="active"><a href="#tab_refund" role="tab" data-toggle="tab">Refund Dana</a></li>
        <li><a href="#tab_replacement" role="tab" data-toggle="tab">Pergantian Barang</a></li>
      </ul>

      <div class="panel-body tab-content">
          <!-- Refund Dana -->
          <div class="tab-pane active" id="tab_refund">
            <form id="form-refund-complain" class="complain-form" autocomplete="off" style="padding: 10px 0;">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>No. Resi</label>
                    <div class="input-group">
                      <input type="text" name="noresi" id="noresi_refund" class="form-control" placeholder="Masukkan nomor resi" required />
                      <span class="input-group-btn">
                        <button type="button" class="btn btn-default btn-sync-resi" data-form="refund" title="Sync data resi">
                          <i class="fa fa-refresh"></i>
                        </button>
                      </span>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Nama Customer</label>
                    <input type="text" name="customer_name" id="customer_name_refund" class="form-control" placeholder="Nama customer" required />
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Marketplace</label>
                    <select name="marketplace" id="marketplace_refund" class="form-control select" required>
                      <option value="">- Pilih Marketplace -</option>
                      <?php foreach ($list_marketplace as $marketplace) : ?>
                        <option value="<?= $marketplace['nama_marketplace'] ?>"><?= $marketplace['nama_marketplace'] ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>No. Pesanan</label>
                    <input type="text" name="no_pesanan" id="no_pesanan_refund" class="form-control" placeholder="Nomor pesanan" required />
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>SKU (Asli)</label>
                    <select name="sku" id="sku_refund" class="form-control select2-tags" required>
                      <option value="">- Cari SKU atau input manual -</option>
                    </select>
                    <small class="text-muted">Sync nomor resi terlebih dahulu untuk memilih SKU</small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Qty (Asli)</label>
                    <input type="number" name="qty" id="qty_refund" class="form-control" placeholder="Jumlah barang asli" readonly />
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Nominal Refund</label>
                    <input type="number" name="refund_amount" class="form-control" placeholder="Contoh: 150000" min="1" required />
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Bank / E-Wallet</label>
                    <input type="text" name="refund_bank" class="form-control" placeholder="Nama bank atau e-wallet" />
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>No. Rekening / ID</label>
                    <input type="text" name="refund_account" class="form-control" placeholder="Nomor rekening / ID" />
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-12">
                  <div class="form-group">
                    <label>Catatan</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Catatan tambahan (opsional)"></textarea>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-12">
                  <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Simpan Refund</button>
                  <button type="reset" class="btn btn-default">Reset</button>
                </div>
              </div>
            </form>
          </div>

          <!-- Pergantian Barang -->
          <div class="tab-pane" id="tab_replacement">
            <form id="form-replacement-complain" class="complain-form" autocomplete="off" style="padding: 10px 0;">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>No. Resi</label>
                    <div class="input-group">
                      <input type="text" name="noresi" id="noresi_replacement" class="form-control" placeholder="Masukkan nomor resi" required />
                      <span class="input-group-btn">
                        <button type="button" class="btn btn-default btn-sync-resi" data-form="replacement" title="Sync data resi">
                          <i class="fa fa-refresh"></i>
                        </button>
                      </span>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Nama Customer</label>
                    <input type="text" name="customer_name" id="customer_name_replacement" class="form-control" placeholder="Nama customer" required />
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Marketplace</label>
                    <select name="marketplace" id="marketplace_replacement" class="form-control select" required>
                      <option value="">- Pilih Marketplace -</option>
                      <?php foreach ($list_marketplace as $marketplace) : ?>
                        <option value="<?= $marketplace['nama_marketplace'] ?>"><?= $marketplace['nama_marketplace'] ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>No. Pesanan</label>
                    <input type="text" name="no_pesanan" id="no_pesanan_replacement" class="form-control" placeholder="Nomor pesanan" required />
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>SKU (Asli)</label>
                    <select name="sku" id="sku_replacement" class="form-control select2-tags" required>
                      <option value="">- Cari SKU atau input manual -</option>
                    </select>
                    <small class="text-muted">Sync nomor resi terlebih dahulu untuk memilih SKU</small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Qty (Asli)</label>
                    <input type="number" name="qty" id="qty_replacement" class="form-control" placeholder="Jumlah barang asli" readonly />
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>SKU Pengganti</label>
                    <select name="replacement_sku" id="replacement_sku" class="form-control select" required>
                      <option value="">- Sync resi terlebih dahulu -</option>
                    </select>
                    <small class="text-muted">Sync nomor resi terlebih dahulu untuk memilih SKU</small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Qty Pengganti</label>
                    <input type="number" name="replacement_qty" class="form-control" min="1" placeholder="Jumlah barang pengganti" required />
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-12">
                  <div class="form-group">
                    <label>Catatan</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan tambahan (opsional)"></textarea>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-12">
                  <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Simpan Pergantian</button>
                  <button type="reset" class="btn btn-default">Reset</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function() {
    $('.select2-tags').select2({
      tags: true,
      placeholder: "- Cari SKU atau input manual -",
      allowClear: true
    });
  });

  // Bootstrap Select-compatible SKU dropdown population
  function populateSkuDropdown($dropdown, skuList, selectedSku) {
    if (!$dropdown || !$dropdown.length || !skuList || !skuList.length) {
      return;
    }

    // Build HTML string for all options
    var html = '<option value="">- Pilih SKU -</option>';

    for (var i = 0; i < skuList.length; i++) {
      var sku = skuList[i];
      if (sku && sku.item_code) {
        html += '<option value="' + sku.item_code + '" data-qty="' + (sku.qty || 0) + '">' + sku.item_code + '</option>';
      }
    }

    // Set all HTML at once
    $dropdown.html(html);

    // Refresh Bootstrap Select if it's being used
    if (typeof $dropdown.selectpicker === 'function') {
      $dropdown.selectpicker('refresh');
    }

    // Select if provided
    if (selectedSku) {
      $dropdown.val(selectedSku);

      // Refresh selectpicker again after setting value
      if (typeof $dropdown.selectpicker === 'function') {
        $dropdown.selectpicker('refresh');
      }

      $dropdown.trigger('change');
    }
  }

  /**
   * Sync receipt information from server
   * @param {String} formType - 'refund' or 'replacement'
   */
  function syncReceiptInfo(formType) {
    // Get form elements based on type
    var elements = getFormElements(formType);
    if (!elements) {
      return;
    }

    var noresi = elements.$noresiInput.val().trim().toUpperCase();

    if (!noresi || noresi.length < 3) {
      showNoty('Masukkan nomor resi terlebih dahulu', 'warning');
      return;
    }

    // Show loading state
    elements.$syncBtn.prop('disabled', true);
    elements.$syncBtn.find('i').removeClass('fa-refresh').addClass('fa-spinner fa-spin');

    $.ajax({
      url: '<?= base_url('retur/get_receipt_info') ?>',
      method: 'POST',
      data: { noresi: noresi },
      dataType: 'json',
      success: function(response) {
        if (response.success && response.data) {
          fillFormData(elements, response.data, formType);
          showNoty('Data resi berhasil di-sync', 'success');
        } else {
          showNoty(response.message || 'Data resi tidak ditemukan', 'warning');
        }
      },
      error: function(xhr) {
        var message = 'Terjadi kesalahan saat sync data';
        try {
          var response = xhr.responseJSON || JSON.parse(xhr.responseText);
          if (response && response.message) {
            message = response.message;
          }
        } catch (e) {}
        showNoty(message, 'error');
      },
      complete: function() {
        // Restore button state
        elements.$syncBtn.prop('disabled', false);
        elements.$syncBtn.find('i').removeClass('fa-spinner fa-spin').addClass('fa-refresh');
      }
    });
  }

  /**
   * Get form elements based on form type
   * @param {String} formType - 'refund' or 'replacement'
   * @returns {Object|null} Object containing form elements
   */
  function getFormElements(formType) {
    if (formType === 'refund') {
      return {
        $noresiInput: $('#noresi_refund'),
        $customerInput: $('#customer_name_refund'),
        $marketplaceSelect: $('#marketplace_refund'),
        $noPesananInput: $('#no_pesanan_refund'),
        $skuInput: $('#sku_refund'),
        $qtyInput: $('#qty_refund'),
        $syncBtn: $('#form-refund-complain .btn-sync-resi')
      };
    } else if (formType === 'replacement') {
      return {
        $noresiInput: $('#noresi_replacement'),
        $customerInput: $('#customer_name_replacement'),
        $marketplaceSelect: $('#marketplace_replacement'),
        $noPesananInput: $('#no_pesanan_replacement'),
        $skuInput: $('#sku_replacement'),
        $qtyInput: $('#qty_replacement'),
        $syncBtn: $('#form-replacement-complain .btn-sync-resi')
      };
    }
    return null;
  }

  /**
   * Fill form with synced data
   * @param {Object} elements - Form elements object
   * @param {Object} data - Data from server
   * @param {String} formType - 'refund' or 'replacement'
   */
  function fillFormData(elements, data, formType) {
    // Fill customer name
    if (data.customer_name) {
      elements.$customerInput.val(data.customer_name);
    }

    // Fill marketplace
    if (data.marketplace) {
      elements.$marketplaceSelect.val(data.marketplace).trigger('change');
    }

    // Fill no pesanan
    if (data.no_pesanan) {
      elements.$noPesananInput.val(data.no_pesanan);
    }

    // Populate SKU dropdown (qty will be auto-set by SKU change event)
    populateSkuDropdown(elements.$skuInput, data.sku_list, data.sku);

    // For replacement form, also populate replacement SKU dropdown
    if (formType === 'replacement' && data.sku_list) {
      var $replacementSkuInput = $('#replacement_sku');
      if ($replacementSkuInput.length) {
        populateSkuDropdown($replacementSkuInput, data.sku_list, null);
      }
    }
  }

  // Setup sync button click handlers
  $('.btn-sync-resi').on('click', function() {
    var formType = $(this).data('form');
    syncReceiptInfo(formType);
  });

  // Handle ENTER key in No Resi fields - trigger sync instead of submit
  $('#noresi_refund, #noresi_replacement').on('keydown', function(e) {
    if (e.keyCode == 13) {
      e.preventDefault();
      var formType = $(this).attr('id').includes('refund') ? 'refund' : 'replacement';
      syncReceiptInfo(formType);
      return false;
    }
  });

  // Auto-update quantity when SKU is selected
  $('#sku_refund, #sku_replacement').on('change', function() {
    var $dropdown = $(this);
    var $qtyInput = $dropdown.closest('.row').find('input[name="qty"]');
    var selectedOption = $dropdown.find('option:selected');
    var qty = selectedOption.attr('data-qty');

    if (qty && $qtyInput.length) {
      $qtyInput.val(qty);
    }
  });

  $('#form-refund-complain').on('submit', function(e) {
    e.preventDefault();
    e.stopImmediatePropagation(); // Prevent plugins.js formSubmit handler
    var $form = $(this);
    var $btn = $form.find('button[type="submit"]');
    var noresi = $('#noresi_refund').val().trim().toUpperCase();

    if (!noresi) {
      showNoty('Nomor resi harus diisi', 'warning');
      return;
    }

    $btn.prop('disabled', true).text('Memeriksa...');

    // First check if same type (refund) already exists
    $.ajax({
      url: 'retur/check-complain-exists',
      method: 'POST',
      data: { noresi: noresi, type: 'refund' },
      dataType: 'text',
      success: function(responseText) {
        try {
          var jsonMatch = responseText.match(/\{[\s\S]*\}/);
          if (jsonMatch) {
            responseText = jsonMatch[0];
          }
          var checkSameType = JSON.parse(responseText);

          if (checkSameType.exists) {
            // Same type exists, check status
            if (checkSameType.status === 'TO_DO') {
              // Show confirmation popup for updating same type
              if (!confirm('No Resi ini telah melakukan proses Refund Dana dengan status TO_DO. Apakah Anda yakin ingin mengubah/update data?')) {
                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Refund');
                return;
              }
              // If confirmed, proceed with update
              submitRefundForm($form, $btn, true, checkSameType.id);
            } else {
              // Status is not TO_DO, cannot change
              showNoty('No Resi ini telah melakukan proses Refund Dana dengan status ' + checkSameType.status_label + '. Tidak dapat diubah.', 'error');
              $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Refund');
            }
            return;
          }

          // No same type, check if different type (replacement) exists
          $.ajax({
            url: 'retur/check-complain-exists',
            method: 'POST',
            data: { noresi: noresi, type: 'replacement' },
            dataType: 'text',
            success: function(responseText2) {
              try {
                var jsonMatch2 = responseText2.match(/\{[\s\S]*\}/);
                if (jsonMatch2) {
                  responseText2 = jsonMatch2[0];
                }
                var checkDiffType = JSON.parse(responseText2);

                if (checkDiffType.exists) {
                  // Different type exists, check status
                  if (checkDiffType.status === 'TO_DO') {
                    // Show confirmation popup for changing type
                    if (!confirm('No Resi ini telah melakukan proses Pergantian Barang dengan status TO_DO. Apakah Anda yakin ingin mengubah ke Refund Dana?')) {
                      $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Refund');
                      return;
                    }
                    // If confirmed, proceed with update (change type)
                    submitRefundForm($form, $btn, true, checkDiffType.id);
                  } else {
                    // Status is not TO_DO, cannot change
                    showNoty('No Resi ini telah melakukan proses Pergantian Barang dengan status ' + checkDiffType.status_label + '. Tidak dapat diubah.', 'error');
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Refund');
                  }
                } else {
                  // No existing complain at all, proceed with insert
                  submitRefundForm($form, $btn, false, null);
                }
              } catch (e) {
                console.error('JSON parse error in check diff type:', e);
                showNoty('Terjadi kesalahan saat memeriksa data', 'error');
                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Refund');
              }
            },
            error: function(xhr) {
              showNoty('Terjadi kesalahan saat memeriksa data', 'error');
              $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Refund');
            }
          });
        } catch (e) {
          console.error('JSON parse error in check same type:', e);
          showNoty('Terjadi kesalahan saat memeriksa data', 'error');
          $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Refund');
        }
      },
      error: function(xhr) {
        showNoty('Terjadi kesalahan saat memeriksa data', 'error');
        $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Refund');
      }
    });
  });

  function submitRefundForm($form, $btn, isUpdate, complainId) {
    $btn.text('Menyimpan...');

    var formData = $form.serialize();
    if (isUpdate && complainId) {
      formData += '&update_existing=1&complain_id=' + complainId;
    }

    $.ajax({
      url: 'retur/save-refund-complain',
      method: 'POST',
      data: formData,
      dataType: 'text',
      success: function(responseText) {
        try {
          var jsonMatch = responseText.match(/\{[\s\S]*\}/);
          if (jsonMatch) {
            responseText = jsonMatch[0];
          }

          var response = JSON.parse(responseText);

          if (response && response.message) {
            showNoty(response.message, 'success');
            $form.trigger('reset');
          }
        } catch (e) {
          console.error('JSON parse error:', e);
          console.error('Response text:', responseText);
          showNoty('Terjadi kesalahan saat memproses response', 'error');
        }
      },
      error: function(xhr) {
        var response = {};
        try {
          var responseText = xhr.responseText || '';
          var jsonMatch = responseText.match(/\{[\s\S]*\}/);
          if (jsonMatch) {
            response = JSON.parse(jsonMatch[0]);
          } else if (xhr.responseJSON) {
            response = xhr.responseJSON;
          }
        } catch (e) {
          console.error('Error parsing error response:', e);
        }
        showNoty(response.message || 'Terjadi kesalahan', 'error');
      },
      complete: function() {
        $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Refund');
      }
    });
  }

  $('#form-replacement-complain').on('submit', function(e) {
    e.preventDefault();
    e.stopImmediatePropagation(); // Prevent plugins.js formSubmit handler
    var $form = $(this);
    var $btn = $form.find('button[type="submit"]');
    var noresi = $('#noresi_replacement').val().trim().toUpperCase();

    if (!noresi) {
      showNoty('Nomor resi harus diisi', 'warning');
      return;
    }

    $btn.prop('disabled', true).text('Memeriksa...');

    // First check if same type (replacement) already exists
    $.ajax({
      url: 'retur/check-complain-exists',
      method: 'POST',
      data: { noresi: noresi, type: 'replacement' },
      dataType: 'text',
      success: function(responseText) {
        try {
          var jsonMatch = responseText.match(/\{[\s\S]*\}/);
          if (jsonMatch) {
            responseText = jsonMatch[0];
          }
          var checkSameType = JSON.parse(responseText);

          if (checkSameType.exists) {
            // Same type exists, check status
            if (checkSameType.status === 'TO_DO') {
              // Show confirmation popup for updating same type
              if (!confirm('No Resi ini telah melakukan proses Pergantian Barang dengan status TO_DO. Apakah Anda yakin ingin mengubah/update data?')) {
                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Pergantian');
                return;
              }
              // If confirmed, proceed with update
              submitReplacementForm($form, $btn, true, checkSameType.id);
            } else {
              // Status is not TO_DO, cannot change
              showNoty('No Resi ini telah melakukan proses Pergantian Barang dengan status ' + checkSameType.status_label + '. Tidak dapat diubah.', 'error');
              $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Pergantian');
            }
            return;
          }

          // No same type, check if different type (refund) exists
          $.ajax({
            url: 'retur/check-complain-exists',
            method: 'POST',
            data: { noresi: noresi, type: 'refund' },
            dataType: 'text',
            success: function(responseText2) {
              try {
                var jsonMatch2 = responseText2.match(/\{[\s\S]*\}/);
                if (jsonMatch2) {
                  responseText2 = jsonMatch2[0];
                }
                var checkDiffType = JSON.parse(responseText2);

                if (checkDiffType.exists) {
                  // Different type exists, check status
                  if (checkDiffType.status === 'TO_DO') {
                    // Show confirmation popup for changing type
                    if (!confirm('No Resi ini telah melakukan proses Refund Dana dengan status TO_DO. Apakah Anda yakin ingin mengubah ke Pergantian Barang?')) {
                      $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Pergantian');
                      return;
                    }
                    // If confirmed, proceed with update (change type)
                    submitReplacementForm($form, $btn, true, checkDiffType.id);
                  } else {
                    // Status is not TO_DO, cannot change
                    showNoty('No Resi ini telah melakukan proses Refund Dana dengan status ' + checkDiffType.status_label + '. Tidak dapat diubah.', 'error');
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Pergantian');
                  }
                } else {
                  // No existing complain at all, proceed with insert
                  submitReplacementForm($form, $btn, false, null);
                }
              } catch (e) {
                console.error('JSON parse error in check diff type:', e);
                showNoty('Terjadi kesalahan saat memeriksa data', 'error');
                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Pergantian');
              }
            },
            error: function(xhr) {
              showNoty('Terjadi kesalahan saat memeriksa data', 'error');
              $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Pergantian');
            }
          });
        } catch (e) {
          console.error('JSON parse error in check same type:', e);
          showNoty('Terjadi kesalahan saat memeriksa data', 'error');
          $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Pergantian');
        }
      },
      error: function(xhr) {
        showNoty('Terjadi kesalahan saat memeriksa data', 'error');
        $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Pergantian');
      }
    });
  });

  function submitReplacementForm($form, $btn, isUpdate, complainId) {
    $btn.text('Menyimpan...');

    var formData = $form.serialize();
    if (isUpdate && complainId) {
      formData += '&update_existing=1&complain_id=' + complainId;
    }

    $.ajax({
      url: 'retur/save-replacement-complain',
      method: 'POST',
      data: formData,
      dataType: 'text',
      success: function(responseText) {
        try {
          var jsonMatch = responseText.match(/\{[\s\S]*\}/);
          if (jsonMatch) {
            responseText = jsonMatch[0];
          }

          var response = JSON.parse(responseText);

          if (response && response.message) {
            showNoty(response.message, 'success');
            $form.trigger('reset');
          }
        } catch (e) {
          console.error('JSON parse error:', e);
          console.error('Response text:', responseText);
          showNoty('Terjadi kesalahan saat memproses response', 'error');
        }
      },
      error: function(xhr) {
        var response = {};
        try {
          var responseText = xhr.responseText || '';
          var jsonMatch = responseText.match(/\{[\s\S]*\}/);
          if (jsonMatch) {
            response = JSON.parse(jsonMatch[0]);
          } else if (xhr.responseJSON) {
            response = xhr.responseJSON;
          }
        } catch (e) {
          console.error('Error parsing error response:', e);
        }
        showNoty(response.message || 'Terjadi kesalahan', 'error');
      },
      complete: function() {
        $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Pergantian');
      }
    });
  }

  // Flag to prevent multiple simultaneous requests
  var isUpdatingStatus = false;
  
  $(document).off('change', '.complain-status-select').on('change', '.complain-status-select', function() {
    // Prevent multiple simultaneous requests
    if (isUpdatingStatus) {
      return;
    }
    
    var id = $(this).data('id');
    var status = $(this).val();
    var $select = $(this);
    var oldStatus = $select.data('old-status');

    if (!id || !status) {
      return;
    }

    // Check if status actually changed
    if (oldStatus === status) {
      return;
    }

    // Store old status to prevent duplicate requests
    $select.data('old-status', status);
    isUpdatingStatus = true;
    $select.prop('disabled', true);

    $.ajax({
      url: 'retur/update-complain-status',
      method: 'POST',
      data: {
        id: id,
        status: status
      },
      dataType: 'text', // Changed to text to handle parsing manually
      success: function(responseText) {
        try {
          // Try to extract JSON from response (in case there's HTML before JSON)
          var jsonMatch = responseText.match(/\{[\s\S]*\}/);
          if (jsonMatch) {
            responseText = jsonMatch[0];
          }
          
          var response = JSON.parse(responseText);
          
          if (response && response.message) {
            showNoty(response.message, 'success');
          }
        } catch (e) {
          console.error('JSON parse error:', e);
          console.error('Response text:', responseText);
          showNoty('Terjadi kesalahan saat memproses response', 'error');
          // Reset select to old value on error
          $select.val(oldStatus);
        }
      },
      error: function(xhr) {
        var response = {};
        try {
          var responseText = xhr.responseText || '';
          var jsonMatch = responseText.match(/\{[\s\S]*\}/);
          if (jsonMatch) {
            response = JSON.parse(jsonMatch[0]);
          } else if (xhr.responseJSON) {
            response = xhr.responseJSON;
          }
        } catch (e) {
          console.error('Error parsing error response:', e);
        }
        showNoty(response.message || 'Terjadi kesalahan', 'error');
        // Reset select to old value on error
        $select.val(oldStatus);
        // Don't reload on error to prevent loop
      },
      complete: function() {
        isUpdatingStatus = false;
        $select.prop('disabled', false);
      }
    });
  });

  function showNoty(message, type) {
    noty({
      text: message,
      layout: 'topRight',
      type: type || 'information',
      timeout: 3000
    });
  }
</script>

