<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title" id="myModalLabel"><?php echo lang('edit_supplier'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
  echo admin_form_open_multipart('suppliers/edit/' . $supplier->id, $attrib); ?>
  <div class="modal-body">
    <p><?= lang('enter_info'); ?></p>
    <div class="row">
      <div class="col-md-6">
        <div class="form-group company">
          <?= lang('company', 'company'); ?>
          <?php echo form_input('company', $supplier->company, 'class="form-control tip" id="company" required="required"'); ?>
        </div>
        <div class="form-group person">
          <?= lang('name', 'name'); ?>
          <?php echo form_input('name', $supplier->name, 'class="form-control tip" id="name" required="required"'); ?>
        </div>
        <div class="form-group">
          <?= lang('email_address', 'email_address'); ?>
          <input type="email" name="email" class="form-control" id="email_address" value="<?= $supplier->email ?>"/>
        </div>
        <div class="form-group">
          <?= lang('phone', 'phone'); ?>
          <input type="tel" name="phone" class="form-control" required="required" id="phone"
               value="<?= $supplier->phone ?>"/>
        </div>
        <div class="form-group">
          <?= lang('address', 'address'); ?>
          <?php echo form_input('address', $supplier->address, 'class="form-control" id="address" required="required"'); ?>
        </div>
        <div class="form-group">
          <?= lang('city', 'city'); ?>
          <?php echo form_input('city', $supplier->city, 'class="form-control" id="city" required="required"'); ?>
        </div>
        <div class="form-group">
          <?= lang('postal_code', 'postal_code'); ?>
          <?php echo form_input('postal_code', $supplier->postal_code, 'class="form-control" id="postal_code"'); ?>
        </div>
        <div class="form-group">
          <?= lang('country', 'country'); ?>
          <?php echo form_input('country', $supplier->country, 'class="form-control" id="country"'); ?>
        </div>
        <div class="form-group">
          <input type="checkbox" id="update_ss" name="update_ss" value="1">
          <?= lang('update_safety_stock', 'update_ss'); ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('payment_term', 'payment_term'); ?>
          <input name="payment_term" class="form-control" id="payment_term" min="1" max="999" type="number" required="required" value="<?= $supplier->payment_term; ?>" />
        </div>
        <div class="form-group">
          <?= lang('bank_name', 'acc_name'); ?>
          <input name="acc_name" class="form-control" id="acc_name" required="required" value="<?= ($json_data->acc_name ?? ''); ?>" />
        </div>
        <div class="form-group">
          <?= lang('bic_code', 'acc_bic'); ?>
          <input name="acc_bic" class="form-control" id="acc_bic" required="required" value="<?= ($json_data->acc_bic ?? ''); ?>" />
        </div>
        <div class="form-group">
          <?= lang('account_no', 'acc_no'); ?>
          <input name="acc_no" class="form-control" id="acc_no" required="required" value="<?= ($json_data->acc_no ?? ''); ?>" />
        </div>
        <div class="form-group">
          <?= lang('account_holder', 'acc_holder'); ?>
          <input name="acc_holder" class="form-control" id="acc_holder" required="required" value="<?= ($json_data->acc_holder ?? ''); ?>" />
        </div>
        <div class="form-group">
            <?= lang('cycle_purchase', 'cycle_purchase'); ?>
            <input name="cycle_purchase" class="form-control" id="cycle_purchase" value="<?= ($json_data->cycle_purchase ?? ''); ?>" />
          </div>
          <div class="form-group">
            <?= lang('delivery_time', 'delivery_time'); ?>
            <input name="delivery_time" class="form-control" id="delivery_time" value="<?= ($json_data->delivery_time ?? ''); ?>" />
          </div>
        <div class="form-group">
          <?= lang('visit_days', 'visit_days'); ?>
          <input name="visit_days" class="form-control" id="visit_days" value="<?= ($json_data->visit_days ?? ''); ?>" />
        </div>
        <div class="form-group">
          <?= lang('visit_weeks', 'visit_weeks'); ?>
          <input name="visit_weeks" class="form-control" id="visit_weeks" value="<?= ($json_data->visit_weeks ?? ''); ?>" />
        </div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <?php echo form_submit('edit_supplier', lang('edit_supplier'), 'class="btn btn-primary"'); ?>
  </div>
</div>
<?php echo form_close(); ?>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
