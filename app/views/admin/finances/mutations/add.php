<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('add_bank_mutation'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
  echo admin_form_open_multipart('finances/mutations/add', $attrib); ?>
  <div class="modal-body">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('date', 'date'); ?>
          <input type="input" name="date" class="form-control" id="date" value="" required="required">
        </div>
      </div>
      <div class="col-md-6">
        <?php if ($Owner || $Admin || !$this->session->userdata('biller_id')) { ?>
        <div class="form-group">
          <?= lang('biller', 'biller'); ?>
          <?php
          $bl[''] = '';
          if ($billers) {
            foreach ($billers as $biller) {
              $bl[$biller->id] = $biller->name;
            }
          } else {
            $bl[$biller->id] = $biller->name;
          }
          echo form_dropdown('biller', $bl, ($biller_id ?? $this->Settings->default_biller), 'id="biller" class="form-control input-tip select2" placeholder="Select Biller" style="width:100%;"');
          ?>
        </div>
        <?php } else { ?>
        <div class="form-group">
          <input name="biller" type="hidden" value="<?= ($biller_id ?? $this->Settings->default_biller); ?>" />
        </div>
        <?php } ?>
      </div>
    </div>
    <div class="well well-sm">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <?= lang('paying_by', 'paid_by'); ?>
            <select name="paid_by" id="paid_by" class="form-control paid_by select2" required="required" style="width:100%;">
              <?= $this->sma->paid_opts(['Transfer']); ?>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <?= lang('amount', 'amount'); ?>
            <input name="amount" type="text" id="amount" value="" class="pa form-control kb-pad currency" required="required"/>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="well well-sm">
          <div class="form-group">
            <div class="row">
              <div class="col-md-6">
                <?= lang('account', 'account') . ' ' . lang('from', 'from_bank_id'); ?>
                <?php
                $bk = [];
                $bk[''] = '';
                if ( ! empty($banks)) {
                  foreach ($banks as $bank) {
                    if ($biller_id && $biller_id != $bank->biller_id) continue;
                    $bk[$bank->id] = $bank->name . ($bank->type != 'Cash' ? ' (' . $bank->number . ')' : '');
                  }
                }
                ?>
                <?= form_dropdown('from_bank_id', $bk, '', 'class="form-control tip from_bank select2" id="from_bank_id" data-placeholder="Select Account From" required="required" style="width:100%;"'); ?>
              </div>
              <div class="col-md-6">
                <?= lang('current_balance', 'balance_from'); ?>
                <div id="balance_from" class="form-control" style="padding: 7px"></div>
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="row">
              <div class="col-md-6" id="paid_cash">
                <?= lang('account', 'account') . ' ' . lang('to', 'to_cash'); ?>
                <?php
                $bk = [];
                $bk[''] = '';
                if ( ! empty($banks)) {
                  foreach ($banks as $bank) {
                    if ($biller_id && $biller_id != $bank->biller_id) continue;
                    if ($bank->type != 'Cash') continue;
                    $bk[$bank->id] = $bank->name . ' (' . $bank->number . ')';
                  }
                }
                ?>
                <?= form_dropdown('to_bank_id', $bk, '', 'class="form-control tip to_bank to_cash select2" id="to_cash" data-placeholder="Select Account To" required="required" style="width:100%;"'); ?>
              </div>
              <div class="col-md-6" id="paid_transfer">
              <?= lang('account', 'account') . ' ' . lang('to', 'to_transfer'); ?>
                <?php
                $bk = [];
                $bk[''] = '';
                if ( ! empty($banks)) {
                  foreach ($banks as $bank) {
                    if ($biller_id && $biller_id != $bank->biller_id) continue;
                    if ($bank->type == 'Cash') continue;
                    $bk[$bank->id] = $bank->name . ' (' . $bank->number . ')';
                  }
                }
                ?>
                <?= form_dropdown('to_bank_id', $bk, '', 'class="form-control tip to_bank to_transfer select2" id="to_transfer" data-placeholder="Select Account To" required="required" style="width:100%;"'); ?>
              </div>
              <div class="col-md-6">
                <?= lang('current_balance', 'balance_to'); ?>
                <div id="balance_to" class="form-control" style="padding: 7px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <?= lang('attachment', 'attachment') ?>
          <input id="attachment" type="file" data-browse-label="<?= lang('browse'); ?>" name="userfile" data-show-upload="false"
          data-show-preview="false" class="form-control file">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <?= lang('note', 'note'); ?>
          <?php echo form_textarea('note', '', 'class="form-control" id="note"'); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <div class="row">
      <div class="col-md-6">
      <?php if ($Owner || $Admin || $GP['mutations-manual']) { ?>
        <label><input type="checkbox" name="skip_pv" value="1"> Skip Payment Validation</label>
      <?php } ?>
      </div>
      <div class="col-md-6">
        <?php echo form_submit('add_bank_mutation', lang('add_bank_mutation'), 'class="btn btn-primary"'); ?>
      </div>
    </div>
  </div>
</div>
<?php echo form_close(); ?>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function () {
    let to_old_value = 0, from_old_value = 0;
    let paid_by = $('#paid_by').val();

    $('#paid_by').on('select2-close', function () {
      paid_by = $(this).val();
      if (paid_by == 'Cash') {
        $('#paid_transfer').hide();
        $('#to_transfer').prop('disabled', true);
        $('#paid_cash').show();
        $('#to_cash').prop('disabled', false);
      }
      if (paid_by == 'Transfer') {
        $('#paid_cash').hide();
        $('#to_cash').prop('disabled', true);
        $('#paid_transfer').show();
        $('#to_transfer').prop('disabled', false);
      }
    });

    $('#paid_cash').hide();
    $('.to_cash').prop('disabled', true);

    $('.from_bank').on('select2:open', function () {
      from_old_value = $(this).val();
    }).on('select2:select', function() {
      if ($(this).val() != '' && $(this).val() == $(`#to_${paid_by.toLowerCase()}`).val()) {
        $(this).val(from_old_value).trigger('change');
        bootbox.alert('Akun bank tidak boleh sama.');
      }
    <?php
        $bal = [];
        foreach ($banks as $bank) {
          $bal[$bank->id] = $bank->balance;
        }
    ?>
      let bal = JSON.parse('<?= json_encode($bal); ?>');
      $('#balance_from').html(currencyFormat(bal[$(this).val()]));
    });

    $('.to_bank').on('select2:open', function() {
      to_old_value = $(this).val();
    }).on('select2:select', function() {
      if ($(this).val() != '' && $(this).val() == $('#from_bank_id').val()) {
        $(this).val(to_old_value).trigger('change');
        bootbox.alert('Akun bank tidak boleh sama.');
      }
    <?php
        $bal = [];
        foreach ($banks as $bank) {
          $bal[$bank->id] = $bank->balance;
        }
    ?>
      let bal = JSON.parse('<?= json_encode($bal); ?>');
      $('#balance_to').html(currencyFormat(bal[$(this).val()]));
    });

    $.fn.datetimepicker.dates['sma'] = <?=$dp_lang?>;
    $("#date").datetimepicker({
      format: site.dateFormats.js_ldate,
      autoclose: true,
      todayBtn: true,
      minView: 2 /* Show date only, not time */
    }).datetimepicker('update', new Date());
  });
</script>
