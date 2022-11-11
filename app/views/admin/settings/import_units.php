<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fa fa-times"></i>
      </button>
      <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('add_units_csv'); ?></h4>
    </div>
    <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
    echo admin_form_open_multipart('system_settings/import_units', $attrib); ?>
    <div class="modal-body">
      <div class="well well-small">
        <a href="https://docs.google.com/spreadsheets/d/183mGcCxbAsEDAmo2Xs9RgSi_Dn8UlVX4_sqV0TgqM0s/edit?usp=sharing"
          class="btn btn-primary pull-right" target="_blank"><i class="fa fa-link"></i> View Master File</a>
        <p>Please change Googlesheet Master data, then download it</p>
        <p>as CSV and import to this page or sync it from Googlesheet.</p>
      </div>
      <div class="form-group">
        <?= lang('upload_file', 'csv_file') ?>
        <input id="csv_file" accept=".csv" type="file" data-browse-label="<?= lang('browse'); ?>" data-show-upload="false"
          data-show-preview="false" name="csv_file" class="form-control file" required />
      </div>
    </div>
    <div class="modal-footer">
      <input name="import" type="hidden" value="1">
      <button class="btn btn-primary" type="submit"><i class="fa fa-upload"></i> Import</button>
      <button class="btn btn-success" id="sync_googlesheet" type="button"><i class="fa fa-sync"></i> Sync from Googlesheet</button>
    </div>
    <?php echo form_close(); ?>
  </div>
</div>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script>
  $('#sync_googlesheet').click(function () {
    let data = {};
    data[security.csrf_token_name] = security.csrf_hash;
    data.command = 'syncGoogleSheet';
    $.ajax({
      data: data,
      method: 'POST',
      success: function (data) {
        if (typeof data == 'object' && ! data.error) {
          addAlert(data.msg, 'success');
          if (oTable) oTable.fnDraw();
        } else if (typeof data == 'object' && data.error) {
          addAlert(data.msg, 'danger');
        } else {
          addAlert('Unknown error', 'danger');
        }

        $('#myModal').modal('hide');
      },
      url: site.base_url + 'system_settings/import_units'
    });
  });
</script>