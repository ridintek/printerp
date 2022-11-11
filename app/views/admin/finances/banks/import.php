<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('add_banks_csv'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
  echo admin_form_open_multipart('finances/banks/import', $attrib); ?>
  <div class="modal-body">
    <div class="well well-small">
      <a href="https://docs.google.com/spreadsheets/d/1LExLFG-RJ0RH3oqlLGSAh8Pjvrw8avxX_kh5zk6RWpY/edit?usp=sharing"
        class="btn btn-primary pull-right" target="_blank"><i class="fa fa-link"></i> View Master File</a>
      <p>Change data from master file, then download it as CSV.<p>
      <p>After CSV downloaded, you can import it to this page.</p>
    </div>
    <div class="form-group">
      <?= lang('date', 'date'); ?>
      <input id="date" class="form-control datetime" name="date" required="required">
    </div>
    <div class="form-group">
      <?= lang('upload_file', 'csv_file') ?>
      <input id="csv_file" accept=".csv" type="file" data-browse-label="<?= lang('browse'); ?>" data-show-upload="false"
        data-show-preview="false" name="csv_file" class="form-control file" required="required" />
    </div>
  </div>
  <div class="modal-footer">
    <input name="import" type="hidden" value="1">
    <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-upload"></i> <?= lang('import'); ?></button>
  </div>
  <?php echo form_close(); ?>
</div>
<script src="<?= $assets ?>js/custom.js"></script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
