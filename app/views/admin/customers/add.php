<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title" id="myModalLabel"><?php echo lang('add_customer'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form', 'id' => 'add-customer-form'];
  echo admin_form_open_multipart('customers/add', $attrib); ?>
  <div class="modal-body">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label class="control-label" for="customer_group"><?php echo $this->lang->line('customer_group'); ?></label>
          <?php
          $cgs[''] = lang('select') . ' ' . lang('customer_group');
          foreach ($customer_groups as $customer_group) {
            $cgs[$customer_group->id] = $customer_group->name;
          }
          if (!$Owner && !$Admin) {
            $disabled = ' disabled="disabled"';
            echo form_hidden('customer_group', 1);
          } else {
            $disabled = '';
          }
          echo form_dropdown('customer_group', $cgs, $Settings->customer_group, 'class="form-control select2" id="customer_group" style="width:100%;" required="required" ' . $disabled);
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
          echo form_dropdown('price_group', $pgs, '', 'class="form-control select2" id="price_group" style="width:100%;" readonly="true"');
          ?>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6">
        <div class="form-group company">
          <?= lang('company', 'company'); ?>
          <?php echo form_input('company', '', 'class="form-control tip" id="company"'); ?>
        </div>
        <div class="form-group person">
          <?= lang('name', 'name'); ?>
          <?php echo form_input('name', '', 'class="form-control tip" id="name" data-bv-notempty="true"'); ?>
        </div>
        <div class="form-group">
          <?= lang('email_address', 'email_address'); ?>
          <input type="email" name="email" class="form-control" id="email_address" data-error="Harap masukkan email" required="required" />
        </div>
        <div class="form-group">
          <?= lang('phone', 'phone'); ?>
          <input type="text" name="phone" class="form-control" required="required" id="phone" pattern="^[0-9]{1,}$" />
        </div>
        <div class="form-group">
          <?= lang('address', 'address'); ?>
          <?php echo form_input('address', '', 'class="form-control" id="address"'); ?>
        </div>
        <div class="form-group">
          <?= lang('ship_address', 'ship_address'); ?>
          <?php echo form_input('ship_address', '', 'class="form-control" id="ship_address"'); ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('postal_code', 'postal_code'); ?>
          <?php echo form_input('postal_code', '', 'class="form-control" id="postal_code"'); ?>
        </div>
        <div class="form-group">
          <?= lang('country', 'country'); ?>
          <?php echo form_input('country', '', 'class="form-control" id="country"'); ?>
        </div>
        <div class="form-group">
          <?= lang('city', 'city'); ?>
          <?php echo form_input('city', '', 'class="form-control" id="city"'); ?>
        </div>
        <div class="form-group">
          <?= lang('state', 'state'); ?>
          <?php
          echo form_input('state', '', 'class="form-control" id="state"');
          ?>
        </div>
        <div class="form-group">
          <?= lang('term_of_payment', 'payment_term'); ?>
          <?php echo form_input('payment_term', '', 'class="form-control" id="payment_term"'); ?>
        </div>
        <div class="form-group">
          <label for="notify_wa">Notify WA</label>
          <select id="notify_wa" name="notify_wa" class="select2" style="width:100%;">
            <option value="" selected>(Send by Default)</option>
            <option value="1">Yes</option>
            <option value="0">No</option>
          </select>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <?php echo form_submit('add_customer', lang('add_customer'), 'class="btn btn-primary"'); ?>
  </div>
</div>
<?php echo form_close(); ?>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script type="text/javascript">
  $(document).ready(function(e) {
    $('#add-customer-form').bootstrapValidator({
      feedbackIcons: {
        valid: 'fad fa-check',
        invalid: 'fad fa-times',
        validating: 'fad fa-refresh'
      },
      excluded: [':disabled']
    });
    initSelect2();
    fields = $('.modal-content').find('.form-control');
    $.each(fields, function() {
      var id = $(this).attr('id');
      var iname = $(this).attr('name');
      var iid = '#' + id;
      if (!!$(this).attr('data-bv-notempty') || !!$(this).attr('required')) {
        $("label[for='" + id + "']").append(' *');
        $(document).on('change', iid, function() {
          $('form[data-toggle="validator"]').bootstrapValidator('revalidateField', iname);
        });
      }
    });
  });
</script>