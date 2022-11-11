<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel"><?= lang('add_payroll_category'); ?></h4>
  </div>
  <div class="modal-body">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('category_code', 'code'); ?>
          <input type="text" id="code" name="code" class="form-control" required="required">
        </div>
      </div><!-- /.col-md-6 -->
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('category_name', 'name'); ?>
          <input type="text" id="name" name="name" class="form-control" required="required">
        </div>
      </div><!-- /.col-md-6 -->
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('category_type', 'name'); ?>
          <?php
          $opts = [
            '' => 'Select Type',
            'decrease' => 'Decrease',
            'increase' => 'Increase'
          ];

          echo form_dropdown('type', $opts, '', 'class="form-control" id="type" style="width:100%;" required="required"'); ?>
        </div>
      </div><!-- /.col-md-6 -->
    </div><!-- /.row -->
  </div>
  <div class="modal-footer">
    <?php echo form_button('add_category', lang('add_category'), 'class="btn btn-primary" id="submit"'); ?>
  </div>
</div>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function () {
    $('#submit').click(function () {
      let category_data = {
        code: $('#code').val(),
        name: $('#name').val(),
        type: $('#type').val()
      };

      category_data[security.csrf_token_name] = security.csrf_hash;

      $.ajax({
        data: category_data,
        method: 'POST',
        success: function (data) {
          console.log(data);
          if (typeof data === 'object' && ! data.error) {
            addAlert(data.msg, 'success');
            if (oTable) oTable.fnDraw();
          } else if (typeof data === 'object' && data.error) {
            addAlert(data.msg, 'danger');
          }

          $('#myModal').modal('toggle');
        },
        url: site.base_url + 'payrolls/categories/add'
      });
    });
  });
</script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>