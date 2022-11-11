<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fad fa-times"></i>
      </button>
      <h4 class="modal-title" id="myModalLabel"><?php echo lang('edit_payment'); ?></h4>
    </div>
    <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
    echo admin_form_open_multipart('procurements/transfers/edit_payment/' . $payment->id, $attrib); ?>
    <div class="modal-body">
      <p><?= lang('enter_info'); ?></p>
      <div class="row">
        <div class="col-sm-6">
          <div class="form-group">
            <?= lang('date', 'date'); ?>
            <?= form_input('date', (isset($_POST['date']) ? $_POST['date'] : $this->sma->fld($payment->date)), 'class="form-control" id="date" required="required"'); ?>
          </div>
        </div>
        <div class="col-sm-6"></div>
        <input type="hidden" value="<?php echo $transfer->id; ?>" name="transfer_id" />
      </div>
      <div class="clearfix"></div>
      <div id="payments">
        <div class="well well-sm well_1">
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <?= lang('old_amount', 'old_amount'); ?>
                <input name="old_amount" id="old_amount" value="<?= $this->sma->formatDecimal($payment->amount); ?>" class="form-control amount currency" type="text" readonly>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <?= lang('new_amount', 'new_amount'); ?>
                <input name="new_amount" id="new_amount" value="<?= $this->sma->formatDecimal($payment->amount); ?>" class="form-control amount currency" type="text" required="required">
              </div>
            </div>
          </div>
          <!-- PAYING BY -->
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <?= lang('paying_by', 'bank_id'); ?>
                <select name="bank_id" id="bank_id" class="select2" required="required" style="width:100%;">
                  <option value="">Select Paid By</option>
                  <?php foreach ($banks as $bank) :
                    if ($bank->biller_id != $this->site->getBiller(['code' => $transfer->from_warehouse_code])->id) continue;  
                  ?>
                    <option value="<?= $bank->id; ?>"><?= $bank->name; ?> (<?= $bank->number; ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <?= lang('current_balance', 'current_balance'); ?>
                <div id="current_balance" class="form-control"></div>
              </div>
            </div>
          </div>
          <div class="clearfix"></div>
        </div>
      </div>

      <div class="form-group">
        <?= lang('attachment', 'attachment') ?>
        <input id="attachment" type="file" data-browse-label="<?= lang('browse'); ?>" name="userfile" data-show-upload="false" data-show-preview="false" class="form-control file">
      </div>

      <div class="form-group">
        <?= lang('note', 'note'); ?>
        <?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : ''), 'class="form-control" id="note"'); ?>
      </div>

    </div>
    <div class="modal-footer">
      <?php echo form_submit('edit_payment', lang('edit_payment'), 'class="btn btn-primary"'); ?>
    </div>
  </div>
  <?php echo form_close(); ?>
</div>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<?php
$bal = [];
foreach ($banks as $_bank) {
  $bal[$_bank->id] = $_bank->amount;
}
?>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function() {
    let bal = JSON.parse('<?= json_encode($bal); ?>');
    let bank_id = <?= $payment->bank_id; ?>;

    $('#new_amount').val(formatCurrency($('#new_amount').val()));
    $('#old_amount').val(formatCurrency($('#old_amount').val()));
    $('#current_balance').html(currencyFormat(bal[$('#bank_id').val()]));

    $('#bank_id').change(function() {
      $('#current_balance').html(currencyFormat(bal[$(this).val()]));
    });
    $('#bank_id').val(bank_id).trigger('change');

    $("#date").datetimepicker({
      format: site.dateFormats.js_ldate,
      fontAwesome: true,
      language: 'sma',
      weekStart: 1,
      todayBtn: 1,
      autoclose: 1,
      todayHighlight: 1,
      minView: 2
    });
  });
</script>