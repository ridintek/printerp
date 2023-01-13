<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-2x">&times;</i>
      </button>
      <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('import_expense_categories'); ?></h4>
    </div>
    <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
    echo admin_form_open_multipart('system_settings/import_income_categories', $attrib); ?>
    <div class="modal-body">
      <div class="well well-small">
        <a href="https://docs.google.com/spreadsheets/d/1n7IZ4vNo2KjlhBMvXu3JmxvdETjDStz3XZ7wy7tXRU4/edit?usp=sharing"
          class="btn btn-primary pull-right" target="_blank"><i class="fa fa-link"></i> View Master File</a>
        <p>Change data from master file, then download it as CSV.<p>
        <p>After CSV downloaded, you can import it to this page.</p>
      </div>
      <div class="form-group">
        <?= lang('upload_file', 'csv_file') ?>
        <input id="csv_file" accept=".csv" type="file" data-browse-label="<?= lang('browse'); ?>" data-show-upload="false"
          data-show-preview="false" name="csv_file" class="form-control file" required />
      </div>
    </div>
    <div class="modal-footer">
      <input name="import" type="hidden" value="1">
      <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-upload"></i> <?= lang('import'); ?></button>
    </div>
    <?php echo form_close(); ?>
  </div>
</div>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
