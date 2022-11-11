<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fa fa-times"></i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('add_income'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
  echo admin_form_open_multipart('finances/incomes/add', $attrib); ?>
  <div class="modal-body">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('date', 'date'); ?>
          <?= form_input('date', (isset($_POST['date']) ? $_POST['date'] : ''), 'class="form-control" id="date" required="required"'); ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('category', 'category'); ?>
          <?php
          $ct[''] = 'Select Category';
          if ($categories) {
            foreach ($categories as $category) {
              $ct[$category->id] = $category->name;
            }
          }
          ?>
          <?= form_dropdown('category', $ct, set_value('category'), 'class="form-control select2 tip" id="category" required="required"'); ?>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('biller', 'biller'); ?>
          <?php
          $wh[''] = 'Select Biller';
          if ($billers) {
            foreach ($billers as $biller) {
              $wh[$biller->id] = $biller->name;
            }
          }
          echo form_dropdown('biller', $wh, (isset($_POST['biller']) ? $_POST['biller'] : ''), 'id="biller" class="form-control input-tip select2" placeholder="Select biller" required="required"');
          ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('amount', 'amount'); ?>
          <input name="amount" type="text" id="amount" value="" class="pa form-control kb-pad currency" required="required" />
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="form-group">
          <div class="row">
            <div class="col-md-6">
              <?= lang('transfer_to', 'transfer_to') ?>
              <?php
              $bk[''] = 'Select Bank';
              if (!empty($banks)) {
                foreach ($banks as $bank) {
                  $bk[$bank->id] = $bank->name;
                }
              }
              ?>
              <?= form_dropdown('transfer_to', $bk, '', 'class="form-control select2 tip" id="transfer_to" placeholder="Select Transfer To" required="required"'); ?>
            </div>
            <div class="col-md-6">
              <?= lang('current_balance', 'balance_transfer_to'); ?>
              <div id="balance_transfer_to" class="form-control" style="padding: 7px"></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-12">
        <div class="form-group">
          <?= lang('attachment', 'attachment') ?>
          <input id="attachment" type="file" data-browse-label="<?= lang('browse'); ?>" name="userfile" data-show-upload="false" data-show-preview="false" class="form-control file">
        </div>
      </div>
      <div class="col-md-12">
        <div class="form-group">
          <?= lang('note', 'note'); ?>
          <?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : ''), 'class="form-control" id="note"'); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <?php echo form_submit('add_income', lang('add_income'), 'class="btn btn-primary"'); ?>
  </div>
</div>
<?php echo form_close(); ?>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script type="text/javascript" charset="UTF-8">
  $.fn.datetimepicker.dates['sma'] = <?= $dp_lang ?>;
</script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function() {
    $('#transfer_to').change(function() {
      <?php
      $bal = [];
      foreach ($banks as $bank) {
        $bal[$bank->id] = $bank->balance;
      }
      ?>
      let bal = JSON.parse('<?= json_encode($bal); ?>');
      $('#balance_transfer_to').html(currencyFormat(bal[$(this).val()]));
    });

    $.fn.datetimepicker.dates['sma'] = <?= $dp_lang ?>;
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