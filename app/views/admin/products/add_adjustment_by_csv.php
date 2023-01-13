<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script type="text/javascript">

  $(document).ready(function () {

  });
</script>

<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fad fa-file-import"></i><?= lang('add_adjustment_by_csv'); ?></h2>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <?php
        $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
        echo admin_form_open_multipart('products/add_adjustment_by_csv', $attrib); ?>
        <div class="row">
          <div class="col-lg-12">

            <div class="col-md-4">
              <div class="form-group">
                <?= lang('date', 'qadate'); ?>
                <?php echo form_input('date', (isset($_POST['date']) ? $_POST['date'] : ''), 'class="form-control input-tip datetimenow" id="qadate" required="required"'); ?>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <?= lang('reference', 'qaref'); ?>
                <?php echo form_input('reference', (isset($_POST['reference']) ? $_POST['reference'] : ''), 'class="form-control input-tip" id="qaref"'); ?>
              </div>
            </div>

            <?php if ($Owner || $Admin || !$this->session->userdata('warehouse_id')) { ?>
              <div class="col-md-4">
                <div class="form-group">
                  <?= lang('warehouse', 'qawarehouse'); ?>
                  <?php
                  $wh[''] = '';
          foreach ($warehouses as $warehouse) {
            $wh[$warehouse->id] = $warehouse->name;
          }
          echo form_dropdown('warehouse', $wh, (isset($_POST['warehouse']) ? $_POST['warehouse'] : ''), 'id="qawarehouse" class="select2" data-placeholder="' . lang('select') . ' ' . lang('warehouse') . '" required="required" style="width:100%;"'); ?>
                </div>
              </div>
              <?php
        } else {
          $warehouse_input = [
            'type'  => 'hidden',
            'name'  => 'warehouse',
            'id'    => 'qawarehouse',
            'value' => $this->session->userdata('warehouse_id'),
          ];

          echo form_input($warehouse_input); } ?>

            <div class="clearfix"></div>
            <div class="col-md-12">

              <div class="well well-small">
                <a href="https://docs.google.com/spreadsheets/d/1OQ7kHaoQL7opzr9IKtqOCXJG3Pzc0Xmb-sOGoQFquzo/edit?usp=sharing"
                  class="btn btn-primary pull-right" target="_blank"><i class="fad fa-link"></i> View Master File</a>
                <p>Change data from master file, then download it as CSV.<p>
                <p>After CSV downloaded, you can import it to this page.</p>
               </div>

               <div class="form-group">
                <?= lang('upload_file', 'csv_file') ?>
                <input type="file" accept=".csv" data-browse-label="<?= lang('browse'); ?>" name="csv_file" class="form-control file"
                  data-show-upload="false" data-show-preview="false" id="csv_file" required="required"/>
              </div>

            </div>
            <div class="col-md-4">
              <div class="form-group">
                <?= lang('adjustment_mode', 'mode', 'class="tip" title="Formula: 5 to increase or -5 to decrease. Overwrite: Overwrite current stock quantity."'); ?>
                <?php
                  $modes = [
                    '' => lang('select') . ' adjustment mode',
                    'formula'   => 'Formula',
                    'overwrite' => 'Overwrite'
                  ];
                ?>
                <?= form_dropdown('mode', $modes, '', 'class="select2" id="mode" required="required"'); ?>
              </div>
            </div>
            <div class="clearfix"></div>

              <div class="col-md-12">
                <div class="form-group">
                  <?= lang('note', 'qanote'); ?>
                  <?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : ''), 'class="form-control" id="qanote" style="margin-top: 10px; height: 100px;"'); ?>
                </div>
              </div>
              <div class="clearfix"></div>

            <div class="col-md-12">
              <div
                class="fprom-group"><?php echo form_submit('add_adjustment', lang('submit'), 'id="add_adjustment" class="btn btn-primary" style="padding: 6px 15px; margin:15px 0;"'); ?>
                <button type="button" class="btn btn-danger" id="reset"><?= lang('reset') ?></div>
            </div>
          </div>
        </div>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
