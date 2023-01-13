<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
    <h4 class="modal-title text-center" id="myModalLabel"><?= ucfirst($mode) ?> Product Mutation</h4>
  </div>
  <div class="modal-body">
    <form id="form" enctype="multipart/form-data">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="created_by">Created By</label>
            <select class="select2" id="created_by" name="created_by" style="width:100%;">
              <?php $users = $this->site->getUsers(); ?>
              <?php foreach ($users as $user) :
                if (!$isAdmin) {
                  if ($user->id != $pm->created_by) continue;
                }
              ?>
                <option value="<?= $user->id ?>"><?= $user->fullname ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="date">Date</label>
            <input type="datetime-local" class="form-control" id="date" name="created_at" value="<?= dtJS($pm->created_at) ?>" <?= ($isAdmin ? '' : ' disabled') ?>>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="from_warehouse">From Warehouse</label>
            <select class="select2" id="from_warehouse" name="from_warehouse" style="width:100%;">
              <?php $warehouses = $this->site->getWarehouses(['active' => 1, 'order' => ['name', 'ASC']]); ?>
              <?php foreach ($warehouses as $warehouse) :
                $selected = '';

                if (!$isAdmin) {
                  if ($this->session->userdata('warehouse_id')) {
                    if ($warehouse->id != $pm->from_warehouse_id) continue;
                  }
                } else {
                  if ($warehouse->code == 'LUC') $selected = ' selected';
                }
              ?>
                <option value="<?= $warehouse->id ?>" <?= $selected ?>><?= $warehouse->name ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="to_warehouse">To Warehouse</label>
            <select class="select2" id="to_warehouse" name="to_warehouse" style="width:100%;">
              <?php $warehouses = $this->site->getWarehouses(['active' => 1, 'order' => ['name', 'ASC']]); ?>
              <?php foreach ($warehouses as $warehouse) :
                $selected = '';

                if (!$isAdmin) {
                  if ($this->session->userdata('warehouse_id')) {
                    if ($warehouse->id != $pm->to_warehouse_id) continue;
                  }
                } else {
                  if ($warehouse->code == 'LUC') $selected = ' selected';
                }
              ?>
                <option value="<?= $warehouse->id ?>" <?= $selected ?>><?= $warehouse->name ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label for="status">Status</label>
            <select class="select2" id="status" name="status" style="width:100%;">
              <option value="pending">Pending</option>
              <option value="sent">Sent</option>
              <option value="received">Received</option>
            </select>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <fieldset class="scheduler-border">
            <legend class="scheduler-border well-info">Select Products</legend>
            <div class="col-md-12">
              <select id="product" data-placeholder="Select Product" style="width:100%"></select>
            </div>
          </fieldset>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <fieldset class="scheduler-border">
            <legend class="scheduler-border well-success">Product List</legend>
            <div class="col-md-12">
              <div class="table-responsive">
                <table id="PMList" class="table table-condensed table-bordered table-hover table-striped">
                  <thead>
                    <tr>
                      <th style="width:60px">Action</th>
                      <th>Code</th>
                      <th>Name</th>
                      <th style="width:100px">Total Qty</th>
                      <th style="width:100px">Received Qty</th>
                      <th style="width:100px">Rest Qty</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </fieldset>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="form-group">
            <label for="attachment">Attachment</label>
            <input type="file" class="form-control file" name="attachment" data-browse-label="Browse" data-show-upload="false" data-show-preview="false">
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
          <div class="form-group">
            <label for="note">Note</label>
            <textarea class="form-control" name="note"><?= $pm->note ?></textarea>
          </div>
        </div>
      </div>
      <?= csrf_field() ?>
    </form>
  </div>
  <div class="modal-footer">
    <button class="btn btn-danger" data-dismiss="modal">Cancel</button>
    <button id="submit" class="btn btn-primary">Save</button>
  </div>
</div>
<script src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script>
  $(function() {
    let productMutation = new ProductMutation('#PMList');
    let pmitems = JSON.parse('<?= json_encode($pmitems) ?>');
    let createdBy = '<?= $pm->created_by ?>';
    let fromWarehouse = '<?= $pm->from_warehouse_id ?>';
    let toWarehouse = '<?= $pm->to_warehouse_id ?>';
    let status = '<?= $pm->status ?>';
    let q = '';

    productMutation.setMode('<?= $mode ?>'); // edit, status

    q += 'warehouse=' + $('#from_warehouse').val();

    if (pmitems) {
      for (let pmitem of pmitems) {
        productMutation.addItem(pmitem);
      }
    }

    if (createdBy) $('#created_by').val(createdBy).trigger('change');
    if (fromWarehouse) $('#from_warehouse').val(fromWarehouse).trigger('change');
    if (toWarehouse) $('#to_warehouse').val(toWarehouse).trigger('change');
    if (status) $('#status').val(status).trigger('change');

    if (productMutation.mode != 'status') {
      $('#product').select2({
        ajax: {
          delay: 1000,
          url: () => {
            return site.base_url + 'products/select2?' + q
          }
        }
      });

      $('#product').on('select2:open', function() {
        $(this).empty();
      });

      $('#product').on('select2:select', function(e) {
        $(this).empty();

        productMutation.addItem(e.params.data);
      });
    } else if (productMutation.mode == 'status') {
      $('#product').select2();
    }

    $('#from_warehouse').on('change', function() {
      q = 'warehouse=' + $(this).val();
    });

    $('#submit').click(function() {
      let formData = new FormData(document.getElementById('form'));

      formData.append('mode', '<?= $mode; ?>');

      $.ajax({
        contentType: false,
        data: formData,
        error: (xhr) => {
          toastr.error(xhr.responseJSON.message);

          $('#myModal').modal('hide');
        },
        processData: false,
        method: 'POST',
        success: (data) => {
          if (data.status >= 200 && data.status < 300) {
            toastr.success(data.message);
            if (typeof Table == 'object') Table.draw(false);
          } else {
            toastr.error(data.message);
          }

          $('#myModal').modal('hide');
        },
        url: site.base_url + 'products/mutation/edit/<?= $pm->id ?>'
      })
    });
  });
</script>