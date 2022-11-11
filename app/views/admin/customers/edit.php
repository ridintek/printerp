<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title" id="myModalLabel"><?php echo lang('edit_customer'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
  echo admin_form_open_multipart('customers/edit/' . $customer->id, $attrib); ?>
  <div class="modal-body">
    <div>Once you edit, you cannot edit anymore unless you have edit customer privilege.</div>
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label class="control-label" for="customer_group"><?php echo $this->lang->line('customer_group'); ?></label>
          <?php
          foreach ($customer_groups as $customer_group) {
            $cgs[$customer_group->id] = $customer_group->name;
          }
          if (!$Owner && !$Admin) {
            $disabled = ' disabled="disabled"';
            echo form_hidden('customer_group', 1);
          } else {
            $disabled = '';
          }
          echo form_dropdown('customer_group', $cgs, $customer->customer_group_id, 'class="form-control select2" id="customer_group" style="width:100%;" required="required"' . $disabled);
          ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label class="control-label" for="price_group"><?php echo $this->lang->line('price_group'); ?></label>
          <?php
          $pgs[''] = lang('select') . ' ' . lang('price_group');
          foreach ($price_groups as $price_group) {
            if ($Admin || $Owner) {
              $pgs[$price_group->id] = $price_group->name;
            }
          }
          echo form_dropdown('price_group', $pgs, $customer->price_group_id, 'class="form-control select2" id="price_group" style="width:100%;"');
          ?>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="form-group company">
          <?= lang('company', 'company'); ?>
          <?php echo form_input('company', $customer->company, 'class="form-control tip" id="company"'); ?>
        </div>
        <div class="form-group person">
          <?= lang('name', 'name'); ?>
          <?php echo form_input('name', $customer->name, 'class="form-control tip" id="name" required="required"'); ?>
        </div>
        <div class="form-group">
          <?= lang('email_address', 'email_address'); ?>
          <input type="email" name="email" class="form-control" id="email_address" value="<?= $customer->email ?>" />
        </div>
        <div class="form-group">
          <?= lang('phone', 'phone'); ?>
          <input type="tel" name="phone" class="form-control" required="required" id="phone" value="<?= $customer->phone ?>" />
        </div>
        <div class="form-group">
          <?= lang('address', 'address'); ?>
          <?php echo form_input('address', $customer->address, 'class="form-control" id="address"'); ?>
        </div>
        <div class="form-group">
          <?= lang('ship_address', 'ship_address'); ?>
          <?php echo form_input('ship_address', $customer->ship_address, 'class="form-control" id="ship_address"'); ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('city', 'city'); ?>
          <?php echo form_input('city', $customer->city, 'class="form-control" id="city"'); ?>
        </div>
        <div class="form-group">
          <?= lang('state', 'state'); ?>
          <?php
          echo form_input('state', $customer->state, 'class="form-control" id="state"');
          ?>
        </div>
        <div class="form-group">
          <?= lang('postal_code', 'postal_code'); ?>
          <?php echo form_input('postal_code', $customer->postal_code, 'class="form-control" id="postal_code"'); ?>
        </div>
        <div class="form-group">
          <?= lang('country', 'country'); ?>
          <?php echo form_input('country', $customer->country, 'class="form-control" id="country"'); ?>
        </div>
        <div class="form-group">
          <?= lang('term_of_payment', 'payment_term'); ?>
          <?php echo form_input('payment_term', $customer->payment_term, 'class="form-control" id="payment_term"'); ?>
        </div>
        <div class="form-group">
          <label for="notify_wa">Notify WA</label>
          <select id="notify_wa" name="notify_wa" class="select2" style="width:100%;">
            <option value="">(Send by Default)</option>
            <option value="1">Yes</option>
            <option value="0">No</option>
          </select>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <?php echo form_submit('edit_customer', lang('edit_customer'), 'class="btn btn-primary"'); ?>
  </div>
</div>
<?php echo form_close(); ?>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script>
  $(document).ready(function () {
    let notify_wa = '<?= ($customerJS->notify_wa ?? '') ?>';

    $('#notify_wa').val(notify_wa).trigger('change');
  });
</script>