<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fad fa-times"></i>
      </button>
      <h4 class="modal-title" id="myModalLabel"><?php echo lang('add_transfer_payment'); ?></h4>
    </div>
    <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
    echo admin_form_open_multipart('procurements/transfers/add_payment/' . $transfer->id, $attrib); ?>
    <div class="modal-body">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <?= lang('date', 'date'); ?>
            <input type="input" name="date" class="form-control" id="date" value="" required="required">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <?= lang('created_by', 'created_by'); ?>
            <?php
            $usr[''] = '';
            
            if ( ! empty($users)) {
              foreach ($users as $user) {
                if ( ! $Admin && ! $Owner && $this->session->userdata('user_id') != $user->id) continue; 
                $usr[$user->id] = $user->fullname;
              }
            }

            echo form_dropdown('created_by', $usr, $this->session->userdata('user_id'), 'id="created_by" class="select2" placeholder="Select Created By" required="required" style="width:100%"');
            ?>
          </div>
        </div>
      </div>

      <div class="well well-sm">
        <div class="form-group">
          <div class="row">
            <div class="col-md-6">
              <?= lang('account', 'account') . ' ' . lang('from', 'from_bank_id'); ?>
              <?php
              $bk = [];
              $bk[''] = '';
              $biller = $this->site->getBiller(['code' => $transfer->to_warehouse_code]);
              if ( ! empty($banks) && $biller) {
                foreach ($banks as $bank) {
                  if ($biller->id == $bank->biller_id) {
                    $bk[$bank->id] = $bank->name;
                  }
                }
              }
              ?>
              <?= form_dropdown('from_bank_id', $bk, '', 'class="select2" id="from_bank_id" data-placeholder="Select Account" required="required" style="width:100%"'); ?>
            </div>
            <div class="col-md-6">
              <?= lang('current_balance', 'balance_from'); ?>
              <div id="balance_from" class="form-control" style="padding: 7px"></div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <div class="row">
            <div class="col-md-6">
              <?= lang('account', 'account') . ' ' . lang('to', 'to_bank_id'); ?>
              <?php
              $bk = [];
              $bk[''] = '';
              $biller = $this->site->getBiller(['code' => $transfer->from_warehouse_code]);
              if ( ! empty($banks) && $biller) {
                foreach ($banks as $bank) {
                  if ($biller->id == $bank->biller_id) {
                    $bk[$bank->id] = $bank->name;
                  }
                }
              }
              ?>
              <?= form_dropdown('to_bank_id', $bk, '', 'class="select2" id="to_bank_id" data-placeholder="Select Account" required="required" style="width:100%"'); ?>
            </div>
            <div class="col-md-6">
              <?= lang('current_balance', 'balance_to'); ?>
              <div id="balance_to" class="form-control" style="padding: 7px"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="form-group">
        <?= lang('amount', 'amount'); ?>
        <input name="amount" type="text" id="amount" value="<?= ($transfer->grand_total - $transfer->paid); ?>" class="pa form-control kb-pad currency" required="required" />
      </div>

      <div class="form-group">
        <?= lang('attachment', 'attachment') ?>
        <input id="attachment" type="file" data-browse-label="<?= lang('browse'); ?>" name="userfile" data-show-upload="false"
        data-show-preview="false" class="form-control file">
      </div>

      <div class="form-group">
        <?= lang('note', 'note'); ?>
        <?php echo form_textarea('note', $this->sma->decode_html($transfer->note), 'class="form-control" id="note"'); ?>
      </div>

    </div>
    <div class="modal-footer">
      <?php echo form_submit('add_transfer_payment', lang('add_transfer_payment'), 'class="btn btn-primary" id="add_transfer_payment"'); ?>
    </div>
  </div>
  <?php echo form_close(); ?>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function () {
    let payment_status = '<?= $transfer->payment_status; ?>';

    $('#amount').val(formatCurrency($('#amount').val()));
    $('#from_bank_id').change(function() {
    <?php
        $bal = [];
        foreach ($banks as $bank) {
          $bal[$bank->id] = $bank->amount;
        }
    ?>
      let bal = JSON.parse('<?= json_encode($bal); ?>');
      $('#balance_from').html(currencyFormat(bal[$(this).val()]));
    });
    $('#to_bank_id').change(function() {
    <?php
        $bal = [];
        foreach ($banks as $bank) {
          $bal[$bank->id] = $bank->amount;
        }
    ?>
      let bal = JSON.parse('<?= json_encode($bal); ?>');
      $('#balance_to').html(currencyFormat(bal[$(this).val()]));
    });
    if (payment_status == 'paid') {
      $('#add_transfer_payment').prop('disabled', true);
    }

    $("#date").datetimepicker({
      format: site.dateFormats.js_ldate,
      autoclose: true,
      todayBtn: true,
      minView: 2 /* Show date only, not time */
    }).datetimepicker('update', new Date());
  });
</script>
