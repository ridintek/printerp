<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-2x">&times;</i>
      </button>
      <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('add_payment'); ?></h4>
    </div>
    <form action="<?= admin_url('finances/payments/add') . ($pay_for ? '/' . $pay_for : '') . ($pay_id ? '/' . $pay_id : ''); ?>" enctype="multipart/form-data" method="post" data-toggle="validator">
      <div class="modal-body">
        <div class="row">
          <div class="col-xs-6">
            <div class="form-group">
              <?= lang('payment_for', 'pay_for'); ?>
              <?php
              $fors[''] = lang('select') . ' ' . lang('payment_for');
              $fors['bank_mutations'] = lang('bank_mutations');
              $fors['expenses'] = lang('expenses');
              $fors['incomes'] = lang('incomes');
              $fors['purchases'] = lang('purchases');
              $fors['sales'] = lang('sales');
              ?>
              <?= form_dropdown('pay_for', $fors, '', 'class="form-control" id="pay_for" required="required"'); ?>
            </div>
          </div>
          <div class="col-xs-6">
            <div class="form-group">
              <?= lang('payment_type', 'pay_type'); ?>
              <?php
              $types[''] = lang('select') . ' ' . lang('payment_type');
              $types['received'] = lang('received');
              $types['sent'] = lang('sent');
              ?>
              <?= form_dropdown('pay_type', $types, '', 'class="form-control" id="pay_type" required="required"'); ?>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-xs-4">
            <?= lang('reference', 'reference'); ?>
            <input class="form-control" id="reference" name="reference" type="text" value="<?= ($reference ?? '') ?>" />
          </div>
          <div class="col-xs-4">
            <?= lang('date', 'date'); ?>
            <input class="form-control datetime" id="date" name="date" type="text" value="" required="required" />
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <?php echo form_submit('add_payment', lang('add_payment'), 'class="btn btn-primary"'); ?>
      </div>
    </form>
  </div>
</div>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function () {
    let pay_for = '<?= ($pay_for ?? ''); ?>';
    let pay_id   = '<?= ($pay_id ?? ''); ?>';

    if (pay_for) {
      $('#pay_for').select2('val', pay_for);
      change_type(pay_for);
      $('#pay_for').select2('readonly', true);
      $('#pay_type').select2('readonly', true);
    }

    $('#pay_for').change(function () {
      let payfor = $(this).val();
      change_type(payfor);
    });

    $('#pay_type').change(function () {
      let paytype = $(this).val();
      if (paytype == 'received') $('#pay_for').select2('val', 'sales');
      if (paytype == 'sent') $('#pay_for').select2('val', 'expenses');
    });

    $("#date").datetimepicker({
      format: site.dateFormats.js_ldate,
      fontAwesome: true,
      language: 'sma',
      weekStart: 1,
      todayBtn: 1,
      autoclose: 1,
      todayHighlight: 1,
      startView: 2,
      forceParse: 0
    }).datetimepicker('update', new Date());

    <?php if ( ! $this->Admin && ! $this->Owner) { ?>
    $('#date').prop('disabled', true);
    <?php } ?>
  });

  function change_type (payfor) {
    if (payfor == 'expenses') $('#pay_type').select2('val', 'sent');
    if (payfor == 'incomes') $('#pay_type').select2('val', 'received');
    if (payfor == 'purchases') $('#pay_type').select2('val', 'sent');
    if (payfor == 'sales') $('#pay_type').select2('val', 'received');
  }
</script>
