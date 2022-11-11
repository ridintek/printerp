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
            <input type="text" class="form-control datetime" name="date" value="<?= $this->serverDateTime ?>" <?= ($isAdmin ? '' : ' disabled') ?>>
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
          <fieldset class="scheduler-border well-warning">
            <legend class="scheduler-border well-info">Machine 1</legend>
            <div class="col-md-6">
              <div class="form-group">
                <label for="end_click1">End Click</label>
                <input type="text" id="end_click1" class="form-control separator" name="end_click[]" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="mc_reject1">Reject Machine</label>
                <input type="text" id="mc_reject1" class="form-control separator" name="mc_reject[]">
              </div>
            </div>
          </fieldset>

          <fieldset class="scheduler-border well-warning">
            <legend class="scheduler-border well-info">Machine 2</legend>
            <div class="col-md-6">
              <div class="form-group">
                <label for="end_click2">End Click</label>
                <input type="text" id="end_click2" class="form-control separator" name="end_click[]">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="mc_reject2">Reject Machine</label>
                <input type="text" id="mc_reject2" class="form-control separator" name="mc_reject[]">
              </div>
            </div>
          </fieldset>

          <fieldset class="scheduler-border well-warning">
            <legend class="scheduler-border well-info">Machine 3</legend>
            <div class="col-md-6">
              <div class="form-group">
                <label for="end_click3">End Click</label>
                <input type="text" id="end_click3" class="form-control separator" name="end_click[]">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="mc_reject3">Reject Machine</label>
                <input type="text" id="mc_reject3" class="form-control separator" name="mc_reject[]">
              </div>
            </div>
          </fieldset>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <label>Reject Operator otomatis dihitung berdasarkan balance yang minus dikurang Reject Mesin</label>
        </div>
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
<script defer src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script>
  $(document).ready(function() {
    let validated = false;

    bootstrapValidate('#podcategory', 'required:POD Category harus diisi.', (isValid) => {
      validated = (isValid ? true : false);
    });
    bootstrapValidate('#end_click1',
      'required:Harus diisi sesuai attachment.|numeric:Harus angka.',
      (isValid) => {
        validated = (isValid ? true : false);
      });
    bootstrapValidate('#mc_reject1',
      'numeric:Harus angka.',
      (isValid) => {
        validated = (isValid ? true : false);
      });

    $('#submit').click(function() {
      let form = new FormData(document.getElementById('form'));
      let endClick = [];

      if ($('#end_click1').val().length) endClick.push($('#end_click1').val());
      if ($('#end_click2').val().length) endClick.push($('#end_click2').val());
      if ($('#end_click3').val().length) endClick.push($('#end_click3').val());

      addConfirm({
        title: 'Konfirmasi penambahan Tracking POD',
        message: `Yakin dengan END CLICK (${endClick.join(' | ')})?`,
        onok: () => {
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
            url: site.base_url + 'trackingpod/add'
          });
        }
      });
    });
  });
</script>