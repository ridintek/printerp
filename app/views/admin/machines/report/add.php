<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
    <h4 class="modal-title text-center" id="myModalLabel">Add Report [<?= $product->code ?>]</h4>
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

                if ($userGroup->name != 'support' && $userGroup->name != 'kurir') continue;
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
            <?php if (TRUE) : ?>
              <textarea class="form-control" id="note" name="note"><?= (!empty($lastReport->note) ? $lastReport->note : '') ?></textarea>
            <?php else : ?>
              <div><?= (!empty($lastReport->note) ? $lastReport->note : '-') ?></div>
              <input type="hidden" id="note" name="note" value="<?= (!empty($lastReport->note) ? $lastReport->note : '') ?>">
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
          <div class="form-group">
            <label for="note">Notes by PIC/TS</label>
            <?php if ($isAdmin || XSession::get('group_name') == 'support') : ?>
              <textarea class="form-control" id="pic_note" name="pic_note"><?= (!empty($lastReport->pic_note) ? $lastReport->pic_note : '') ?></textarea>
            <?php else : ?>
              <div><?= (!empty($lastReport->pic_note) ? $lastReport->pic_note : '-') ?></div>
              <input type="hidden" name="pic_note" value="<?= (!empty($lastReport->pic_note) ? $lastReport->pic_note : '') ?>">
            <?php endif; ?>
          </div>
        </div>
      </div>
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
    $('#created_at').val(dateTime('<?= $this->serverDateTime ?>'));
    $('#created_by').val('<?= XSession::get('user_id') ?>').trigger('change');

    $('#condition').change(function() {
      if (this.value != 'good') {
        $('.assign-ts').slideDown();
      } else {
        $('.assign-ts').slideUp();
      }
    });

    $('#submit').click(function() {
      let form = new FormData(document.getElementById('form'));
      let condition = $('#condition');
      let note = $('#note');

      if (condition.val() == 'trouble' || condition.val() == 'off') {
        if (note.val() != '<p>-</p>' && note.val().length < 20) {
          alertify.alert('<h3 class="bold red">PERINGATAN!</h3>',
            `<b>Kerusakan apapun harus mengisi catatan di Note minimal 20 karakter.
            Saat ini ${note.val().length} karakter.</b>`);
          return false;
        }
      }

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
        },
        url: site.base_url + 'machines/report/add/<?= $product->id ?>'
      })
    });
  });
</script>