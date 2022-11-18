<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title" id="myModalLabel"><?php echo lang('edit_biller'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
  echo admin_form_open_multipart('billers/edit/' . $biller->id, $attrib); ?>
  <div class="modal-body">
    <p><?= lang('enter_info'); ?></p>
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('logo', 'biller_logo'); ?>
          <?php
          $biller_logos = [];
          foreach ($logos as $key => $value) {
            $biller_logos[$value] = $value;
          }
          echo form_dropdown('logo', $biller_logos, $biller->logo, 'class="form-control select2" id="biller_logo" required="required" '); ?>
        </div>
      </div>
      <div class="col-md-6">
        <div id="logo-con" class="text-center"><img src="<?= base_url('assets/uploads/logos/' . $biller->logo) ?>" alt=""></div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6">
        <div class="form-group company">
          <?= lang('company', 'company'); ?>
          <?php echo form_input('company', $biller->company, 'class="form-control tip" id="company" required="required"'); ?>
        </div>
        <div class="form-group person">
          <?= lang('name', 'name'); ?>
          <?php echo form_input('name', $biller->name, 'class="form-control tip" id="name" required="required"'); ?>
        </div>
        <div class="form-group">
          <?= lang('email_address', 'email_address'); ?>
          <input type="email" name="email" class="form-control" required="required" id="email_address" value="<?= $biller->email ?>" />
        </div>
        <div class="form-group">
          <?= lang('phone', 'phone'); ?>
          <input type="tel" name="phone" class="form-control" required="required" id="phone" value="<?= $biller->phone ?>" />
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('whatsapp', 'whatsapp'); ?>
          <input type="tel" name="whatsapp" class="form-control" required="required" id="whatsapp" value="<?= (!empty($biller->json_data) ? json_decode($biller->json_data)->whatsapp : '') ?>" />
        </div>
        <div class="form-group">
          <?= lang('address', 'address'); ?>
          <?php echo form_input('address', $biller->address, 'class="form-control" id="address" required="required"'); ?>
        </div>
        <div class="form-group">
          <?= lang('city', 'city'); ?>
          <?php echo form_input('city', $biller->city, 'class="form-control" id="city" required="required"'); ?>
        </div>
        <div class="form-group">
          <?= lang('target', 'target'); ?>
          <?php echo form_input('target', ($billerJS->target ?? 0), 'class="form-control currency" id="target"'); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <?php echo form_submit('edit_biller', lang('edit_biller'), 'class="btn btn-primary"'); ?>
  </div>
</div>
<?php echo form_close(); ?>
<script type="text/javascript" charset="utf-8">
  $(document).ready(function() {
    $('#biller_logo').change(function(event) {
      var biller_logo = $(this).val();
      $('#logo-con').html('<img src="<?= base_url('assets/uploads/logos') ?>/' + biller_logo + '" alt="">');
    });

    $('#target').val(formatCurrency('<?= $billerJS->target ?? 0 ?>'));
  });
</script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>