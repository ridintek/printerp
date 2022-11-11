<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('edit_expense'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
  echo admin_form_open_multipart('finances/expenses/edit/' . $expense->id, $attrib); ?>
  <div class="modal-body">
    <div class="form-group">
      <?= lang('reference', 'reference'); ?>
      <?= form_input('reference', $expense->reference, 'class="form-control tip" id="reference" required="required"'); ?>
    </div>
    <div class="form-group">
      <?= lang('date', 'date'); ?>
      <?= form_input('date', $expense->date, 'class="form-control datetime" id="date" required="required"'); ?>
    </div>
    <div class="form-group">
      <?= lang('category', 'category'); ?>
      <?php
      $ct[''] = lang('select') . ' ' . lang('category');
      foreach ($categories as $category) {
        // Skip for asset purchase.
        if ($category->id == 18 || $category->id == 19) continue;

        $ct[$category->id] = $category->name;
      }
      ?>
      <?= form_dropdown('category', $ct, $expense->category_id, 'class="form-control tip select2" id="category"'); ?>
    </div>
    <div class="form-group">
      <?= lang('biller', 'biller'); ?>
      <?php
      $wh[''] = lang('select') . ' ' . lang('biller');
      if ($billers) {
        foreach ($billers as $biller) {
          $wh[$biller->id] = $biller->name;
        }
      }
      echo form_dropdown('biller', $wh, $expense->biller_id, 'id="biller" class="form-control input-tip select2" style="width:100%;" ');
      ?>
    </div>
    <div class="form-group">
      <?= lang('paid_by', 'paid_by'); ?>
      <?php
      $pb[''] = '';
      if ($banks) {
        foreach ($banks as $bank) {
          $pb[$bank->id] = $bank->name;
        }
      }
      echo form_dropdown('paid_by', $pb, $expense->bank_id, 'id="paid_by" class="form-control input-tip select2" placeholder="Select paid by" style="width:100%;" ');
      ?>
    </div>
    <div class="form-group">
      <?= lang('old_amount', 'old_amount'); ?>
      <input name="old_amount" type="number" id="old_amount" value="<?= $this->sma->formatDecimal($expense->amount); ?>" class="pa form-control kb-pad amount" required="required" readonly="readonly" />
    </div>
    <div class="form-group">
      <?= lang('new_amount', 'new_amount'); ?>
      <input name="new_amount" type="number" id="new_amount" value="<?= $this->sma->formatDecimal($expense->amount); ?>" class="pa form-control kb-pad amount" required="required" />
    </div>
    <div class="form-group">
      <?= lang('supplier', 'supplier'); ?>
      <select class="form-control" id="supplier" name="supplier" data-placeholder="Select Supplier"></select>
    </div>
    <div class="form-group">
      <?= lang('attachment', 'attachment') ?>
      <input id="attachment" type="file" data-browse-label="<?= lang('browse'); ?>" name="userfile" data-show-upload="false" data-show-preview="false" class="form-control file">
    </div>
    <div class="form-group">
      <?= lang('note', 'note'); ?>
      <?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : $expense->note), 'class="form-control" id="note"'); ?>
    </div>
  </div>
  <div class="modal-footer">
    <?php echo form_submit('edit_expense', lang('edit_expense'), 'class="btn btn-primary"'); ?>
  </div>
</div>
<?php echo form_close(); ?>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function() {
    let supplier_id = '<?= $expense->supplier_id; ?>';

    preSelectSupplier('#supplier', supplier_id);

    document.querySelector('#new_amount').addEventListener('wheel', (ev) => {
      ev.preventDefault();
    });
  });
</script>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>