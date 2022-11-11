<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fad fa-times"></i>
      </button>
      <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('edit_payment'); ?></h4>
    </div>
    <div class="modal-body">
      <div class="row">
        <div class="col-sm-6">
          <div class="form-group">
            <?= lang('date', 'date'); ?>
            <?= form_input('date', (isset($_POST['date']) ? $_POST['date'] : $this->sma->fld($payment->date)), 'class="form-control datetime" id="date" required="required"'); ?>
          </div>
        </div>
        <?php if ($Owner || $Admin) { ?>
        <div class="col-sm-6">
          <div class="well well-sm well-info">
            <?= lang('created_by', 'ep_created_by'); ?>
            <?php
              $users = $this->site->getUsers();
              foreach ($users as $user) {
                $usr[$user->id] = $user->first_name . ' ' . $user->last_name;
              }
            ?>
            <?= form_dropdown('created_by', $usr, $payment->created_by, 'class="select2" id="ep_created_by" style="width:100%;"'); ?>
          </div>
        </div>
        <?php } else { ?>
          <input id="ep_created_by" type="hidden" name="created_by" value="<?= $payment->created_by; ?>">
        <?php } ?>
        <div class="col-sm-6">
          <div class="well well-sm">
            <div class="form-group">
              <?= lang('payment_method', 'payment_method'); ?>
              <select id="payment_method" class="select2" name="payment_method" style="width:100%;">
                <?= $this->sma->paid_opts(NULL, FALSE, TRUE); ?>
              </select>
            </div>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="well well-sm bank_id" style="display: none">
            <div class="form-group">
              <?= lang('bank_account', 'bank_id'); ?>
              <select id="bank_id" class="select2" name="bank_id" style="width:100%;">
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
              <input id="amount" name="amount" type="text" class="form-control currency">
            </div>
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
      <?php if (($Owner || $Admin || getPermission('sales-skip_validation')) && ! $waiting_transfer) { ?>
      <div class="form-group">
        <label><input type="checkbox" name="skip_payment_validation" id="skip_payment_validation" /> Skip Payment Validation</label>
      </div>
      <?php } ?>
      <input id="sale_id" name="sale_id" type="hidden" value="<?= $inv->id; ?>">
      <input type="button" name="edit_payment" value="<?= lang('edit_payment'); ?>" class="btn btn-primary" id="edit_payment" />
    </div>
  </div>
</div>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script type="text/javascript">
  $(document).ready(function () {
    let balance = '<?= floatval($inv->grand_total) - floatval($inv->paid); ?>';
    let paid = '<?= floatval($inv->paid); ?>';
    let transfer_balance = '<?= ($payment_validation ? floatval($payment_validation->amount) + $payment_validation->unique_code : 0); ?>';
    let min_dp = '<?= $settings_json->min_dp; ?>';
    let min_persen = '<?= $settings_json->min_dp_percent; ?>';
    let ori_amount = balance, changed = false;
    let waiting_transfer = <?= $waiting_transfer; ?>;
    let skip_validation = false, payment_method = null;
    let payment_options = {
      cash: '<?= $this->sma->getPaymentOptionsByType("cash", TRUE); ?>',
      edc: '<?= $this->sma->getPaymentOptionsByType("edc", TRUE); ?>',
      transfer: '<?= $this->sma->getPaymentOptionsByType("transfer", TRUE); ?>'
    };

    $(document).on('change', '#payment_method', function () {
      payment_method = $(this).val();
      if (payment_method == 'Cash') {
        $('.amount').slideDown();
        $('.bank_id').slideDown();
        $('#bank_id').html(payment_options.cash).trigger('change');
      }
      if (payment_method == 'Transfer') {
        $('.amount').slideUp();
        if (skip_validation) {
          $('.amount').slideDown();
          $('.bank_id').slideDown();
        } else {
          $('.amount').slideUp();
          $('.bank_id').slideUp();
        }
        $('#bank_id').html(payment_options.transfer).trigger('change');
      }
      if (payment_method == 'EDC') {
        $('.amount').slideDown();
        $('.bank_id').slideDown();
        $('#bank_id').html(payment_options.edc).trigger('change');
      }
      if (payment_method == '') {
        $('.amount').slideUp();
        $('.bank_id').slideUp();
        $('#bank_id').html('').select2('destroy').select2();
      }
    });

    $('#skip_payment_validation').on('ifChanged', function (e) {
      skip_validation = (e.target.checked ? true : false);
      if (e.target.checked && payment_method == 'Transfer') {
        $('.amount').slideDown();
        $('.bank_id').slideDown();
      } else if (payment_method == 'Transfer') {
        $('.amount').slideUp();
        $('.bank_id').slideUp();
      }
    });

    $('#edit_payment').click(function (e) {
      if ( ! $('#payment_method').val()) {
        bootbox.alert('Mohon pilih metode pembayaran!');
        return false;
      }
      if ( ! $('#bank_id').val()) {
        if (skip_validation || payment_method != 'Transfer') {
          bootbox.alert('Mohon pilih akun pembayaran!');
          return false;
        }
      }
      let form = new FormData();
      form.append('<?= $this->security->get_csrf_token_name(); ?>', '<?= $this->security->get_csrf_hash(); ?>');
      form.append('edit_payment', true);
      form.append('amount', $('#amount').val());
      form.append('date', $('#date').val());
      form.append('bank_id', $('#bank_id').val());
      form.append('created_by', $('#ep_created_by').val());
      form.append('note', $('#note').val());
      form.append('payment_method', $('#payment_method').val());
      form.append('sale_id', $('#sale_id').val());
      form.append('skip_payment_validation', skip_validation);
      form.append('userfile', ($('#attachment')[0].files.length > 0 ? $('#attachment')[0].files[0] : ''));

      $.ajax({
        contentType: false,
        data: form,
        method: 'POST',
        processData: false,
        success: function (data) {
          if (data && ! data.error) {
            if (typeof mTable !== 'undefined') mTable.fnDraw(false);
            addAlert(data.msg, 'success');
            $('#myModal2').modal('hide');
          } else {
            addAlert(data.msg, 'danger');
            $('#myModal2').modal('hide');
          }
        },
        url: '<?= admin_url("sales/edit_payment/{$payment->id}"); ?>'
      });
    });

    $('#amount').on('focus', function () {
      disableSubmit(); changed = false;
    }).change(function () {
      // Not used in EDIT
    }).on('blur', function () {
      if ( ! changed) {
        enableSubmit();
      }
    });

    if (waiting_transfer) {
      $('#date').prop('disabled', true);
      $('#ep_created_by').prop('disabled', true);
      $('#amount').prop('disabled', true);
      $('#attachment').prop('disabled', true);
      disableSubmit();
      $('#payment_method').val('Transfer')
        .trigger('change').prop('readonly', true);
      balance = transfer_balance;
      $('.amount').slideDown();
    }

    if (paid) {
      $('#payment_method').val('<?= $inv->payment_method; ?>').trigger('change');
      $('#bank_id').val('<?= $payment->bank_id; ?>').trigger('change');
    }

    $('#amount').val(formatCurrency(paid));

    function disableSubmit () {
      $('#edit_payment').prop('disabled', true);
    }
    function enableSubmit () {
      $('#edit_payment').prop('disabled', false);
    }
  });
</script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>