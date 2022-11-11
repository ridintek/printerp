<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fad fa-times"></i>
      </button>
      <h4 class="modal-title" id="myModalLabel"><?php echo lang('add_payment'); ?></h4>
    </div>
    <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
    echo admin_form_open_multipart('procurements/purchases/add_payment/' . $purchase->id, $attrib); ?>
    <div class="modal-body">
      <p><?= lang('enter_info'); ?></p>
      <div class="row">
        <div class="col-sm-6">
          <div class="form-group">
              <?= lang('date', 'date'); ?>
              <?= form_input('date', (isset($_POST['date']) ? $_POST['date'] : ''), 'class="form-control" id="date" required="required"'); ?>
            </div>
          <!--<div class="form-group">
            <?= lang('reference', 'reference'); ?>
            <?= form_input('reference', (isset($_POST['reference']) ? $_POST['reference'] : ''), 'class="form-control tip" id="reference"'); ?>
          </div>-->
        </div>
        <div class="col-sm-6"></div>
        <input type="hidden" value="<?php echo $purchase->id; ?>" name="purchase_id"/>
      </div>
      <div class="clearfix"></div>
      <div id="payments">
        <div class="well well-sm well_1">
          <div class="row">
            <div class="col-sm-12">
              <div class="payment">
                <div class="form-group">
                  <?= lang('amount', 'amount'); ?>
                  <input name="amount-paid" id="amount"
                    value="<?= $this->sma->formatDecimal($purchase->balance * -1); ?>"
                    class="form-control amount currency" type="text" required="required"
                  />
                </div>
              </div>
            </div>
          </div>
          <!-- PAYING BY -->
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <?= lang('paying_by', 'paid_by'); ?>
                <select name="paid_by" id="paid_by" class="select2" required="required" style="width:100%;">
                  <option value="">Select Paid By</option>
                <?php
                  foreach ($banks as $bank) { ?>
                  <option value="<?= $bank->id; ?>"><?= $bank->name; ?> (<?= $bank->number; ?>)</option>
                <?php
                  } ?>
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
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <input type="checkbox" class="form-control" id="discount" name="discount" value="1">
                <?= lang('use_discount', 'discount'); ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="form-group">
        <?= lang('attachment', 'attachment') ?>
        <input id="attachment" type="file" data-browse-label="<?= lang('browse'); ?>" name="payment_proof" data-show-upload="false" data-show-preview="false" class="form-control file">
      </div>

      <div class="form-group">
        <?= lang('note', 'note'); ?>
        <?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : ''), 'class="form-control" id="note"'); ?>
      </div>

    </div>
    <div class="modal-footer">
      <?php echo form_submit('add_payment', lang('add_payment'), 'class="btn btn-primary"'); ?>
    </div>
  </div>
  <?php echo form_close(); ?>
</div>
<script src="<?= $assets ?>js/custom.js"></script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<?php
  $bal = [];
  foreach ($banks as $_bank) {
    $bal[$_bank->id] = $_bank->balance;
  }
?>
<script>
  $(document).ready(function () {
    let bal = JSON.parse('<?= json_encode($bal); ?>');

    $('#amount').val(formatCurrency($('#amount').val()));
    $('#current_balance').html(currencyFormat(bal[$('#paid_by').val()]));

    $('#paid_by').change(function() {
      $('#current_balance').html(currencyFormat(bal[$(this).val()]));
    });

    $("#date").datetimepicker({
      format: site.dateFormats.js_ldate,
      fontAwesome: true,
      language: 'sma',
      weekStart: 1,
      todayBtn: 1,
      autoclose: 1,
      todayHighlight: 1,
      minView: 2
    }).datetimepicker('update', new Date());
  });
</script>
