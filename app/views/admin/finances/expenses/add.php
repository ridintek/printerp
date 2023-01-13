<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('add_expense'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
  echo admin_form_open_multipart('finances/expenses/add', $attrib); ?>
  <div class="modal-body">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('date', 'date'); ?>
          <input class="form-control datetimenow" id="date" name="date" type="text" required="required" value="">
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('biller', 'biller'); ?>
          <?php
          $wh[''] = lang('select') . ' ' . lang('biller');
          if ($billers) {
            foreach ($billers as $biller) {
              $wh[$biller->id] = $biller->name;
            }
          }
          echo form_dropdown('biller', $wh, (isset($_POST['biller']) ? $_POST['biller'] : $this->Settings->default_biller), 'id="biller" class="form-control input-tip select2" style="width:100%;" required="required"');
          ?>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('category', 'category'); ?>
          <?php
          $ct[''] = lang('select') . ' ' . lang('category');
          if ($categories) {
            foreach ($categories as $category) {
              // Skip for asset purchase.
              if ($category->id == 18 || $category->id == 19) continue;

              $ct[$category->id] = $category->name;
            }
          }
          ?>
          <?= form_dropdown('category', $ct, set_value('category'), 'class="form-control select2 tip" id="category" required="required" style="width:100%;"'); ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('supplier', 'supplier'); ?>
          <div class="input-group">
            <select class="form-control" name="supplier" id="supplier"></select>
            <?php if ($Owner || $Admin || $GP['suppliers-index']) { ?>
              <div class="input-group-addon no-print" style="padding: 2px 5px; border-left: 0;">
                <a href="#" id="view-supplier" class="external" data-toggle="modal" data-target="#myModal">
                  <i class="fa fa-user" id="addIcon"></i>
                </a>
              </div>
            <?php } ?>
            <?php if ($Owner || $Admin || $GP['suppliers-add']) { ?>
              <div class="input-group-addon no-print" style="padding: 2px 5px;">
                <a href="<?= admin_url('suppliers/add'); ?>" id="add-supplier" class="external" data-toggle="modal" data-target="#myModal2">
                  <i class="fa fa-plus-circle" id="addIcon"></i>
                </a>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
    <div class="form-group well well-sm">
      <div class="row">
        <div class="col-md-6">
          <?= lang('paid_by', 'paid_by') ?>
          <?php
          $bk[''] = lang('select') . ' ' . lang('paid_by');
          if (!empty($banks)) {
            foreach ($banks as $bank) {
              $bk[$bank->id] = $bank->name;
            }
          }
          ?>
          <?= form_dropdown('paid_by', $bk, '', 'class="form-control select2 tip" id="paid_by" required="required" style="width:100%;"'); ?>
        </div>
        <div class="col-md-6">
          <?= lang('current_balance', 'balance_paid_by'); ?>
          <div id="balance_paid_by" class="form-control" style="padding: 7px"></div>
        </div>
      </div>
    </div>
    <div class="form-group">
      <?= lang('amount', 'amount'); ?>
      <input name="amount" type="text" id="amount" value="" class="pa form-control kb-pad currency" required="required" />
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
    <?php echo form_submit('add_expense', lang('add_expense'), 'class="btn btn-primary"'); ?>
  </div>
</div>
<?php echo form_close(); ?>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function() {
    $('.datetimenow').datetimepicker({
      format: site.dateFormats.js_ldate,
      fontAwesome: true,
      language: 'sma',
      weekStart: 1,
      todayBtn: 1,
      autoclose: 1,
      todayHighlight: 1,
      minView: 2
    }).datetimepicker('update', new Date());

    $('#paid_by').change(function() {
      <?php
      $bal = [];
      foreach ($banks as $bank) {
        $bal[$bank->id] = $bank->balance;
      }
      ?>
      let bal = JSON.parse('<?= json_encode($bal); ?>');
      $('#balance_paid_by').html(currencyFormat(bal[$(this).val()]));
    });
  });
</script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>