<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('edit_bank'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form', 'id' => 'edit-bank-form'];
  echo admin_form_open_multipart('finances/banks/edit/' . $bank->id, $attrib); ?>
  <div class="modal-body">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('code', 'code'); ?>
          <?php echo form_input('code', $bank->code, 'class="form-control" id="code" required="required"'); ?>
        </div>
        <div class="form-group">
          <?= lang('account_holder', 'holder'); ?>
          <?php echo form_input('holder', $bank->holder, 'class="form-control tip" id="holder"'); ?>
        </div>
        <div class="form-group">
          <?= lang('biller', 'biller_id'); ?>
          <?php
          $whc = [];
          foreach ($billers as $biller) {
            $whc[$biller->id] = $biller->name;
          }
          echo form_dropdown('biller_id', $whc, $bank->biller_id, 'class="form-control select2" id="biller_id" required="required" style="width:100%;"'); ?>
        </div>
        <div class="form-group">
          <?= lang('type', 'type'); ?>
          <?php
          $types = $this->site->getBankTypes(TRUE); // TRUE = add empty
          $ty = [];
          foreach ($types as $type) {
            $ty[$type] = $type;
          }
          echo form_dropdown('type', $ty, $bank->type, 'class="form-control select2-tags" id="type" required="required" style="width:100%;"'); ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('name', 'name'); ?>
          <?php echo form_input('name', $bank->name, 'class="form-control tip" id="name" required="required"'); ?>
        </div>
        <div class="form-group">
          <?= lang('bic_code', 'bic'); ?>
          <?php echo form_input('bic', $bank->bic, 'class="form-control tip" id="bic"'); ?>
        </div>
        <div class="form-group">
          <?= lang('number', 'number'); ?>
          <?php echo form_input('number', $bank->number, 'class="form-control" id="number"'); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <?php echo form_submit('edit_bank', lang('edit_bank'), 'class="btn btn-primary"'); ?>
  </div>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
  $(document).ready(function (e) {
  });
</script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>