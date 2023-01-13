<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fa fa-times"></i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('edit_income'); ?> (<?= $income->reference; ?>)</h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
  echo admin_form_open_multipart('finances/incomes/edit/' . $income->id, $attrib); ?>
  <div class="modal-body">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('date', 'date'); ?>
          <?= form_input('date', $income->date, 'class="form-control datetime" id="date" required="required"'); ?>
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
          <?= form_dropdown('category', $ct, $income->category_id, 'class="form-control select2 tip" id="category" required="required"'); ?>
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
          echo form_dropdown('biller', $wh, $income->biller_id, 'id="biller" class="form-control input-tip select2" placeholder="Select biller" required="required"');
          ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('amount', 'amount'); ?>
          <input name="amount" type="text" id="amount" value="<?= formatStock($income->amount); ?>" class="pa form-control kb-pad currency" required="required" />
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
              <?= form_dropdown('transfer_to', $bk, $income->bank_id, 'class="form-control select2 tip" id="transfer_to" placeholder="Select Transfer To" required="required"'); ?>
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
          <?php echo form_textarea('note', htmlDecode($income->note), 'class="form-control" id="note"'); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <?php echo form_submit('edit_income', lang('edit_income'), 'class="btn btn-primary"'); ?>
  </div>
</div>
<?php echo form_close(); ?>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
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
  });
</script>