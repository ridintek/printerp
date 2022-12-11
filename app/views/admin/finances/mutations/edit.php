<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('edit_bank_mutation'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
  echo admin_form_open_multipart('finances/mutations/edit/' . $mutation->id, $attrib); ?>
  <div class="modal-body">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('reference', 'reference'); ?>
          <input type="input" name="reference" class="form-control" id="reference" value="<?= $mutation->reference; ?>" readonly="readonly">
        </div>
        <div class="form-group">
        <?= lang('biller', 'biller'); ?>
        <?php
        $wh[''] = '';
        if ($billers) {
          foreach ($billers as $biller) {
            $wh[$biller->id] = $biller->name;
          }
        }
        echo form_dropdown('biller', $wh, $mutation->biller_id, 'id="biller" class="form-control input-tip select2" data-placeholder="Select Biller" style="width:100%;"');
        ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('date', 'date'); ?>
          <input type="input" name="date" class="form-control" id="date" value="<?= $mutation->date; ?>" required="required">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="well well-sm">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <?= lang('old_amount', 'old_amount'); ?>
                <input name="old_amount" type="text" id="old_amount" value="<?= $mutation->amount; ?>" class="pa form-control kb-pad amount" required="required" readonly="readonly" />
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <?= lang('new_amount', 'new_amount'); ?>
                <input name="new_amount" type="text" id="new_amount" value="" class="pa form-control kb-pad amount currency" required="required" />
              </div>
            </div>
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
                $bk[''] = '';
                if ( ! empty($banks)) {
                  foreach ($banks as $bank) {
                    $bk[$bank->id] = $bank->name;
                  }
                }
                ?>
                <?= form_dropdown('from_bank_id', $bk, $mutation->from_bank_id, 'class="form-control select2 tip" id="from_bank_id" data-placeholder="Select Account From" required="required" style="width:100%;"'); ?>
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
                if ( ! empty($banks)) {
                  foreach ($banks as $bank) {
                    $bk[$bank->id] = $bank->name;
                  }
                }
                ?>
                <?= form_dropdown('to_bank_id', $bk, $mutation->to_bank_id, 'class="form-control select2 tip" id="to_bank_id" data-placeholder="Select Account To" required="required" style="width:100%;"'); ?>
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
          <?php echo form_textarea('note', $mutation->note, 'class="form-control" id="note"'); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <?php echo form_submit('edit_bank_mutation', lang('edit_bank_mutation'), 'class="btn btn-primary"'); ?>
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
    document.querySelector('#new_amount').addEventListener('wheel', function(ev) {
      ev.preventDefault();
    });
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

    $('#balance_from').html(currencyFormat('<?= $balance_from; ?>'));
    $('#balance_to').html(currencyFormat('<?= $balance_to; ?>'));
    $('#old_amount').val(formatCurrency(<?= $mutation->amount; ?>));

    $("#date").datetimepicker({
      format: site.dateFormats.js_ldate,
      autoclose: true,
      todayBtn: true,
      minView: 2 /* Show date only, not time */
    })
  });
</script>
