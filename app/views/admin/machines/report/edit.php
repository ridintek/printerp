<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
    <h4 class="modal-title text-center" id="myModalLabel">Edit Report [<?= $product->code ?>]</h4>
  </div>
  <div class="modal-body">
    <form id="form" data-toggle="validator" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="created_by">Created By</label>
            <select class="select2" id="created_by" name="created_by" style="width:100%;">
              <?php $users = $this->site->getUsers(); ?>
              <?php foreach ($users as $user) :
                if (!$isAdmin) {
                  if ($user->id != XSession::get('user_id')) continue;
                }
              ?>
                <option value="<?= $user->id ?>"><?= $user->fullname ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="created_at">Created At</label>
            <input type="datetime-local" id="created_at" class="form-control" name="created_at" <?= ($isAdmin ? '' : ' disabled') ?>>
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
                if (!$isAdmin) {
                  if (XSession::get('warehouse_id')) {
                    if ($warehouse->id != XSession::get('warehouse_id')) continue;
                  }
                }

                $selected = (strcasecmp($warehouse->name, $product->warehouses) === 0 ? ' selected' : '');
              ?>
                <option value="<?= $warehouse->id ?>" <?= $selected ?>><?= $warehouse->name ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-group">
            <label for="condition">Condition</label>
            <select class="select2" id="condition" name="condition" data-placeholder="Select Condition" style="width:100%;">
              <option value=""></option>
              <option value="good">Good (Baik)</option>
              <option value="off">Off (Mati)</option>
              <option value="solved">Solved (Terselesaikan)</option>
              <option value="trouble">Trouble (Bermasalah)</option>
            </select>
          </div>
        </div>
      </div>

      <div class="row assign-ts" style="display:none">
        <div class="col-md-6">
          <div class="form-group">
            <label for="pic">Assign Team Support</label>
            <select class="select2" id="pic" name="pic" data-placeholder="Pilih TS" style="width:100%;">
              <option value=""></option>
              <?php $users = $this->site->getUsers(['active' => 1]); ?>
              <?php foreach ($users as $user) :
                $userGroup = $this->site->getUserGroup($user->id);

                if (strcasecmp($userGroup->name, 'SUPPORT') != 0) continue;
              ?>
                <option value="<?= $user->id ?>" data-group="<?= $userGroup->name ?>"><?= $user->fullname ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>Assigned At</label>
            <input type="datetime-local" class="form-control" id="assigned_at" name="assigned_at">
          </div>
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
            <label for="note">Notes by User</label>
            <textarea class="form-control" name="note"><?= $report->note ?></textarea>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
          <div class="form-group">
            <label for="note">Notes by PIC/TS</label>
            <textarea class="form-control" name="pic_note"><?= $report->pic_note ?></textarea>
          </div>
        </div>
      </div>
    </form>
  </div>
  <div class="modal-footer">
    <button class="btn btn-danger" data-dismiss="modal">Cancel</button>
    <button id="submit" class="btn btn-primary">Edit</button>
  </div>
</div>
<script defer src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script>
  $(document).ready(function() {
    $('#assigned_at').val(dateTime('<?= $productJS->assigned_at ?>'));
    $('#assigned_by').val('<?= $productJS->assigned_by ?>').trigger('change');
    $('#created_at').val(dateTime('<?= $report->created_at ?>'));
    $('#created_by').val('<?= $report->created_by ?>').trigger('change');

    $('#condition').change(function() {
      if (this.value != 'good') {
        $('.assign-ts').slideDown();
      } else {
        $('.assign-ts').slideUp();
      }
    });

    $('#pic').val('<?= $productJS->pic_id ?>').trigger('change');
    $('#condition').val('<?= $report->condition ?>').trigger('change');

    $('#submit').click(function() {
      let form = new FormData(document.getElementById('form'));

      $.ajax({
        contentType: false,
        data: form,
        error: (xhr) => {
          addAlert(xhr.responseJSON.message, 'danger');
          toastr.error(xhr.responseJSON.message);
        },
        method: 'POST',
        processData: false,
        success: function(data) {
          if (Table) Table.draw(false);
          addAlert(data.message, 'success');
          toastr.success(data.message);

          $('#myModal').modal('hide');

          $('#myModal2').modal('hide');
        },
        url: site.base_url + 'machines/report/edit/<?= $report->id ?>'
      })
    });
  });
</script>