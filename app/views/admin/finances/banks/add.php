<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('add_bank_account'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form', 'id' => 'add-bank-form'];
  echo admin_form_open_multipart('finances/banks/add', $attrib); ?>
  <div class="modal-body">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('code', 'code'); ?>
          <?php echo form_input('code', '', 'class="form-control" id="code" required="required"'); ?>
        </div>
        <div class="form-group">
          <?= lang('account_holder', 'holder'); ?>
          <?php echo form_input('holder', '', 'class="form-control tip" id="holder"'); ?>
        </div>
        <div class="form-group">
          <?= lang('biller', 'biller_id'); ?>
          <?php
          $whc[''] = '';
          foreach ($billers as $biller) {
            $whc[$biller->id] = $biller->name;
          }
          echo form_dropdown('biller_id', $whc, '', 'class="form-control select2" id="biller_id" data-placeholder="Select biller" required="required" style="width:100%;"'); ?>
        </div>
        <div class="form-group">
          <?= lang('type', 'type'); ?>
          <?php
          $types = $this->site->getBankTypes(TRUE); // TRUE = add empty
          foreach ($types as $type) {
            $ty[$type] = $type;
          }
          echo form_dropdown('type', $ty, '', 'class="form-control select2-tags" id="bank_type" data-placeholder="Select Bank Type" required="required" style="width:100%;"'); ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('name', 'name'); ?>
          <?php echo form_input('name', '', 'class="form-control tip" id="name" required="required"'); ?>
        </div>
        <div class="form-group">
          <?= lang('bic_code', 'bic'); ?>
          <?php echo form_input('bic', '', 'class="form-control tip" id="bic"'); ?>
        </div>
        <div class="form-group">
          <?= lang('number', 'number'); ?>
          <?php echo form_input('number', '', 'class="form-control" id="number"'); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <?php echo form_submit('add_bank_account', lang('add_bank_account'), 'class="btn btn-primary"'); ?>
  </div>
</div>
<?php echo form_close(); ?>
<script async src="<?= $assets ?>js/modal.js?v=<?= $hash; ?>"></script>
<script async src="<?= $assets ?>js/custom.js"></script>
<script>
  $(document).ready(function (e) {
  });
</script>
