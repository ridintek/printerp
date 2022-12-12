<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fad fa-times"></i>
      </button>
      <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('add_payment'); ?></h4>
    </div>
    <div class="modal-body">
      <div class="row">
        <div class="col-sm-6">
          <div class="form-group">
            <?= lang('date', 'date'); ?>
            <input type="datetime-local" class="form-control" id="date" name="date">
          </div>
        </div>
        <?php if ($Owner || $Admin) { ?>
          <div class="col-sm-6">
            <div class="well well-sm well-info">
              <?= lang('created_by', 'created_by'); ?>
              <?php
              $users = $this->site->getUsers();
              foreach ($users as $user) {
                $usr[$user->id] = $user->fullname;
              }
              ?>
              <?= form_dropdown('created_by', $usr, XSession::get('user_id'), 'class="select2" id="ap_created_by" style="width:100%;"'); ?>
            </div>
          </div>
        <?php } else { ?>
          <input id="ap_created_by" type="hidden" name="created_by" value="<?= XSession::get('user_id'); ?>">
        <?php } ?>
        <div class="col-sm-6">
          <div class="well well-sm">
            <div class="form-group">
              <?= lang('payment_method', 'payment_method'); ?>
              <select id="payment_method" class="select2" name="payment_method" style="width:100%;" required="required">
                <?= $this->sma->paid_opts(NULL, FALSE, TRUE); ?>
              </select>
            </div>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="well well-sm bank_id" style="display: none">
            <div class="form-group">
              <?= lang('bank_account', 'bank_id'); ?>
              <select id="bank_id" class="select2" name="bank_id" style="width:100%;" required="required">
              </select>
            </div>
          </div>
        </div>
        <?php if ($waiting_transfer) { ?>
          <div class="col-sm-12">
            <div class="well well-sm well-success text-center">
              Silakan transfer ke rekening dengan nominal tepat di bawah ini.<br>
              <a href="https://indoprinting.co.id/trackorder?inv=<?= $inv->reference; ?>&phone=<?= $customer->phone; ?>&submit=1" target="_blank">
                <strong>Lihat Rekening Bank dan Tracking Order</strong>
              </a>
            </div>
          </div>
        <?php } ?>
        <div class="col-sm-12">
          <div class="well well-sm amount" style="display: none">
            <div class="form-group">
              <?= lang('amount', 'amount'); ?>
              <input class="form-control currency" id="amount" name="amount" type="text" required="required">
            </div>
            <?php if ($Owner || $Admin || isset($GP['sales-skip_validation'])) { ?>
              <div class="form-group use_unique_code" style="display: none">
                <label><input class="form-control" id="use_unique_code" name="use_unique_code" type="checkbox" value="1"> Use Unique Code</label>
              </div>
              <div class="form-group unique_code" style="display: none">
                <input class="form-control currency" id="unique_code" name="unique_code" placeholder="Unique Code" type="text" value="">
              </div>
            <?php } ?>
          </div>
        </div>
      </div>

      <div class="form-group attachment">
        <?= lang('attachment', 'attachment') ?>
        <input id="attachment" type="file" data-browse-label="<?= lang('browse'); ?>" name="userfile" data-show-upload="false" data-show-preview="false" class="form-control file">
      </div>

      <div class="form-group note">
        <?= lang('note', 'note'); ?>
        <?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : ''), 'class="form-control" id="note"'); ?>
      </div>
    </div>
    <div class="modal-footer">
      <?php if (($Owner || $Admin || isset($GP['sales-skip_validation'])) && !$waiting_transfer) { ?>
        <div class="form-group">
          <label><input type="checkbox" name="skip_payment_validation" id="skip_payment_validation" /> Skip Payment Validation</label>
        </div>
      <?php } ?>
      <input type="button" name="add_payment" value="<?= lang('add_payment'); ?>" class="btn btn-primary" id="add_payment" />
    </div>
  </div>
