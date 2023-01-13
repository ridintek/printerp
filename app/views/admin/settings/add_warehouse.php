<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fad fa-times"></i>
      </button>
      <h4 class="modal-title" id="myModalLabel"><?php echo lang('add_warehouse'); ?></h4>
    </div>
    <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
    echo admin_form_open_multipart('system_settings/add_warehouse', $attrib); ?>
    <div class="modal-body">
    <p><?= lang('enter_info'); ?></p>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="code"><?php echo $this->lang->line('code'); ?></label>
            <?php echo form_input('code', '', 'class="form-control" id="code" required="required"'); ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="name"><?php echo $this->lang->line('name'); ?></label>
            <?php echo form_input('name', '', 'class="form-control" id="name" required="required"'); ?>
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
            echo form_dropdown('price_group', $pgs, '', 'class="select2" id="price_group" style="width:100%;"');
            ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="phone"><?php echo $this->lang->line('phone'); ?></label>
            <?php echo form_input('phone', '', 'class="form-control" id="phone"'); ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="email"><?php echo $this->lang->line('email'); ?></label>
            <?php echo form_input('email', '', 'class="form-control" id="email"'); ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="geolocation"><?= lang('geolocation') ?></label>
            <?php echo form_input('geolocation', '', 'class="form-control" id="geolocation"'); ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="cycle_transfer"><?= lang('cycle_transfer') ?></label>
            <?php echo form_input('cycle_transfer', '', 'class="form-control" id="cycle_transfer"'); ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="delivery_time"><?= lang('delivery_time') ?></label>
            <?php echo form_input('delivery_time', '', 'class="form-control" id="delivery_time"'); ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="visit_days"><?= lang('visit_days') ?></label>
            <?php echo form_input('visit_days', '', 'class="form-control" id="visit_days"'); ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label" for="visit_weeks"><?= lang('visit_weeks') ?></label>
            <?php echo form_input('visit_weeks', '', 'class="form-control" id="visit_weeks"'); ?>
          </div>
        </div>
        <div class="col-md-12">
          <div class="form-group">
            <label class="control-label" for="address"><?php echo $this->lang->line('address'); ?></label>
            <?php echo form_textarea('address', '', 'class="form-control" id="address" required="required"'); ?>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <?php echo form_submit('add_warehouse', lang('add_warehouse'), 'class="btn btn-primary"'); ?>
    </div>
  </div>
  <?php echo form_close(); ?>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
