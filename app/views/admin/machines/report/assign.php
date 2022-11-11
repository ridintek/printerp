<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
    <h4 class="modal-title text-center" id="myModalLabel">Assign TS [<?= $product->code ?>]</h4>
  </div>
  <div class="modal-body">
    <form id="form" data-toggle="validator" enctype="multipart/form-data">
      <div class="row">
        <div class="col-md-12">
          <div class="form-group">
            <label for="pic">Team Support</label>
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
        url: site.base_url + 'machines/report/assign/<?= $product->id ?>'
      })
    });
  });
</script>