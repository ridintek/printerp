<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
  $(document).ready(function () {
    let api_keys = JSON.parse('<?= $mutasibank->api_keys; ?>');
    $('#APITable').find('[name="api_keys[]"]').val(api_keys.shift());
    for (let key of api_keys) {
      $('#APITable').find('tbody').append(`<tr><td><input class="form-control" name="api_keys[]" type="text" value="${key}" /></td></tr>`);
    }

    $('#add_api_key').on('click', function (e) {
      $('#APITable').find('tbody').append('<tr><td><input class="form-control" name="api_keys[]" type="text" value="" /></td></tr>');
      e.preventDefault();
    });
  });
</script>
<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fa fa-cog"></i><?= lang('mutasibank_settings'); ?></h2>
    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown"><a href="<?= admin_url('system_settings/paypal') ?>" class="toggle_up"><i
          class="icon fa fa-paypal"></i><span
          class="padding-right-10"><?= lang('paypal'); ?></span></a></li>
        <li class="dropdown"><a href="<?= admin_url('system_settings/skrill') ?>" class="toggle_down"><i
          class="icon fa fa-bank"></i><span class="padding-right-10"><?= lang('skrill'); ?></span></a>
        </li>
        <li class="dropdown"><a href="<?= admin_url('system_settings/mutasibank') ?>" class="toggle_down"><i
          class="icon fa fa-bank"></i><span class="padding-right-10"><?= lang('mutasibank'); ?></span></a>
        </li>
      </ul>
    </div>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <p class="introtext"><?= lang('update_info'); ?></p>
        <?php $attrib = ['role' => 'form', 'id="mutasibank_form"'];
          echo admin_form_open('system_settings/mutasibank', $attrib);
          ?>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <?= lang('activate', 'active'); ?>
              <?php
                $yn = ['1' => 'Yes', '0' => 'No'];
                echo form_dropdown('active', $yn, $mutasibank->active, 'class="form-control tip" required="required" id="active"');
                ?>
            </div>
            <div class="form-group">
              <table class="table table-bordered table-condensed table-hover" id="APITable">
                <thead>
                  <tr>
                    <th>API Keys</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><input class="form-control" name="api_keys[]" type="text" value="" /></td>
                  </tr>
                </tbody>
              </table>
              <button class="btn" id="add_api_key"><i class="fa fa-plus"></i> Add API Keys</button>
            </div>
          </div>
        </div>
        <div style="clear: both; height: 10px;"></div>
        <div class="form-group">
          <?php echo form_submit('update_settings', lang('update_settings'), 'class="btn btn-primary"'); ?>
        </div>
      </div>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>