<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
    <h4 class="modal-title text-center" id="myModalLabel">Add Whatsapp Profile</h4>
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
                <option value="<?= $user->id ?>"><?= $user->fullname ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="date">Date</label>
            <input type="datetime-local" class="form-control" name="date" value="<?= dtJS($this->serverDateTime) ?>" <?= ($isAdmin ? '' : ' disabled') ?>>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="engine">Engine</label>
            <select class="select2" id="engine" name="engine" data-placeholder="Select Engine" style="width:100%;">
              <option value=""></option>
              <option value="rapiwha">Rapiwha (panel.rapiwha.com)</option>
              <option value="watsap">Watsap (panel.watsap.id)</option>
              <option value="whacenter">Whacenter (app.whacenter.com)</option>
            </select>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <fieldset class="scheduler-border well-warning">
            <legend class="scheduler-border well-info">Profile</legend>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="phone">Phone</label>
                  <input type="text" id="phone" class="form-control" name="phone" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="api_key">API Key</label>
                  <input type="text" id="api_key" class="form-control" name="api_key">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="api_key">Device ID</label>
                  <input type="text" id="api_key" class="form-control" name="api_key">
                </div>
              </div>
            </div>
          </fieldset>
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
        title: 'Tambah profile Whatsapp',
        message: `Tambahkan profile Whatsapp?`,
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
            url: site.base_url + 'whatsapp/profile/add'
          });
        }
      });
    });
  });
</script>