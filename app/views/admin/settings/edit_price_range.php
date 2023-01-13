<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-2x">&times;</i>
      </button>
      <h4 class="modal-title" id="myModalLabel"><?php echo lang('Edit Price Range'); ?></h4>
    </div>
    <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
    echo admin_form_open('system_settings/edit_price_range/' . $id, $attrib); ?>
    <div class="modal-body">
      <p><?= lang('enter_info'); ?></p>

      <div class="form-group">
        <label class="control-label" for="name"><?php echo $this->lang->line('Range Name'); ?></label>
        <?php echo form_input('name', $price_range->name, 'class="form-control" id="name" required="required"'); ?>
      </div>
      
    </div>
    <div class="modal-footer">
      <?php echo form_submit('edit_price_range', lang('Edit Price Range'), 'class="btn btn-primary"'); ?>
    </div>
  </div>
  <?php echo form_close(); ?>
</div>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>