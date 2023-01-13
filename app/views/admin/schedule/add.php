<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
    <h4 class="modal-title text-center" id="myModalLabel"><i class="fad fa-clock"></i> Add Schedule</h4>
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
          <fieldset class="scheduler-border well-warning">
            <legend class="scheduler-border well-info">Minggu</legend>
            <div class="col-md-6">
              <div class="form-group">
                <label for="sun_from">From</label>
                <input type="time" id="sun_from" class="form-control" name="sun[]">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="sun_to">To</label>
                <input type="time" id="sun_to" class="form-control" name="sun[]">
              </div>
            </div>
          </fieldset>

          <fieldset class="scheduler-border well-warning">
            <legend class="scheduler-border well-info">Senin</legend>
            <div class="col-md-6">
              <div class="form-group">
                <label for="mon_from">From</label>
                <input type="time" id="mon_from" class="form-control time" name="mon[]">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="mon_to">To</label>
                <input type="time" id="mon_to" class="form-control time" name="mon[]">
              </div>
            </div>
          </fieldset>

          <fieldset class="scheduler-border well-warning">
            <legend class="scheduler-border well-info">Selasa</legend>
            <div class="col-md-6">
              <div class="form-group">
                <label for="tue_from">From</label>
                <input type="time" id="tue_from" class="form-control time" name="tue[]">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="tue_to">To</label>
                <input type="time" id="tue_to" class="form-control time" name="tue[]">
              </div>
            </div>
          </fieldset>

          <fieldset class="scheduler-border well-warning">
            <legend class="scheduler-border well-info">Rabu</legend>
            <div class="col-md-6">
              <div class="form-group">
                <label for="wed_from">From</label>
                <input type="time" id="wed_from" class="form-control time" name="wed[]">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="wed_to">To</label>
                <input type="time" id="wed_to" class="form-control time" name="wed[]">
              </div>
            </div>
          </fieldset>

          <fieldset class="scheduler-border well-warning">
            <legend class="scheduler-border well-info">Kamis</legend>
            <div class="col-md-6">
              <div class="form-group">
                <label for="thu_from">From</label>
                <input type="time" id="thu_from" class="form-control time" name="thu[]">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="thu_to">To</label>
                <input type="time" id="thu_to" class="form-control time" name="thu[]">
              </div>
            </div>
          </fieldset>

          <fieldset class="scheduler-border well-warning">
            <legend class="scheduler-border well-info">Jum'at</legend>
            <div class="col-md-6">
              <div class="form-group">
                <label for="fri_from">From</label>
                <input type="time" id="fri_from" class="form-control time" name="fri[]">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="fri_to">To</label>
                <input type="time" id="fri_to" class="form-control time" name="fri[]">
              </div>
            </div>
          </fieldset>

          <fieldset class="scheduler-border well-warning">
            <legend class="scheduler-border well-info">Sabtu</legend>
            <div class="col-md-6">
              <div class="form-group">
                <label for="sat_from">From</label>
                <input type="time" id="sat_from" class="form-control time" name="sat[]">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="sat_to">To</label>
                <input type="time" id="sat_to" class="form-control time" name="sat[]">
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
        url: site.base_url + 'schedule/add'
      });
    });
  });
</script>