</div>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script type="text/javascript">
  $(document).ready(function() {
    let balance = filterDecimal('<?= ($inv->grand_total - $inv->paid); ?>');
    let paid = filterDecimal('<?= $inv->paid ?>');
    let transfer_balance = filterDecimal('<?= ($payment_validation ? $payment_validation->amount + $payment_validation->unique_code : 0); ?>');
    let min_dp = filterDecimal('<?= $settings_json->min_dp; ?>');
    let min_percent = filterDecimal('<?= $settings_json->min_dp_percent; ?>');
    let changed = false;
    let waiting_transfer = <?= $waiting_transfer; ?>;
    let skip_validation = false,
      payment_method = null;
    let payment_options = {
      cash: '<?= $this->sma->getPaymentOptionsByType("cash", TRUE); ?>',
      edc: '<?= $this->sma->getPaymentOptionsByType("edc", TRUE); ?>',
      transfer: '<?= $this->sma->getPaymentOptionsByType("transfer", TRUE); ?>'
    };

    console.log(`Balance: ${balance}`);
    let amount = balance;

    // if (typeof(oTable) !== 'undefined') oTable.fnDraw(false);

    $('#date').val(dateTime());

    $('#payment_method').on('change', function() { // Do not use 'change'
      payment_method = $(this).val();
      if (payment_method == 'Cash') {
        $('.amount').slideDown();
        $('.bank_id').slideDown();
        $('#bank_id').html(payment_options.cash).trigger('change');
        $('.use_unique_code').slideUp();
        $('.unique_code').slideUp();
      } else
      if (payment_method == 'Transfer') {
        if (skip_validation) {
          $('.bank_id').slideDown();
          $('.use_unique_code').slideDown();
          if ($('#use_unique_code')[0].checked) {
            $('.unique_code').slideDown();
          } else {
            $('.unique_code').slideUp();
          }
        } else {
          $('.bank_id').slideUp();
          $('.use_unique_code').slideUp();
          $('.unique_code').slideUp();
        }
        $('.amount').slideDown();
        $('#bank_id').html(payment_options.transfer).trigger('change');
      } else
      if (payment_method == 'EDC') {
        $('.amount').slideDown();
        $('.bank_id').slideDown();
        $('#bank_id').html(payment_options.edc).trigger('change');
        $('.use_unique_code').slideUp();
        $('.unique_code').slideUp();
      } else
      if (payment_method == '') {
        $('.amount').slideUp();
        $('.bank_id').slideUp();
        $('#bank_id').html('').select2('destroy').select2();
        $('.use_unique_code').slideUp();
        $('.unique_code').slideUp();
      }
    });

    $('#skip_payment_validation').on('ifChanged', function(e) {
      skip_validation = (e.target.checked ? true : false);
      if (e.target.checked && payment_method == 'Transfer') {
        $('.bank_id').slideDown();
        $('.use_unique_code').slideDown();
        if ($('#use_unique_code')[0].checked) {
          $('.unique_code').slideDown();
        } else {
          $('.unique_code').slideUp();
        }
      } else if (payment_method == 'Transfer') {
        $('.bank_id').slideUp();
        $('.use_unique_code').slideUp();
        $('.unique_code').slideUp();
      }
    });

    $('#use_unique_code').on('ifChanged', function(e) {
      if (e.target.checked && payment_method == 'Transfer') {
        $('.unique_code').slideDown();
      } else {
        $('.unique_code').slideUp();
      }
    });

    $('#add_payment').click(function(e) { // Add New Payment
      if (!$('#payment_method').val()) {
        bootbox.alert('Mohon pilih metode pembayaran!');
        return false;
      }
      if (!$('#bank_id').val()) {
        if (skip_validation || payment_method != 'Transfer') {
          bootbox.alert('Mohon pilih akun pembayaran!');
          return false;
        }
      }
      let form = new FormData();
      form.append('<?= $this->security->get_csrf_token_name(); ?>', '<?= $this->security->get_csrf_hash(); ?>');
      form.append('add_payment', true);
      form.append('amount', $('#amount').val());
      form.append('date', $('#date').val());
      form.append('bank_id', $('#bank_id').val());
      form.append('created_by', $('#ap_created_by').val());
      form.append('note', $('#note').val());
      form.append('payment_method', $('#payment_method').val());
      form.append('skip_payment_validation', skip_validation);
      form.append('userfile', ($('#attachment')[0].files.length > 0 ? $('#attachment')[0].files[0] : ''));

      $.ajax({
        contentType: false,
        data: form,
        method: 'POST',
        processData: false,
        success: function(data) {
          console.groupCollapsed('add_payment');
          console.warn(data);
          console.groupEnd();
          if (!data.error) {
            if (oTable) oTable.fnDraw(false);
            addAlert(data.msg, 'success');
            $('#myModal').modal('hide');
          } else {
            addAlert(data.msg, 'danger');
            $('#myModal').modal('hide');
          }
        },
        url: '<?= admin_url("sales/add_payment/{$inv->id}"); ?>'
      });
    });

    $('#amount').on('focus', function() {
      disableSubmit();
      changed = false;
    }).change(function() {
      amount = $(this).val();
      changed = true;

      amount = filterDecimal(amount);
    }).on('blur', function() {
      // Calculate f*cked price. 20% from balance.
      let min_price = (parseFloat(balance) * (parseFloat(min_percent) * 0.01));

      // If min_price less than min_dp then use min_dp as min_price.
      if (parseFloat(min_price) < parseFloat(min_dp)) min_price = min_dp;

      console.log(`amount: ${amount}`);
      console.log(`balance: ${balance}`);
      console.log(`min_dp: ${min_dp}`);
      console.log(`min_percent: ${min_percent}`);
      console.log(`min_price: ${min_price}`);

      if ($(this).val() == '') {
        bootbox.alert({
          message: 'Harus ada nominal pembayaran.',
          callback: function() {
            enableSubmit();
            changed = false;
          }
        });
        $('#amount').val(formatCurrency(balance));
      } else
      if (skip_validation) {
        changed = false;
      } else
      if (parseFloat(amount) > parseFloat(balance)) {
        bootbox.alert(`Pembayaran melebihi balance <b>${formatCurrency(balance)}</b>.`, function() {
          enableSubmit();
          changed = false;
        });
        $('#amount').val(formatCurrency(balance));
      } else
      if (amount < parseFloat(min_price)) {
        bootbox.alert(`Min. DP adalah <b>${formatCurrency(min_price)}</b>.`, function() {
          enableSubmit();
          changed = false;
        });
        $('#amount').val(formatCurrency(balance));
      } else {
        changed = false;
      }
      if (!changed) {
        enableSubmit();
      }
    });

    if (waiting_transfer) {
      $('#date').prop('disabled', true);
      $('#created_by').prop('disabled', true);
      $('#amount').prop('disabled', true);
      $('#attachment').prop('disabled', true);
      disableSubmit();
      $('#payment_method').val('Transfer')
        .trigger('change').prop('readonly', true);
      balance = transfer_balance;
      $('.amount').slideDown();
    }

    $('#amount').val(formatCurrency(balance));

    function disableSubmit() {
      $('#add_payment').prop('disabled', true);
    }

    function enableSubmit() {
      $('#add_payment').prop('disabled', false);
    }
  });
</script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>