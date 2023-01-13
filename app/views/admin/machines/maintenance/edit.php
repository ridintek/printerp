<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
    <h4 class="modal-title text-center" id="myModalLabel">Edit Schedule [<?= $warehouse->name ?>]</h4>
  </div>
  <div class="modal-body">
    <form id="form" data-toggle="validator" enctype="multipart/form-data">
      <div class="row">
        <div class="col-md-12">
          <table class="table table-condensed">
            <thead>
              <tr>
                <th>Group</th>
                <th>PIC</th>
                <th>Auto Assign</th>
              </tr>
            </thead>
            <tbody>
              <?php $users = $this->site->getUsers(); $x = 1; ?>
              <?php foreach ($this->site->getSubCategories('AST') as $category) : ?>
                <tr>
                  <input type="hidden" name="group[<?= $x ?>][category]" value="<?= $category->code ?>">
                  <td class="text-center"><?= $category->name ?></td>
                  <td class="text-center">
                    <select class="select2" id="pic_<?= strtolower($category->code) ?>" name="group[<?= $x ?>][pic]" data-placeholder="Pilih TS">
                      <option value=""></option>
                      <?php foreach ($users as $user) :
                        $userGroup = $this->site->getUserGroup($user->id);

                        if (strcasecmp($userGroup->name, 'support') !== 0) continue;
                      ?>
                        <option value="<?= $user->id ?>" data-group="<?= $userGroup->name ?>"><?= $user->fullname ?></option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td class="text-center">
                    <div class="form-group">
                      <input type="checkbox" id="assign_<?= strtolower($category->code) ?>" name="group[<?= $x ?>][auto_assign]" value="1">
                    </div>
                  </td>
                </tr>
                <?php $x++; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <input type="hidden" name="<?= csrf_token_name() ?>" value="<?= csrf_hash() ?>">
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
    let whJS= JSON.parse(`<?= $warehouse->json_data ?>`);
    let maintenances = (whJS.maintenances ?? []);

    if (maintenances) {
      for (let schedule of maintenances) {
        $(`#pic_${schedule.category.toLowerCase()}`).val(schedule.pic).trigger('change');
        
        if (schedule.auto_assign == 1) {
          $(`#assign_${schedule.category.toLowerCase()}`).prop('checked', true);
        }
      }
    }

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
        url: site.base_url + 'machines/maintenance/edit/<?= $warehouse->id ?>'
      })
    });
  });
</script>