<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
    <h4 class="modal-title text-center" id="myModalLabel">Add Purchase</h4>
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
                  if ($user->id != $this->session->userdata('user_id')) continue;
                }
              ?>
                <option value="<?= $user->id ?>"><?= $user->first_name . ' ' . $user->last_name ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="date">Date</label>
            <input type="text" class="form-control datetime" id="date" name="created_at" value="<?= $this->serverDateTime ?>" <?= ($isAdmin ? '' : ' disabled') ?>>
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
                    if ($warehouse->id != $this->session->userdata('warehouse_id')) continue;
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
                    if ($warehouse->id != $this->session->userdata('warehouse_id')) continue;
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
                <table id="ProductList" class="table table-condensed table-bordered table-hover table-striped">
                  <thead>
                    <tr>
                      <th style="width:60px">Action</th>
                      <th>Code</th>
                      <th>Name</th>
                      <th style="width:150px">Quantity</th>
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
            <textarea class="form-control" name="note"></textarea>
          </div>
        </div>
      </div>
      <?= csrf_field() ?>
    </form>
  </div>
  <div class="modal-footer">
    <button class="btn btn-danger" data-dismiss="modal">Cancel</button>
    <button id="submit" class="btn btn-primary">Add</button>
  </div>
</div>
<script src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script>
  $(function() {
    let purchase = new Purchase('#ProductList');
    let q = '';

    purchase.setMode('add');

    q += 'warehouse=' + $('#from_warehouse').val();

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

      // TODO: Insert new item list.
      purchase.addItem(e.params.data);
    });

    $('#from_warehouse').on('change', function() {
      q = 'warehouse=' + $(this).val();
    });

    // $('table#PMList tbody').sortable();

    $('#submit').click(function() {
      let formData = new FormData(document.getElementById('form'));

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
        url: site.base_url + 'procurements/purchases2/add'
      })
    });
  });
</script>