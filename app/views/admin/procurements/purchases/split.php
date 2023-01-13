<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <button type="button" class="btn btn-xs btn-default no-print pull-right" style="margin-right:15px;" onclick="window.print();">
      <i class="fad fa-print"></i> <?= lang('print'); ?>
    </button>
    <h4 class="modal-title" id="myModalLabel">Split Purchase Order</h4>
  </div>
  <div class="modal-body">
    <div class="col-md-6">
      <div class="form-group">
        <label for="supplier">New Supplier</label>
        <?= form_dropdown('supplier', '', '', 'class="form-control supplier" id="supplier" style="width:100%;" required="required"'); ?>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <?= lang('date', 'date'); ?>
        <?= form_input('date', '', 'class="form-control datetimenow" id="date"'); ?>
      </div>
    </div>
    <div class="table-responsive">
      <table id="Table" class="table table-bordered table-hover table-striped">
        <thead>
          <tr>
            <th style="min-width:30px; width: 30px; text-align: center;">
              <input class="checkbox checkth" type="checkbox" name="check" />
            </th>
            <th><?= lang('code'); ?></th>
            <th><?= lang('name'); ?></th>
            <th style="width:10%;"><?= lang('quantity'); ?></th>
            <th><?= lang('supplier'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if ($items) {
                  foreach ($items as $item) {
                    $purchase = $this->site->getStockPurchaseByID($item->purchase_id);
                    if ( ! $purchase) continue;
                    $supplier = $this->site->getSupplierByID($purchase->supplier_id);
                    if ( ! $supplier) continue;
          ?>
          <tr>
            <td>
              <div class="text-center">
                <input class="checkbox multi-select" type="checkbox"
                  name="items[]" value="<?= $item->id; ?>">
              </div>
            </td>
            <td><?= $item->product_code; ?></td>
            <td><?= $item->product_name; ?></td>
            <td>
              <input type="text" class="form-control currency text-right"
                data-stock-id="<?= $item->id; ?>"
                value="<?= formatDecimal($item->purchased_qty); ?>">
            </td>
            <td><?= $supplier->company . ' (' . $supplier->name . ')'; ?></td>
          </tr>
          <?php   }
                } ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="modal-footer">
    <?php echo form_submit('add_po', 'Add New PO', 'class="btn btn-primary" id="add_po"'); ?>
  </div>
</div>
<script src="<?= $assets ?>js/modal.js"></script>
<script>
  $(document).ready(function() {
    $('#add_po').click(function () {
      let data = {};

      if (parseInt($('#supplier').val()) <= 0) {
        Notify.error('Supplier tidak ada. Mohon pilih Supplier.');
        return false;
      }

      data.item = [];

      $('input[name="items[]"]').each(function() {
        if ($(this).is(':checked')) {
          let item = {
            stock_id: $(this).val(),
            quantity: $(`input[data-stock-id="${$(this).val()}"]`).val()
          };
          data.item.push(item);
        }
      });

      data.date     = $('#date').val();
      data.supplier = $('#supplier').val();

      data[security.csrf_token_name] = security.csrf_hash;

      httpPost(site.base_url + 'procurements/purchases/split', data, {
        error: (data) => {
          console.warn(data);
        },
        success: function (data) {
          if (typeof data == 'object' && ! data.error) {
            addAlert(data.msg, 'success');
            if (oTable) oTable.fnDraw(false);
          } else if (typeof data == 'object' && data.error) {
            addAlert(data.msg, 'danger');
          } else {
            addAlert('Unknown error.', 'danger');
          }

          $('#myModal').modal('hide');
        }
      });
    });

    $('.datetimenow').datetimepicker({
      format: site.dateFormats.js_ldate,
      fontAwesome: true,
      language: 'sma',
      weekStart: 1,
      todayBtn: 1,
      autoclose: 1,
      todayHighlight: 1,
      minView: 2
    }).datetimepicker('update', new Date());

    $('#supplier').select2({
      minimumInputLength: 1,
      ajax: {
        url: site.base_url + 'suppliers/suggestions',
        dataType: 'json',
        delay: 1000,
        data: function(params) {
          return {
            term: params.term,
            limit: 10,
          };
        },
        processResults: function(data) {
          if (data.results) {
            return { results: data.results };
          } else {
            return { results: [{ id: '', text: 'No Match Found' }] };
          }
        },
      },
      theme: 'classic'
    });
  });
</script>