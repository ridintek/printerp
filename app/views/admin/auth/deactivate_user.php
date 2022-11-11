<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
      <h4 class="modal-title" id="myModalLabel"><?php echo lang('deactivate'); ?></h4>
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
    echo admin_form_open('auth/deactivate/' . $user->id, $attrib); ?>
    <div class="modal-body">
      <p><?php echo sprintf(lang('deactivate_heading'), $user->username); ?></p>

      <div class="form-group">
        <label class="checkbox" for="confirm">
          <input type="checkbox" name="confirm" value="yes" id="confirm"/> <?= lang('yes') ?>
        </label>
      </div>

      <?php echo form_hidden($csrf); ?>
      <?php echo form_hidden(['id' => $user->id]); ?>

    </div>
    <div class="modal-footer">
      <?php echo form_submit('deactivate', lang('deactivate'), 'class="btn btn-primary"'); ?>
    </div>
  </div>
  <?php echo form_close(); ?>
</div>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
