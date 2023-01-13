<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fad fa-times"></i>
      </button>
      <h4 class="modal-title" id="myModalLabel"><?php echo lang('edit_warehouse'); ?></h4>
    </div>
    <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
    echo admin_form_open_multipart('system_settings/edit_warehouse/' . $id, $attrib); ?>
    <div class="modal-body">
      <p><?= lang('enter_info'); ?></p>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="code"><?php echo $this->lang->line('code'); ?></label>
            <?php echo form_input('code', $warehouse->code, 'class="form-control" id="code" required="required"'); ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="name"><?php echo $this->lang->line('name'); ?></label>
            <?php echo form_input('name', $warehouse->name, 'class="form-control" id="name" required="required"'); ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="price_group"><?php echo $this->lang->line('price_group'); ?></label>
            <?php
            $pgs[''] = lang('select') . ' ' . lang('price_group');
            foreach ($price_groups as $price_group) {
              $pgs[$price_group->id] = $price_group->name;
            }
            echo form_dropdown('price_group', $pgs, $warehouse->price_group_id, 'class="select2" id="price_group" style="width:100%;"');
            ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="phone"><?php echo $this->lang->line('phone'); ?></label>
            <?php echo form_input('phone', $warehouse->phone, 'class="form-control" id="phone"'); ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="email"><?php echo $this->lang->line('email'); ?></label>
            <?php echo form_input('email', $warehouse->email, 'class="form-control" id="email"'); ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="geolocation"><?= lang('geolocation') ?></label>
            <?php echo form_input('geolocation', $warehouse->geolocation, 'class="form-control" id="geolocation"'); ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="cycle_transfer"><?= lang('cycle_transfer') ?></label>
            <?php echo form_input('cycle_transfer', ($warehouse_js->cycle_transfer ?? ''), 'class="form-control" id="cycle_transfer"'); ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="delivery_time"><?= lang('delivery_time') ?></label>
            <?php echo form_input('delivery_time', ($warehouse_js->delivery_time ?? ''), 'class="form-control" id="delivery_time"'); ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="visit_days"><?= lang('visit_days') ?></label>
            <?php echo form_input('visit_days', ($warehouse_js->visit_days ?? ''), 'class="form-control" id="visit_days"'); ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="visit_weeks"><?= lang('visit_weeks') ?></label>
            <?php echo form_input('visit_weeks', ($warehouse_js->visit_weeks ?? ''), 'class="form-control" id="visit_weeks"'); ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <input type="checkbox" id="active" name="active" value="1">
            <label class="control-label" for="active"><?= lang('active') ?></label>
          </div>
        </div>
        <div class="col-md-12">
          <div class="form-group">
            <label class="control-label" for="address"><?php echo $this->lang->line('address'); ?></label>
            <?php echo form_textarea('address', $warehouse->address, 'class="form-control" id="address" required="required"'); ?>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <div style="float: left">
        <input type="checkbox" id="update_ss" name="update_ss" value="1">
        <?= lang('update_safety_stock', 'update_ss'); ?>
      </div>
      <?php echo form_submit('edit_warehouse', lang('edit_warehouse'), 'class="btn btn-primary"'); ?>
    </div>
  </div>
  <?php echo form_close(); ?>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script>
  (function() {
    let active = '<?= $warehouse->active ?>';

    if (active == 1) {
      $('#active').iCheck('check');
    }
  })();
</script>