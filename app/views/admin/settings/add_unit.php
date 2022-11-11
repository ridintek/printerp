<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fa fa-times"></i>
    </button>
    <h4 class="modal-title" id="myModalLabel"><?php echo lang('add_unit'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
  echo admin_form_open('system_settings/add_unit', $attrib); ?>
  <div class="modal-body">
    <p><?= lang('enter_info'); ?></p>
    <div class="form-group">
      <?= lang('unit_code', 'code'); ?>
      <?= form_input('code', set_value('code'), 'class="form-control tip" id="code" required="required"'); ?>
    </div>
    <div class="form-group">
      <?= lang('unit_name', 'name'); ?>
      <?= form_input('name', set_value('name'), 'class="form-control tip" id="name" required="required"'); ?>
    </div>
    <div class="form-group">
      <?= lang('base_unit', 'base_unit'); ?>
      <?php
      $opts = [];
      $opts[''] = 'Select Base Unit';
      if ( ! empty($base_units)) {
        foreach ($base_units as $bu) {
          $opts[$bu->code] = $bu->name . ' (' . $bu->code . ')';
        }
      }
      ?>
      <?= form_dropdown('base_unit', $opts, set_value('base_unit'), 'class="select2" id="base_unit" style="width:100%;"'); ?>
    </div>
    <div id="measuring" style="display:none;">
      <div class="form-group">
        <?= lang('operator', 'operator'); ?>
        <?php
        $oopts = ['*' => lang('*'), '/' => lang('/'), '+' => lang('+'), '-' => lang('-')];
        ?>
        <?= form_dropdown('operator', $oopts, set_value('operator'), 'class="select2" id="operator" style="width:100%;"'); ?>
      </div>
      <div class="form-group">
        <?= lang('operation_value', 'operation_value'); ?>
        <?= form_input('operation_value', set_value('operation_value'), 'class="form-control tip" id="operation_value"'); ?>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <?php echo form_submit('add_unit', lang('add_unit'), 'class="btn btn-primary"'); ?>
  </div>
</div>
<?php echo form_close(); ?>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script type="text/javascript">
  $(document).ready(function() {
    $('#base_unit').change(function(e) {
      var bu = $(this).val();
      if(bu.length > 0) {
        $('#measuring').slideDown();
      } else {
        $('#measuring').slideUp();
      }
    });
  });
</script>