<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
    <h4 class="modal-title text-center" id="myModalLabel">Add Tracking POD</h4>
  </div>
  <div class="modal-body">
    <form id="form" data-toggle="validator" enctype="multipart/form-data">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="podcreated_by">Created By</label>
            <select class="select2" id="podcreated_by" name="created_by" style="width:100%;">
              <?php $users = $this->site->getUsers(); ?>
              <?php foreach ($users as $user) :
                $selected = ($track->created_by == $user->id ? ' selected' : '');
              ?>
                <option value="<?= $user->id ?>" <?= $selected ?>><?= $user->first_name . ' ' . $user->last_name ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="date">Date</label>
            <input type="text" class="form-control datetime" name="date" value="<?= $track->created_at ?>">
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="warehouse_id">Warehouse</label>
            <select class="select2" id="warehouse_id" name="warehouse" style="width:100%;">
              <?php $warehouses = $this->site->getAllWarehouses(); ?>
              <?php foreach ($warehouses as $warehouse) :
                $selected = ($track->warehouse_id == $warehouse->id ? ' selected' : '');
              ?>
                <option value="<?= $warehouse->id ?>" <?= $selected ?>><?= $warehouse->name ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-group">
            <label for="podcategory">POD Category</label>
            <select class="select2" id="podcategory" name="category" data-placeholder="Select Category" style="width:100%;">
              <option value=""></option>
              <option value="klikpod">KLIKPOD FULL COLOR</option>
              <option value="klikpodbw">KLIKPOD BW</option>
            </select>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <fieldset class="scheduler-border well-success">
            <legend class="scheduler-border well-info">Editable Data</legend>
            <div class="col-md-4">
              <div class="form-group">
                <label for="end_click">End Click</label>
                <input type="text" id="end_click" class="form-control separator" name="end_click[]" value="<?= formatDecimal($track->end_click) ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="mc_reject">Machine Reject</label>
                <input type="text" id="mc_reject" class="form-control separator" name="mc_reject[]" value="<?= formatDecimal($track->mc_reject * -1) ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="erp_click">ERP Click</label>
                <input type="text" id="erp_click" class="form-control separator" name="erp_click" value="<?= formatDecimal($track->erp_click) ?>">
              </div>
            </div>
          </fieldset>
          <fieldset class="scheduler-border well-warning">
            <legend class="scheduler-border well-info">Read-Only Data</legend>
            <div class="col-md-6">
              <div class="form-group">
                <label for="start_click">Start Click</label>
                <input type="text" id="start_click" class="form-control separator" name="start_click" value="<?= formatDecimal($track->start_click) ?>" disabled>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="usage_click">Usage Click</label>
                <input type="text" id="usage_click" class="form-control separator" name="usage_click" value="<?= formatDecimal($track->usage_click) ?>" disabled>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="op_reject">Operator Reject</label>
                <input type="text" id="op_reject" class="form-control separator" name="op_reject" value="<?= formatDecimal($track->op_reject) ?>" disabled>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="total_reject">Total Reject</label>
                <input type="text" id="total_reject" class="form-control separator" name="total_reject" value="<?= formatDecimal($track->mc_reject + $track->op_reject) ?>" disabled>
              </div>
            </div>
          </fieldset>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <label>Reject Operator otomatis dihitung berdasarkan balance yang minus dikurang Reject Mesin</label>
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
            <textarea class="form-control" name="note"><?= $track->note ?></textarea>
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
<script defer src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script>
  $(document).ready(function() {
    let validated = false;
    let category = '<?= $product->code ?>'.toLowerCase();

    $('#podcategory').val(category).trigger('change');

    $('#submit').click(function() {
      let form = new FormData(document.getElementById('form'));
      let endClick = [];

      $.ajax({
        contentType: false,
        data: form,
        method: 'POST',
        processData: false,
        success: function(data) {
          if (isObject(data)) {
            if (data.success) {
              if (typeof Table == 'object') Table.draw(false);
              addAlert(data.message, 'success');
            } else {
              addAlert(data.message, 'danger');
            }
          } else {
            addAlert('Something wrong here.', 'danger');
          }

          $('#myModal').modal('hide');
        },
        url: site.base_url + 'trackingpod/edit/<?= $track->id ?>'
      });
    });
  });
</script>