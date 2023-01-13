<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('reactivate_validation'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
  echo admin_form_open_multipart('finances/validations/reactivate/' . $payment_validation->id, $attrib); ?>
  <div class="modal-body">
    <div class="row">
      <div class="col-xs-12">
        <div class="form-group">
          <?= lang('reference', 'reference'); ?>
          <input type="input" name="reference" class="form-control" id="reference" value="<?= $payment_validation->reference; ?>" readonly="readonly">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-xs-12">
        <div class="form-group">
          <?= lang('transaction_date', 'trans_date'); ?>
          <input type="input" name="trans_date" class="form-control datetime" id="trans_date" value="" required="required">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-xs-12">
        <div class="form-group">
          <?= lang('amount', 'amount'); ?>
          <input name="amount" type="text" id="amount"
            value="<?= $this->sma->formatMoney($payment_validation->amount + $payment_validation->unique_code, 'none'); ?>"
            class="pa form-control kb-pad currency">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-xs-12">
        <div class="form-group">
          <?= lang('to_bank', 'to_bank'); ?>
          <?php
            $bk = [];
            $bk[''] = '';
            if ( ! empty($banks)) {
              foreach ($banks as $bank) {
                if ($biller_id) {
                  if ($bank->biller_id != $biller_id) continue;
                }
                $bk[$bank->id] = $bank->name . ($bank->type != 'Cash' ? ' (' . $bank->number . '/' . $bank->holder . ')' : '');
              }
            }
          ?>
          <?= form_dropdown('to_bank', $bk, '', 'class="form-control select2" placeholder="Select Account To" id="to_bank" required="required" style="width:100%;"'); ?>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-xs-12">
        <div class="form-group">
          <?= lang('description', 'description'); ?>
          <input type="input" name="description" class="form-control" id="description" value="">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-xs-12">
        <div class="form-group">
          <label><input type="checkbox" name="manual_validation" value="1" required="required"> I Accept re-activate validation manually.</label>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <?php echo form_submit('reactivate_validation', lang('reactivate_validation'), 'class="btn btn-primary"'); ?>
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
