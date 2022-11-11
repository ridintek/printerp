<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
    <h4 class="modal-title text-center" id="myModalLabel"><i class="fad fa-clock"></i> Add Holiday</h4>
  </div>
  <div class="modal-body">
    <form id="form" enctype="multipart/form-data">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="biller">Biller</label>
            <select class="select2" id="biller" data-placeholder="Pilih Biller" name="biller[]" style="width:100%;" multiple>
              <option value=""></option>
              <?php $billers = $this->site->getBillers(); ?>
              <?php foreach ($billers as $biller) : ?>
                <option value="<?= $biller->id ?>"><?= $biller->name ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <fieldset class="scheduler-border well-danger">
            <legend class="scheduler-border well-info">Holiday</legend>
            <div class="col-md-6">
              <div class="form-group">
                <label for="holidayfrom">From</label>
                <input type="date" id="holiday_from" class="form-control" name="holiday[]">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="holiday_to">To</label>
                <input type="date" id="holiday_to" class="form-control" name="holiday[]">
              </div>
            </div>
          </fieldset>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <fieldset class="scheduler-border well-warning">
            <legend class="scheduler-border well-info">Working Hour</legend>
            <div class="col-md-6">
              <div class="form-group">
                <label for="working_from">From</label>
                <input type="time" id="working_from" class="form-control" name="working[]">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="working_to">To</label>
                <input type="time" id="working_to" class="form-control" name="working[]">
              </div>
            </div>
          </fieldset>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <label for="description">Description</label>
          <textarea id="description" class="form-control" name="description"></textarea>
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
    $('#submit').click(function() {
      let form = new FormData(document.getElementById('form'));

      $.ajax({
        contentType: false,
        data: form,
        error: (xhr) => {
          toastr.error(xhr.responseJSON.message, xhr.responseJSON.title);
        },
        method: 'POST',
        processData: false,
        success: (data) => {
          if (isObject(data)) {
            if (data.status >= 200 && data.status < 300) {
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
        url: site.base_url + 'schedule/holiday/add'
      });
    });
  });
</script>