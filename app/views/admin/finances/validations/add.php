<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-2x">&times;</i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('add_payment_validation'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
  echo admin_form_open_multipart('finances/validations/add', $attrib); ?>
  <div class="modal-body">
    <div class="row">
      <div class="col-xs-12">
        <div class="form-group">
          <?= lang('reference', 'reference'); ?>
          <input type="input" name="reference" class="form-control" id="reference" value="">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-xs-12">
        <div class="form-group">
          <?= lang('amount', 'amount'); ?>
          <input name="amount" type="text" id="amount" value="" class="pa form-control kb-pad currency" required="required"/>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <?php echo form_submit('add_payment_validation', lang('add_payment_validation'), 'class="btn btn-primary"'); ?>
  </div>
</div>
<?php echo form_close(); ?>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script type="text/javascript" charset="UTF-8">
  $.fn.datetimepicker.dates['sma'] = <?=$dp_lang?>;
</script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function () {
  });
</script>
