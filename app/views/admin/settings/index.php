<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$wm = ['0' => lang('no'), '1' => lang('yes')];
$ps = ['0' => lang('disable'), '1' => lang('enable')];
?>
<script>
  $(document).ready(function() {
    <?php if (isset($message)) {
      echo 'localStorage.clear();';
    } ?>
    var timezones = <?= json_encode(DateTimeZone::listIdentifiers(DateTimeZone::ALL)); ?>;
    $('#timezone').autocomplete({
      source: timezones
    });
    if ($('#protocol').val() == 'smtp') {
      $('#smtp_config').slideDown();
    } else if ($('#protocol').val() == 'sendmail') {
      $('#sendmail_config').slideDown();
    }
    $('#protocol').change(function() {
      if ($(this).val() == 'smtp') {
        $('#sendmail_config').slideUp();
        $('#smtp_config').slideDown();
      } else if ($(this).val() == 'sendmail') {
        $('#smtp_config').slideUp();
        $('#sendmail_config').slideDown();
      } else {
        $('#smtp_config').slideUp();
        $('#sendmail_config').slideUp();
      }
    });
    $('#overselling').change(function() {
      if ($(this).val() == 1) {
        if ($('#accounting_method').select2("val") != 2) {
          bootbox.alert('<?= lang('overselling_will_only_work_with_AVCO_accounting_method_only') ?>');
          $('#accounting_method').select2("val", '2');
        }
      }
    });
    $('#accounting_method').change(function() {
      var oam = <?= $Settings->accounting_method ?>,
        nam = $(this).val();
      if (oam != nam) {
        bootbox.alert('<?= lang('accounting_method_change_alert') ?>');
      }
    });
    $('#accounting_method').change(function() {
      if ($(this).val() != 2) {
        if ($('#overselling').select2("val") == 1) {
          bootbox.alert('<?= lang('overselling_will_only_work_with_AVCO_accounting_method_only') ?>');
          $('#overselling').select2("val", 0);
        }
      }
    });
    $('#item_addition').change(function() {
      if ($(this).val() == 1) {
        bootbox.alert('<?= lang('product_variants_feature_x') ?>');
      }
    });
    var sac = $('#sac').val()
    if (sac == 1) {
      $('.nsac').slideUp();
    } else {
      $('.nsac').slideDown();
    }
    $('#sac').change(function() {
      if ($(this).val() == 1) {
        $('.nsac').slideUp();
      } else {
        $('.nsac').slideDown();
      }
    });
  });
