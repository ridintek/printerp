<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title" id="myModalLabel"><?php echo lang('add_notification'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
  echo admin_form_open('notifications/add', $attrib); ?>
  <div class="modal-body">
    <p><?= lang('enter_info'); ?></p>
    <div class="well well-sm">
      <div class="row">
        <div class="col-sm-6">
          <div class="form-group">
            <?php echo lang('from', 'from_date'); ?>
            <div class="controls">
              <?php echo form_input('from_date', '', 'class="form-control" id="from_date" required="required"'); ?>
            </div>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="form-group">
            <?php echo lang('till', 'to_date'); ?>
            <div class="controls">
              <?php echo form_input('to_date', '', 'class="form-control" id="to_date" required="required"'); ?>
            </div>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="form-group">
            <?= lang('type', 'type'); ?>
            <?php
              $tp = [
                '' => '',
                'danger'  => 'Critical',
                'info'    => 'Info',
                'success' => 'Success',
                'warning' => 'Warning'
              ];
            ?>
            <?= form_dropdown('type', $tp, 'info', 'class="select2" data-placeholder="Select Message Type" style="width:100%;"'); ?>
          </div>
        </div>
      </div>
    </div>
    <div class="form-group">
      <?php echo lang('comment', 'comment'); ?>
      <div class="controls">
        <?php echo form_textarea($comment); ?>
      </div>
    </div>
    <div class="form-group">
      <input type="radio" class="checkbox" name="scope" value="1" id="customer"><label for="customer"
        class="padding05"><?= lang('for_customers_only') ?></label>
      <input type="radio" class="checkbox" name="scope" value="2" id="staff"><label for="staff"
        class="padding05"><?= lang('for_staff_only') ?></label>
      <input type="radio" class="checkbox" name="scope" value="3" id="both" checked="checked"><label
        for="both" class="padding05"><?= lang('for_both') ?></label>
    </div>
  </div>
  <div class="modal-footer">
    <?php echo form_submit('add_notification', lang('add_notification'), 'class="btn btn-primary"'); ?>
  </div>
</div>
<?php echo form_close(); ?>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function () {
    $.fn.datetimepicker.dates['sma'] = <?=$dp_lang?>;
    let tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);

    $('#from_date').datetimepicker({
      format: site.dateFormats.js_ldate,
      fontAwesome: true,
      language: 'sma',
      todayBtn: 1,
      autoclose: 1,
      minView: 2
    }).datetimepicker('update', new Date());

    $('#to_date').datetimepicker({
      format: site.dateFormats.js_ldate,
      fontAwesome: true,
      language: 'sma',
      todayBtn: 1,
      autoclose: 1,
      minView: 2
    }).datetimepicker('update', tomorrow);
  });
</script>