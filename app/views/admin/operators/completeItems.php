<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- ITEMS_STATUS -->
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title" id="myModalLabel">Complete Sale Items</h4>
  </div>
  <div class="modal-body">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <i class="fad fa-info-circle"></i> Informasi Produksi
      </div>
      <div class="panel-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="creator"><i class="fad fa-user-hard-hat"></i> Operator</label>
              <select class="select2" id="creator" style="width:100%;">
                <?php $users = $this->site->getUsers(); ?>
                <?php foreach ($users as $user) : ?>
                  <?php if (!$this->Owner && !$this->Admin) {
                    if (XSession::get('user_id') != $user->id) continue;
                  }

                  $selected = (XSession::get('user_id') == $user->id ? ' selected' : '');
                  ?>
                  <option value="<?= $user->id ?>" <?= $selected ?>><?= $user->fullname ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <?php $disabled = ($isAdmin ? '' : ' disabled'); ?>
              <label for="date">Tanggal Produksi</label>
              <input type="text" class="form-control datetime" id="date" value="<?= date('Y-m-d H:i:s') ?>" <?= $disabled ?>>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="panel panel-primary">
      <div class="panel-heading">
        <i class="fad fa-list"></i> Sale Items List
      </div>
      <div class="panel-body">
        <div id="items_table"></div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <input type="hidden" id="nopg" value="0">
    <label class="alert alert-danger" style="display:none" id="msg"></label>
    <?php
    echo form_button('update', lang('complete'), 'class="btn btn-primary" id="update"'); ?>
  </div>
</div>
<script>
  $(document).ready(function() {
    let items = [];
    let submitted = false;

    mTable = new Tabulator('#items_table', {
      columns: [{
          field: 'id',
          title: 'ID',
          width: '10%'
        },
        {
          field: 'code',
          title: 'Code',
          width: '15%'
        },
        {
          field: 'name',
          title: 'Name',
          width: '20%'
        },
        {
          field: 'quantity',
          title: 'Quantity',
          formatter: function(cell, formatterParams) {
            let val = cell.getValue();
            return formatQuantityRight(val);
          }
        },
        {
          field: 'finished_qty',
          title: 'Finished Qty',
          formatter: function(cell, formatterParams) {
            let val = cell.getValue();
            return formatQuantityRight(val);
          }
        },
        {
          field: 'process_qty',
          title: 'Process Qty',
          formatter: function(cell, formatterParams) {
            let data = cell.getData();
            let val = cell.getValue();
            return input_text('process_qty[]', val,
              `class="form-control text-right" data-saleitem-id="${data.id}" data-quantity="${data.quantity}" data-process-qty="${data.process_qty}" id="pqty_${data.id}"`);
          }
        },
        {
          field: 'status',
          title: 'Status',
          formatter: function(cell, formatterParams) {
            let val = cell.getValue();
            return render_status(val);
          }
        }
      ],
      layout: 'fitColumns',
      maxHeight: 300,
      resizableColumns: true,
      responsiveLayout: 'hide',
      tableBuilding: function() {
        product_ids = [];
        $('input[name="val[]"]').each(function() {
          if (this.checked) product_ids.push(this.value);
        });
      },
      tableBuilt: function() {
        $.ajax({
          data: {
            <?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>',
            product_ids: JSON.stringify(product_ids)
          },
          error: function() {
            console.log('ajax error');
          },
          method: 'POST',
          success: function(data) {
            if (data && !data.error) {
              mTable.setData(data.data);
            } else {
              addAlert(data.msg, 'danger');
              $('#myModal').modal('hide');
            }
          },
          url: '<?= admin_url("operators/getItemsStatus"); ?>'
        });
      }
    });

    $(document).on('change', 'input[name="process_qty[]"]', function() {
      if (this.value.length == 0 || this.value == 0) this.value = $(this).data('process-qty');
      if (this.value > $(this).data('quantity')) this.value = $(this).data('quantity');
    });

    $(document).on('keydown', 'input[name="process_qty[]"]', function() {
      let code = event.keyCode;
      console.log(code)

      if (code < 48 || code > 57) { // 0 - 9
        if (code != 190 && code != 8 && code != 46) { // 8: BS, 46: DEL, 190: .
          return false;
        }
      }
      return true;
    });

    $('#update').click(function() {
      if (!submitted) submitted = true;
      else return false; // Double submit protection.

      $(this).prop('disabled', true);

      $('input[name="process_qty[]"]').each(function(index, elem) {
        items.push({
          id: elem.dataset.saleitemId,
          quantity: elem.value
        });
      });

      $.ajax({
        data: {
          <?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>',
          items: JSON.stringify(items),
          created_by: $('#creator').val(), // DO NOT USE 'created_by' its ID is used by filter.
          date: $('#date').val(),
          _pg: $('#nopg').val(),
          update: true
        },
        error: function(data) {
          console.warn(data);
        },
        method: 'POST',
        success: function(data) {
          console.log(data);
          if (typeof(data) === 'object' && data.error == 0) {
            addAlert(data.msg, 'success');
            if (oTable) oTable.fnDraw(false); // False. Do not re-sort or re-filter.
            $('#myModal').modal('hide');
          } else if (typeof(data) === 'object' && data.error) {
            addAlert(data.msg, 'danger');
            $('#myModal').modal('hide');
          } else {
            addAlert('Unknown error. Please check console.', 'danger');
            addAlert(data, 'danger');
            $('#myModal').modal('hide');
          }
        },
        url: '<?= admin_url("operators/completeSaleItems"); ?>'
      });
      return false;
    });
  });
</script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>