</script>
<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fa fa-cog"></i><?= lang('system_settings'); ?></h2>

    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown"><a href="<?= admin_url('system_settings/mutasibank') ?>" class="toggle_down">
            <i class="icon fa fa-code"></i><span class="padding-right-10"><?= lang('mutasibank'); ?></span></a>
        </li>
      </ul>
    </div>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">

        <p class="introtext"><?= lang('update_info'); ?></p>

        <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
        echo admin_form_open_multipart('system_settings', $attrib);
        ?>
        <div class="row">
          <div class="col-lg-12">
            <fieldset class="scheduler-border">
              <legend class="scheduler-border"><?= lang('site_config') ?></legend>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('site_name', 'site_name', ['data-toggle' => 'popover', 'data-content' => 'Your site name', 'data-trigger' => 'hover']); ?>
                    <?= form_input('site_name', $Settings->site_name, 'class="form-control tip" id="site_name"  required="required"'); ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('language', 'language', ['data-toggle' => 'popover', 'data-content' => 'Language of site name', 'data-trigger' => 'hover']); ?>
                    <?php
                    $lang = [
                      'arabic'               => 'Arabic',
                      'english'              => 'English',
                      'german'               => 'German',
                      'indonesian'           => 'Indonesian',
                      'portuguese-brazilian' => 'Portuguese (Brazil)',
                      'simplified-chinese'   => 'Simplified Chinese',
                      'spanish'              => 'Spanish',
                      'thai'                 => 'Thai',
                      'traditional-chinese'  => 'Traditional Chinese',
                      'turkish'              => 'Turkish',
                      'vietnamese'           => 'Vietnamese',
                    ];
                    echo form_dropdown('language', $lang, $Settings->language, 'class="select2" id="language" required="required" style="width:100%;"');
                    ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="currency"><?= lang('default_currency'); ?></label>

                    <div class="controls">
                      <?php
                      $cu = [];
                      if ($currencies) {
                        foreach ($currencies as $currency) {
                          $cu[$currency->code] = $currency->name;
                        }
                      }
                      // d($cu); d($Settings->default_currency); die();
                      echo form_dropdown('currency', $cu, $Settings->default_currency, 'class="form-control" id="currency" required="required" style="width:100%;"');
                      ?>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('accounting_method', 'accounting_method'); ?>
                    <?php
                    $am = [0 => 'FIFO (First In First Out)', 1 => 'LIFO (Last In First Out)', 2 => 'AVCO (Average Cost Method)'];
                    echo form_dropdown('accounting_method', $am, $Settings->accounting_method, 'class="select2" id="accounting_method" required="required" style="width:100%;"');
                    ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="email"><?= lang('default_email'); ?></label>

                    <?= form_input('email', $Settings->default_email, 'class="form-control tip" required="required" id="email"'); ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="customer_group"><?= lang('default_customer_group'); ?></label>
                    <?php
                    $pgs = [];
                    if ($customer_groups) {
                      foreach ($customer_groups as $customer_group) {
                        $pgs[$customer_group->id] = $customer_group->name;
                      }
                    }
                    echo form_dropdown('customer_group', $pgs, $Settings->customer_group, 'class="select2" id="customer_group" style="width:100%" required="required"');
                    ?>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="price_group"><?= lang('default_price_group'); ?></label>
                    <?php
                    $cgs = [];
                    if ($price_groups) {
                      foreach ($price_groups as $price_group) {
                        $cgs[$price_group->id] = $price_group->name;
                      }
                    }
                    echo form_dropdown('price_group', $cgs, $Settings->price_group, 'class="select2" id="price_group" style="width:100%;" required="required"');
                    ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('maintenance_mode', 'mmode'); ?>
                    <div class="controls"> <?php
                                            echo form_dropdown('mmode', $wm, (isset($_POST['mmode']) ? $_POST['mmode'] : $Settings->mmode), 'class="select2" required="required" id="mmode" style="width:100%;"');
                                            ?> </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="theme"><?= lang('theme'); ?></label>

                    <div class="controls">
                      <?php
                      $themes = [
                        'default' => 'Default'
                      ];
                      echo form_dropdown('theme', $themes, $Settings->theme, 'id="theme" class="select2" required="required" style="width:100%;"');
                      ?>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="rtl"><?= lang('rtl_support'); ?></label>

                    <div class="controls">
                      <?php
                      echo form_dropdown('rtl', $ps, $Settings->rtl, 'id="rtl" class="select2" required="required" style="width:100%;"');
                      ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="captcha"><?= lang('login_captcha'); ?></label>

                    <div class="controls">
                      <?php
                      echo form_dropdown('captcha', $ps, $Settings->captcha, 'id="captcha" class="select2" required="required" style="width:100%;"');
                      ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="disable_editing"><?= lang('disable_editing'); ?></label>
                    <?= form_input('disable_editing', $Settings->disable_editing, 'class="form-control" id="disable_editing" required="required"'); ?>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="rows_per_page"><?= lang('rows_per_page'); ?></label>
                    <?php
                    $rppopts = ['10' => '10', '25' => '25', '50' => '50',  '100' => '100', '-1' => lang('all') . ' (' . lang('not_recommended') . ')'];
                    echo form_dropdown('rows_per_page', $rppopts, $Settings->rows_per_page, 'id="rows_per_page" class="select2" style="width:100%;" required="required"');
                    ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="dateformat"><?= lang('dateformat'); ?></label>

                    <div class="controls">
                      <?php
                      foreach ($date_formats as $date_format) {
                        $dt[$date_format->id] = $date_format->js;
                      }
                      echo form_dropdown('dateformat', $dt, $Settings->dateformat, 'id="dateformat" class="select2" style="width:100%;" required="required"');
                      ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="timezone"><?= lang('timezone'); ?></label>
                    <?php
                    $timezone_identifiers = DateTimeZone::listIdentifiers();
                    foreach ($timezone_identifiers as $tzi) {
                      $tz[$tzi] = $tzi;
                    }
                    ?>
                    <?= form_dropdown('timezone', $tz, TIMEZONE, 'class="select2" id="timezone" required="required" style="width:100%;"'); ?>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="restrict_calendar"><?= lang('calendar'); ?></label>

                    <div class="controls">
                      <?php
                      $opt_cal = [1 => lang('private'), 0 => lang('shared')];
                      echo form_dropdown('restrict_calendar', $opt_cal, $Settings->restrict_calendar, 'class="select2" required="required" id="restrict_calendar" style="width:100%;"');
                      ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="warehouse"><?= lang('default_warehouse'); ?></label>

                    <div class="controls">
                      <?php
                      $wh = [];
                      if ($warehouses) {
                        foreach ($warehouses as $warehouse) {
                          $wh[$warehouse->id] = $warehouse->name . ' (' . $warehouse->code . ')';
                        }
                      }
                      echo form_dropdown('warehouse', $wh, $Settings->default_warehouse, 'class="select2" id="warehouse" required="required" style="width:100%;"');
                      ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('default_biller', 'biller'); ?>
                    <?php
                    $bl[''] = '';
                    if (!empty($billers)) {
                      foreach ($billers as $biller) {
                        $bl[$biller->id] = $biller->name;
                      }
                      echo form_dropdown('biller', $bl, (isset($_POST['biller']) ? $_POST['biller'] : $Settings->default_biller), 'id="biller" data-placeholder="' . lang('select') . ' ' . lang('biller') . '" required="required" class="select2" style="width:100%;"');
                    } else {
                      echo form_dropdown('biller', 'No Billers found', '', 'id="biller" data-placeholder="' . lang('select') . ' ' . lang('biller') . '" required="required" class="select2" style="width:100%;" disabled');
                    }
                    ?>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('pdf_lib', 'pdf_lib'); ?>
                    <?php $pdflibs = ['mpdf' => 'mPDF', 'dompdf' => 'Dompdf']; ?>
                    <?= form_dropdown('pdf_lib', $pdflibs, $Settings->pdf_lib, 'class="select2" id="pdf_lib" required="required" style="width:100%"'); ?>
                  </div>
                </div>
              </div>
            </fieldset>

            <fieldset class="scheduler-border">
              <legend class="scheduler-border"><?= lang('products') ?></legend>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('product_tax', 'tax_rate'); ?>
                    <?php
                    echo form_dropdown('tax_rate', $ps, $Settings->default_tax_rate, 'class="select2" id="tax_rate" required="required" style="width:100%;"');
                    ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="racks"><?= lang('racks'); ?></label>

                    <div class="controls">
                      <?php
                      echo form_dropdown('racks', $ps, $Settings->racks, 'id="racks" class="select2" required="required" style="width:100%;"');
                      ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="attributes"><?= lang('attributes'); ?></label>

                    <div class="controls">
                      <?php
                      echo form_dropdown('attributes', $ps, $Settings->attributes, 'id="attributes" class="select2"  required="required" style="width:100%;"');
                      ?>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="product_expiry"><?= lang('product_expiry'); ?></label>
                    <div class="controls">
                      <?php
                      echo form_dropdown('product_expiry', $ps, $Settings->product_expiry, 'id="product_expiry" class="select2" required="required" style="width:100%;"');
                      ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="remove_expired"><?= lang('remove_expired'); ?></label>

                    <div class="controls">
                      <?php
                      $re_opts = [0 => lang('no') . ', ' . lang('i_ll_remove'), 1 => lang('yes') . ', ' . lang('remove_automatically')];
                      echo form_dropdown('remove_expired', $re_opts, $Settings->remove_expired, 'id="remove_expired" class="select2" required="required" style="width:100%;"');
                      ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="image_size"><?= lang('image_size'); ?> (Width :
                      Height) *</label>
                    <div class="row">
                      <div class="col-xs-6">
                        <?= form_input('iwidth', $Settings->iwidth, 'class="form-control tip" id="iwidth" placeholder="image width" required="required"'); ?>
                      </div>
                      <div class="col-xs-6">
                        <?= form_input('iheight', $Settings->iheight, 'class="form-control tip" id="iheight" placeholder="image height" required="required"'); ?></div>
                    </div>
                    <div class="clearfix"></div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="thumbnail_size"><?= lang('thumbnail_size'); ?>
                      (Width : Height) *</label>

                    <div class="row">
                      <div class="col-xs-6">
                        <?= form_input('twidth', $Settings->twidth, 'class="form-control tip" id="twidth" placeholder="thumbnail width" required="required"'); ?>
                      </div>
                      <div class="col-xs-6">
                        <?= form_input('theight', $Settings->theight, 'class="form-control tip" id="theight" placeholder="thumbnail height" required="required"'); ?>
                      </div>
                    </div>
                    <div class="clearfix"></div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('watermark', 'watermark'); ?>
                    <?php
                    echo form_dropdown('watermark', $wm, (isset($_POST['watermark']) ? $_POST['watermark'] : $Settings->watermark), 'class="select2" required="required" id="watermark" style="width:100%;"');
                    ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('display_all_products', 'display_all_products'); ?>
                    <?php
                    $dopts = [0 => lang('hide_with_0_qty'), 1 => lang('show_with_0_qty')];
                    echo form_dropdown('display_all_products', $dopts, (isset($_POST['display_all_products']) ? $_POST['display_all_products'] : $Settings->display_all_products), 'class="select2" required="required" id="display_all_products" style="width:100%;"');
                    ?>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('barcode_separator', 'barcode_separator'); ?>
                    <?php
                    $bcopts = ['-' => lang('dash'), '.' => lang('dot'), '~' => lang('tilde'), '_' => lang('underscore')];
                    echo form_dropdown('barcode_separator', $bcopts, (isset($_POST['barcode_separator']) ? $_POST['barcode_separator'] : $Settings->barcode_separator), 'class="select2" required="required" id="barcode_separator" style="width:100%;"');
                    ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('barcode_renderer', 'barcode_renderer'); ?>
                    <?php
                    $bcropts = [1 => lang('image'), 0 => lang('svg')];
                    echo form_dropdown('barcode_renderer', $bcropts, (isset($_POST['barcode_renderer']) ? $_POST['barcode_renderer'] : $Settings->barcode_img), 'class="select2" required="required" id="barcode_renderer" style="width:100%;"');
                    ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('update_cost_with_purchase', 'update_cost'); ?>
                    <?= form_dropdown('update_cost', $wm, $Settings->update_cost, 'class="select2" id="update_cost" required="required" style="width:100%"'); ?>
                  </div>
                </div>
              </div>
            </fieldset>
            
            <fieldset class="scheduler-border">
              <legend class="scheduler-border">QMS Configuration</legend>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('QMS Ticket Expired Time (Minutes)', 'qms_expired_time') ?>
                    <?= form_input('qms_expired_time', ($settings_json->qms_expired_time ?? 0), 'class="form-control" id="qms_expired_time"') ?>
                  </div>
                </div>
              </div>
            </fieldset>

            <fieldset class="scheduler-border">
              <legend class="scheduler-border"><?= lang('safety_stock'); ?></legend>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Safety Stock Period (Month)</label>
                    <?= form_input('safety_stock_period', ($settings_json->safety_stock_period ?? 1), 'class="form-control tip" required="required"'); ?>
                  </div>
                </div>
              </div>
            </fieldset>

            <fieldset class="scheduler-border">
              <legend class="scheduler-border"><?= lang('sales') ?></legend>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="overselling" data-toggle="popover" data-content="You can sell item even the quantity of item is 0 (zero) if you enabled it." data-trigger="hover"><?= lang('over_selling'); ?></label>
                    <div class="controls">
                      <?php
                      $opt = [1 => lang('yes'), 0 => lang('no')];
                      echo form_dropdown('restrict_sale', $opt, $Settings->overselling, 'class="select2" id="overselling" required="required" style="width:100%;"');
                      ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="reference_format"><?= lang('reference_format'); ?></label>
                    <div class="controls">
                      <?php
                      $ref = [1 => lang('prefix_year_no'), 2 => lang('prefix_month_year_no'), 3 => lang('sequence_number'), 4 => lang('random_number')];
                      echo form_dropdown('reference_format', $ref, $Settings->reference_format, 'class="select2" required="required" id="reference_format" style="width:100%;"');
                      ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('invoice_tax', 'tax_rate2'); ?>
                    <?php $tr['0'] = lang('disable');
                    echo form_dropdown('tax_rate2', $tr, $Settings->default_tax_rate2, 'id="tax_rate2" class="select2" required="required" style="width:100%;"'); ?>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="product_discount"><?= lang('product_level_discount'); ?></label>
                    <div class="controls">
                      <?php
                      echo form_dropdown('product_discount', $ps, $Settings->product_discount, 'id="product_discount" class="select2" required="required" style="width:100%;"');
                      ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="product_serial"><?= lang('product_serial'); ?></label>
                    <div class="controls">
                      <?php
                      echo form_dropdown('product_serial', $ps, $Settings->product_serial, 'id="product_serial" class="select2" required="required" style="width:100%;"');
                      ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="detect_barcode"><?= lang('auto_detect_barcode'); ?></label>
                    <div class="controls">
                      <?php
                      echo form_dropdown('detect_barcode', $ps, $Settings->auto_detect_barcode, 'id="detect_barcode" class="select2" required="required" style="width:100%;"');
                      ?>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="bc_fix"><?= lang('bc_fix'); ?></label>
                    <?= form_input('bc_fix', $Settings->bc_fix, 'class="form-control tip" required="required" id="bc_fix"'); ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="item_addition"><?= lang('item_addition'); ?></label>
                    <div class="controls">
                      <?php
                      $ia = [0 => lang('add_new_item'), 1 => lang('increase_quantity_if_item_exist')];
                      echo form_dropdown('item_addition', $ia, $Settings->item_addition, 'id="item_addition" class="select2" required="required" style="width:100%;"');
                      ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('set_focus', 'set_focus'); ?>
                    <?php
                    $sfopts = [0 => 'Disable', 1 => lang('add_item_input'), 2 => lang('last_order_item')];
                    echo form_dropdown('set_focus', $sfopts, (isset($_POST['set_focus']) ? $_POST['set_focus'] : $Settings->set_focus), 'id="set_focus" data-placeholder="' . lang('select') . ' ' . lang('set_focus') . '" required="required" class="select2" style="width:100%;"');
                    ?>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="invoice_view"><?= lang('invoice_view'); ?></label>
                    <div class="controls">
                      <?php
                      $opt_inv = [0 => lang('standard')];
                      echo form_dropdown('invoice_view', $opt_inv, $Settings->invoice_view, 'class="select2" required="required" id="invoice_view" style="width:100%;"');
                      ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="min_dp"><?= lang('min_dp'); ?></label>
                    <?= form_input('min_dp', ($settings_json->min_dp ?? 0), 'class="form-control currency" id="min_dp"'); ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="min_dp_percent"><?= lang('min_dp_percent'); ?></label>
                    <?= form_input('min_dp_percent', ($settings_json->min_dp_percent ?? 0), 'class="form-control" id="min_dp_percent"'); ?>
                  </div>
                </div>
              </div>
            </fieldset>

            <fieldset class="scheduler-border">
              <legend class="scheduler-border"><?= lang('prefix') ?></legend>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="sales_prefix"><?= lang('sales_prefix'); ?></label>
                    <?= form_input('sales_prefix', $Settings->sales_prefix, 'class="form-control tip" id="sales_prefix"'); ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="return_prefix"><?= lang('return_prefix'); ?></label>
                    <?= form_input('return_prefix', $Settings->return_prefix, 'class="form-control tip" id="return_prefix"'); ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="payment_prefix"><?= lang('payment_prefix'); ?></label>
                    <?= form_input('payment_prefix', $Settings->payment_prefix, 'class="form-control tip" id="payment_prefix"'); ?>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="ppayment_prefix"><?= lang('ppayment_prefix'); ?></label>
                    <?= form_input('ppayment_prefix', $Settings->ppayment_prefix, 'class="form-control tip" id="ppayment_prefix"'); ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="tpayment_prefix"><?= lang('tpayment_prefix'); ?></label>
                    <?= form_input('tpayment_prefix', $Settings->tpayment_prefix, 'class="form-control tip" id="tpayment_prefix"'); ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="delivery_prefix"><?= lang('delivery_prefix'); ?></label>
                    <?= form_input('delivery_prefix', $Settings->delivery_prefix, 'class="form-control tip" id="delivery_prefix"'); ?>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="quote_prefix"><?= lang('quote_prefix'); ?></label>
                    <?= form_input('quote_prefix', $Settings->quote_prefix, 'class="form-control tip" id="quote_prefix"'); ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="purchase_prefix"><?= lang('purchase_prefix'); ?></label>
                    <?= form_input('purchase_prefix', $Settings->purchase_prefix, 'class="form-control tip" id="purchase_prefix"'); ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="returnp_prefix"><?= lang('returnp_prefix'); ?></label>
                    <?= form_input('returnp_prefix', $Settings->returnp_prefix, 'class="form-control tip" id="returnp_prefix"'); ?>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="transfer_prefix"><?= lang('transfer_prefix'); ?></label>
                    <?= form_input('transfer_prefix', $Settings->transfer_prefix, 'class="form-control tip" id="transfer_prefix"'); ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('expense_prefix', 'expense_prefix'); ?>
                    <?= form_input('expense_prefix', $Settings->expense_prefix, 'class="form-control tip" id="expense_prefix"'); ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('income_prefix', 'income_prefix'); ?>
                    <?= form_input('income_prefix', $Settings->income_prefix, 'class="form-control tip" id="income_prefix"'); ?>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('mutation_prefix', 'mutation_prefix'); ?>
                    <?= form_input('mutation_prefix', $Settings->mutation_prefix, 'class="form-control tip" id="mutation_prefix"'); ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('qa_prefix', 'qa_prefix'); ?>
                    <?= form_input('qa_prefix', $Settings->qa_prefix, 'class="form-control tip" id="qa_prefix"'); ?>
                  </div>
                </div>
              </div>
            </fieldset>

            <fieldset class="scheduler-border">
              <legend class="scheduler-border"><?= lang('money_number_format') ?></legend>
              <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label class="control-label" for="decimals"><?= lang('decimals'); ?></label>
                  <div class="controls">
                    <?php
                    $decimals = [0 => lang('disable'), 1 => '1', 2 => '2', 3 => '3', 4 => '4'];
                    echo form_dropdown('decimals', $decimals, $Settings->decimals, 'class="select2" id="decimals"  style="width:100%;" required="required"');
                    ?>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label class="control-label" for="qty_decimals"><?= lang('qty_decimals'); ?></label>
                  <div class="controls">
                    <?php
                    $qty_decimals = [0 => lang('disable'), 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6']; // Added 5 and 6 qty_decimals
                    echo form_dropdown('qty_decimals', $qty_decimals, $Settings->qty_decimals, 'class="select2" id="qty_decimals"  style="width:100%;" required="required"');
                    ?>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <?= lang('sac', 'sac'); ?>
                  <?= form_dropdown('sac', $ps, set_value('sac', $Settings->sac), 'class="select2" id="sac"  required="required" style="width:100%"'); ?>
                </div>
              </div>
                    </div>

              <div class="nsac">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="decimals_sep"><?= lang('decimals_sep'); ?></label>

                    <div class="controls"> <?php
                                            $dec_point = ['.' => lang('dot'), ',' => lang('comma')];
                                            echo form_dropdown('decimals_sep', $dec_point, $Settings->decimals_sep, 'class="select2" id="decimals_sep"  style="width:100%;" required="required"');
                                            ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="control-label" for="thousands_sep"><?= lang('thousands_sep'); ?></label>
                    <div class="controls"> <?php
                                            $thousands_sep = ['.' => lang('dot'), ',' => lang('comma'), '0' => lang('space')];
                                            echo form_dropdown('thousands_sep', $thousands_sep, $Settings->thousands_sep, 'class="select2" id="thousands_sep"  style="width:100%;" required="required"');
                                            ?>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <?= lang('display_currency_symbol', 'display_symbol'); ?>
                  <?php $opts = [0 => lang('disable'), 1 => lang('before'), 2 => lang('after')]; ?>
                  <?= form_dropdown('display_symbol', $opts, $Settings->display_symbol, 'class="select2" id="display_symbol" style="width:100%;" required="required"'); ?>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <?= lang('currency_symbol', 'symbol'); ?>
                  <?= form_input('symbol', $Settings->symbol, 'class="select2" id="symbol" style="width:100%;"'); ?>
                </div>
              </div>
            </fieldset>

            <fieldset class="scheduler-border">
              <legend class="scheduler-border"><?= lang('email') ?></legend>
              <div class="col-md-4">
                <div class="form-group">
                  <label class="control-label" for="protocol"><?= lang('email_protocol'); ?></label>

                  <div class="controls">
                    <?php
                    $popt = ['mail' => 'PHP Mail Function', 'sendmail' => 'Send Mail', 'smtp' => 'SMTP'];
                    echo form_dropdown('protocol', $popt, $Settings->protocol, 'class="select2" id="protocol"  style="width:100%;" required="required"');
                    ?>
                  </div>
                </div>
              </div>
              <div class="clearfix"></div>
              <div class="row" id="sendmail_config" style="display: none;">
                <div class="col-md-12">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="control-label" for="mailpath"><?= lang('mailpath'); ?></label>

                      <?= form_input('mailpath', $Settings->mailpath, 'class="form-control tip" id="mailpath"'); ?>
                    </div>
                  </div>
                </div>
              </div>
              <div class="clearfix"></div>
              <div class="row" id="smtp_config" style="display: none;">
                <div class="col-md-12">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="control-label" for="smtp_host"><?= lang('smtp_host'); ?></label>

                      <?= form_input('smtp_host', $Settings->smtp_host, 'class="form-control tip" id="smtp_host"'); ?>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="control-label" for="smtp_user"><?= lang('smtp_user'); ?></label>

                      <?= form_input('smtp_user', $Settings->smtp_user, 'class="form-control tip" id="smtp_user"'); ?>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="control-label" for="smtp_pass"><?= lang('smtp_pass'); ?></label>

                      <?= form_password('smtp_pass', $Settings->smtp_pass, 'class="form-control tip" id="smtp_pass" autocomplete="off"'); ?>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="control-label" for="smtp_port"><?= lang('smtp_port'); ?></label>

                      <?= form_input('smtp_port', $Settings->smtp_port, 'class="form-control tip" id="smtp_port"'); ?>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label class="control-label" for="smtp_crypto"><?= lang('smtp_crypto'); ?></label>

                      <div class="controls"> <?php
                                              $crypto_opt = ['' => lang('none'), 'tls' => 'TLS', 'ssl' => 'SSL'];
                                              echo form_dropdown('smtp_crypto', $crypto_opt, $Settings->smtp_crypto, 'class="select2" id="smtp_crypto" style="width:100%"');
                                              ?> </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <input id="testmail_to" class="form-control" type="text" value="" placeholder="Your test email" autocomplete="off">
                  <button class="btn btn-primary form-control" id="send_test_mail"><i class="fa fa-fw fa-envelope"></i> Send Test Mail</button>
                </div>
              </div>
            </fieldset>

            <fieldset class="scheduler-border">
              <legend class="scheduler-border"><?= lang('award_points') ?></legend>
              <div class="col-md-12">
                <div class="form-group">
                  <label class="control-label"><?= lang('customer_award_points'); ?></label>

                  <div class="row">
                    <div class="col-sm-4 col-xs-6">
                      <?= lang('each_spent'); ?><br>
                      <?= form_input('each_spent', $this->sma->formatDecimal($Settings->each_spent), 'class="form-control"'); ?>
                    </div>
                    <div class="col-sm-1 col-xs-1 text-center"><i class="fa fa-arrow-right"></i>
                    </div>
                    <div class="col-sm-4 col-xs-5">
                      <?= lang('award_points'); ?><br>
                      <?= form_input('ca_point', $Settings->ca_point, 'class="form-control"'); ?>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-12">
                <div class="form-group">
                  <label class="control-label"><?= lang('staff_award_points'); ?></label>

                  <div class="row">
                    <div class="col-sm-4 col-xs-6">
                      <?= lang('each_in_sale'); ?><br>
                      <?= form_input('each_sale', $this->sma->formatDecimal($Settings->each_sale), 'class="form-control"'); ?>
                    </div>
                    <div class="col-sm-1 col-xs-1 text-center"><i class="fa fa-arrow-right"></i>
                    </div>
                    <div class="col-sm-4 col-xs-5">
                      <?= lang('award_points'); ?><br>
                      <?= form_input('sa_point', $Settings->sa_point, 'class="form-control"'); ?>
                    </div>
                  </div>

                </div>
              </div>
            </fieldset>

          </div>
        </div>
        <div class="cleafix"></div>
        <div class="form-group">
          <div class="controls">
            <?= form_submit('update_settings', lang('update_settings'), 'class="btn btn-primary btn-lg"'); ?>
          </div>
        </div>
        <?= form_close(); ?>
      </div>
    </div>
    <div class="alert alert-info" role="alert">
      <p>
        <a class="btn btn-primary btn-xs pull-right" target="_blank" href="<?= admin_url('cron/run'); ?>">Run cron job now</a>
      <p><strong>Cron Job</strong> (run at 1:00 AM daily):</p>
      <pre>0 1 * * * wget -qO- <?= admin_url('cron/run'); ?> &gt;/dev/null 2&gt;&amp;1</pre>
      OR
      <pre>0 1 * * * <?= (defined('PHP_BINDIR') ? PHP_BINDIR . DIRECTORY_SEPARATOR : '') . 'php ' . FCPATH . SELF . ' admin/cron run'; ?> >/dev/null 2>&1</pre>
      For CLI: <code>schedule path/to/php path/to/index.php controller method</code>
      </p>
    </div>
  </div>
</div>
<script>
  $(document).ready(function() {
    $('#send_test_mail').click(function() {
      $.ajax({
        data: {
          to: $('#testmail_to').val(),
          <?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>'
        },
        method: 'POST',
        url: site.base_url + 'system_settings/testmail',
        success: function(data) {
          console.log(data);
          if (!data.error) {
            addAlert(data.msg, 'success');
          } else {
            addAlert(data.msg, 'danger');
          }
        }
      });
      return false;
    });
    $('#invoice_view').change(function(e) {
      if ($(this).val() == 2) {
        $('#states').show();
      } else {
        $('#states').hide();
      }
    });
    if ($('#invoice_view').val() == 2) {
      $('#states').show();
    } else {
      $('#states').hide();
    }
    $('[data-toggle="popover"]').popover();

    $('#min_dp').val(formatCurrency('<?= ($Settings_json->min_dp ?? 0); ?>'));
  });
</script>